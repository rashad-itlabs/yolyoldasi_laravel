<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Ride;
use App\Models\User;
use App\Models\UserStat;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Ləğv etdikdən sonra eyni səfərə neçə dəfə yenidən müraciət etmək olar.
     *
     * Əvvəllər qayda qalıcı idi: bir dəfə müraciət etmiş sərnişin ləğv etsə
     * belə həmin səfərə bir daha qayıda bilmirdi. Səhvən ləğv etmiş adam
     * üçün bu, itirilmiş brondur — erkən mərhələdə hər bron isə qızıldır.
     * Limit tam sərbəstlikdən yaxşıdır: sürücünü eyni sərnişinin təkrar-təkrar
     * göndərdiyi sorğularla yormaq da olmaz.
     */
    private const MAX_RETRIES = 1;

    public function __construct(
        private NotificationService $notifications,
        private RideMatchService $matcher,
        private ReferralService $referrals
    ) {
    }

    /**
     * Sərnişin rezervasiya sorğusu göndərir.
     * Yer sayı yarış şəraitinə açıqdır, ona görə səfər sətri
     * tranzaksiya daxilində lockForUpdate ilə kilidlənir.
     */
    public function request(Ride $ride, User $passenger, int $seats, ?string $message): Booking
    {
        abort_if($ride->driver_id === $passenger->id, 422, 'Öz səfərinə rezervasiya edə bilməzsən.');

        $booking = DB::transaction(function () use ($ride, $passenger, $seats, $message) {
            $ride = Ride::whereKey($ride->id)->lockForUpdate()->firstOrFail();

            abort_unless($ride->status === 'active', 422, 'Bu səfər artıq aktiv deyil.');
            abort_if($ride->departure_at->isPast(), 422, 'Səfərin vaxtı keçib.');

            // Sürücünün qoyduğu şərt serverdə tətbiq olunur. Klientdə düymənin
            // gizlədilməsi kifayət deyil — API birbaşa da çağırıla bilər.
            abort_if(
                $ride->women_only && $passenger->gender !== 'female',
                422,
                'Bu səfər yalnız qadın sərnişinlər üçündür.'
            );

            $this->guardRepeatBooking($ride, $passenger);

            $instant = (bool) $ride->instant_booking;

            if ($instant) {
                abort_if($ride->seats_left < $seats, 422, 'Kifayət qədər boş yer yoxdur.');
            }

            $booking = Booking::create([
                'ride_id'      => $ride->id,
                'passenger_id' => $passenger->id,
                'driver_id'    => $ride->driver_id,
                'seats'        => $seats,
                'total_price'  => $ride->price_per_seat * $seats,
                'status'       => $instant ? 'confirmed' : 'pending',
                'message'      => $message,
                'decided_at'   => $instant ? now() : null,
            ]);

            if ($instant) {
                $ride->increment('booked_seats', $seats);
            } else {
                // Cavab statistikasının məxrəci. Ani bron sayılmır: orada
                // sürücünün cavab verəcəyi bir şey yoxdur və faiz süni
                // şəkildə yüksələrdi.
                UserStat::where('user_id', $ride->driver_id)->increment('driver_requests_total');
            }

            $this->openConversation($booking);

            // Ani bronda sürücüyə `bookingConfirmed` yox, ayrıca növ gedir.
            // `bookingConfirmed` sərnişinin bildirişidir ("Bronunuz
            // təsdiqləndi") — sürücüyə eyni mətn getsə, o, kiminsə yer
            // tutduğunu yox, özünün bron etdiyini oxuyardı.
            $this->notifications->send($ride->driver_id, $instant ? 'bookingInstant' : 'bookingRequested', [
                'ride_id'    => $ride->id,
                'booking_id' => $booking->id,
                'actor_id'   => $passenger->id,
                'payload'    => ['seats' => $seats],
            ]);

            return $booking;
        });

        // Sərnişinin həmin marşrutdakı tələb elanı bağlanır — açıq qalsa,
        // sürücülərin siyahısında artıq həll olunmuş tələb görünərdi.
        $this->matcher->markFulfilled($passenger->id, $ride);

        // Dəvət olunmuş sərnişinin ilk real hərəkəti — dəvət edənə mükafat.
        $this->referrals->reward($passenger);

        return $booking;
    }

    /**
     * Eyni səfərə təkrar müraciətə icazə verilirmi.
     *
     * Ayırd edən şey **kimin** bağladığıdır. Sürücü rədd edib və ya ləğv
     * edibsə, cavab artıq verilib və təkrar sorğu onu yenidən narahat
     * etməkdən başqa heç nə deyil. Sərnişin özü ləğv edibsə, bu, fikir
     * dəyişməsidir və ona bir şans verilir.
     */
    private function guardRepeatBooking(Ride $ride, User $passenger): void
    {
        $previous = Booking::where('ride_id', $ride->id)
            ->where('passenger_id', $passenger->id)
            ->get();

        if ($previous->isEmpty()) {
            return;
        }

        $blocking = $previous->whereNotIn('status', ['cancelled_by_passenger']);

        abort_if(
            $blocking->isNotEmpty(),
            409,
            'Bu səfərə artıq müraciət etmisən.'
        );

        abort_if(
            $previous->count() > self::MAX_RETRIES,
            409,
            'Bu səfərə təkrar müraciət limitini keçmisən.'
        );
    }

    public function confirm(Booking $booking, User $driver): Booking
    {
        abort_unless($booking->driver_id === $driver->id, 403, 'Bu rezervasiya sənin deyil.');
        abort_unless($booking->status === 'pending', 422, 'Bu rezervasiya artıq cavablandırılıb.');

        return DB::transaction(function () use ($booking) {
            $ride = Ride::whereKey($booking->ride_id)->lockForUpdate()->firstOrFail();

            abort_if($ride->seats_left < $booking->seats, 422, 'Kifayət qədər boş yer qalmayıb.');

            $ride->increment('booked_seats', $booking->seats);

            $booking->update(['status' => 'confirmed', 'decided_at' => now()]);

            $this->recordResponseTime($booking);

            $this->notifications->send($booking->passenger_id, 'bookingConfirmed', [
                'ride_id'    => $booking->ride_id,
                'booking_id' => $booking->id,
                'actor_id'   => $booking->driver_id,
            ]);

            return $booking->fresh();
        });
    }

    public function reject(Booking $booking, User $driver): Booking
    {
        abort_unless($booking->driver_id === $driver->id, 403, 'Bu rezervasiya sənin deyil.');
        abort_unless($booking->status === 'pending', 422, 'Bu rezervasiya artıq cavablandırılıb.');

        $booking->update(['status' => 'rejected', 'decided_at' => now()]);

        // Rədd də cavabdır: sərnişin üçün ən pis hal cavabsızlıqdır, ona görə
        // statistika "təsdiq faizi" yox, "cavab faizi" ölçür.
        $this->recordResponseTime($booking);

        Conversation::where('booking_id', $booking->id)->update(['is_locked' => true]);

        $this->notifications->send($booking->passenger_id, 'bookingRejected', [
            'ride_id'    => $booking->ride_id,
            'booking_id' => $booking->id,
            'actor_id'   => $booking->driver_id,
        ]);

        return $booking->fresh();
    }

    public function cancel(Booking $booking, User $actor, ?string $reason): Booking
    {
        $isPassenger = $booking->passenger_id === $actor->id;
        $isDriver    = $booking->driver_id === $actor->id;

        abort_unless($isPassenger || $isDriver, 403, 'Bu rezervasiya sənin deyil.');
        abort_if(
            in_array($booking->status, ['cancelled_by_passenger', 'cancelled_by_driver', 'completed'], true),
            422,
            'Bu rezervasiya artıq bağlanıb.'
        );

        return DB::transaction(function () use ($booking, $actor, $isPassenger, $reason) {
            $heldSeat = in_array($booking->status, Booking::SEAT_HOLDING, true);

            if ($heldSeat) {
                $ride = Ride::whereKey($booking->ride_id)->lockForUpdate()->firstOrFail();
                $ride->decrement('booked_seats', min($booking->seats, $ride->booked_seats));
            }

            $booking->update([
                'status'              => $isPassenger ? 'cancelled_by_passenger' : 'cancelled_by_driver',
                'cancelled_at'        => now(),
                'cancellation_reason' => $reason,
            ]);

            Conversation::where('booking_id', $booking->id)->update(['is_locked' => true]);

            $this->notifications->send(
                $isPassenger ? $booking->driver_id : $booking->passenger_id,
                'bookingCancelled',
                [
                    'ride_id'    => $booking->ride_id,
                    'booking_id' => $booking->id,
                    'actor_id'   => $actor->id,
                    'payload'    => ['reason' => $reason],
                ]
            );

            return $booking->fresh();
        });
    }

    /**
     * Sürücü səfəri tamamlanmış elan edir: təsdiqlənmiş rezervasiyalar
     * 'completed' olur, səfər sayğacları artır, tərəflərdən rəy istənilir.
     */
    public function completeRide(Ride $ride, User $driver): Ride
    {
        abort_unless($ride->driver_id === $driver->id, 403, 'Bu səfər sənin deyil.');
        abort_unless($ride->status === 'active', 422, 'Yalnız aktiv səfər tamamlana bilər.');
        abort_if($ride->departure_at->isFuture(), 422, 'Səfər hələ başlamayıb.');

        return DB::transaction(function () use ($ride) {
            $bookings = Booking::where('ride_id', $ride->id)->where('status', 'confirmed')->get();

            foreach ($bookings as $booking) {
                $booking->update(['status' => 'completed']);

                UserStat::where('user_id', $booking->passenger_id)->increment('passenger_trip_count');

                $this->notifications->send($booking->passenger_id, 'reviewRequest', [
                    'ride_id'    => $ride->id,
                    'booking_id' => $booking->id,
                    'actor_id'   => $ride->driver_id,
                ]);
            }

            if ($bookings->isNotEmpty()) {
                UserStat::where('user_id', $ride->driver_id)->increment('driver_trip_count');
            }

            $ride->update(['status' => 'completed']);

            return $ride->fresh();
        });
    }

    /**
     * Sürücünün bu sorğuya nə qədər vaxta cavab verdiyini yazır.
     *
     * İki sütun saxlanılır (sayğac və dəqiqələr cəmi), hazır orta yox: orta
     * saxlansaydı, hər yeni cavabda bütün tarixçəni yenidən oxumaq lazım
     * gələrdi. Rəqəm sərnişinə "adətən 2 saat ərzində cavab verir" kimi
     * göstərilir — müraciət göndərməzdən əvvəlki ən vacib naməlum budur.
     *
     * Bir həftədən uzun gecikmə kənara atılır: unudulmuş, sonra təsadüfən
     * açılmış bir sorğu sürücünün ortasını əbədi korlamamalıdır.
     */
    private function recordResponseTime(Booking $booking): void
    {
        $minutes = (int) $booking->created_at->diffInMinutes(now());

        if ($minutes > 60 * 24 * 7) {
            return;
        }

        UserStat::where('user_id', $booking->driver_id)->update([
            'driver_decided_count'    => DB::raw('driver_decided_count + 1'),
            'driver_decision_minutes' => DB::raw('driver_decision_minutes + '.max(0, $minutes)),
        ]);
    }

    /** Söhbət rezervasiya sorğusu ilə birlikdə açılır ki, tərəflər danışa bilsin. */
    private function openConversation(Booking $booking): Conversation
    {
        $conversation = Conversation::create([
            'booking_id' => $booking->id,
            'ride_id'    => $booking->ride_id,
        ]);

        ConversationParticipant::insert([
            ['conversation_id' => $conversation->id, 'user_id' => $booking->passenger_id, 'unread_count' => 0],
            ['conversation_id' => $conversation->id, 'user_id' => $booking->driver_id,    'unread_count' => 0],
        ]);

        return $conversation;
    }
}
