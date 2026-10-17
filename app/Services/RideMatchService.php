<?php

namespace App\Services;

use App\Models\Ride;
use App\Models\RideRequest;
use App\Support\LocalTime;
use Illuminate\Support\Facades\DB;

/**
 * Tələb ilə təklifi bir-birinə bağlayan yer.
 *
 * İki istiqamətdə işləyir və hər ikisi bazarın eyni boşluğunu doldurur:
 *
 *  • sürücü elan verəndə → həmin marşrutu gözləyən sərnişinlərə xəbər gedir
 *    (`rideRequestMatched`). Sərnişin boş axtarışdan sonra çıxıb getmir,
 *    səfər yaranan gün geri çağırılır;
 *
 *  • sərnişin tələb elanı verəndə → o marşrutu əvvəllər sürmüş sürücülərə
 *    xəbər gedir (`rideRequestPosted`). Elan vermək qumar olmaqdan çıxır.
 *
 * Hər iki metod səssizdir: bildiriş göndərilməsə də əsas əməliyyat (elan
 * yaratmaq, tələb yazmaq) uğurlu sayılır.
 */
class RideMatchService
{
    /** Bir tələb elanı barədə eyni anda neçə sürücüyə yazılır. */
    private const DRIVER_FANOUT = 25;

    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Yeni (və ya yenilənmiş) səfər — onu gözləyənlərə xəbər ver.
     *
     * @return int  xəbər verilən sərnişin sayı
     */
    public function notifyWaitingPassengers(Ride $ride): int
    {
        if ($ride->status !== 'active' || $ride->departure_at->isPast()) {
            return 0;
        }

        // Tələb elanının tarixi Bakı günüdür; müqayisə də Bakı günü ilə.
        $date = LocalTime::toLocal($ride->departure_at)->toDateString();

        $requests = RideRequest::query()
            ->open()
            ->where('from_city_id', $ride->from_city_id)
            ->where('to_city_id', $ride->to_city_id)
            ->where('user_id', '!=', $ride->driver_id)
            ->whereRaw('? BETWEEN DATE_SUB(wanted_date, INTERVAL flexible_days DAY) AND DATE_ADD(wanted_date, INTERVAL flexible_days DAY)', [$date])
            ->where('seats', '<=', $ride->seats_left)
            // Gündə bir dəfədən çox yazmırıq: bir marşrutda beş elan verən
            // sürücü sərnişinin telefonunu beş dəfə oyadardı və sərnişin
            // bildirişləri tamamilə söndürərdi.
            ->where(fn ($q) => $q->whereNull('notified_at')->orWhere('notified_at', '<', now()->subDay()))
            ->limit(100)
            ->get();

        foreach ($requests as $request) {
            $this->notifications->send($request->user_id, 'rideRequestMatched', [
                'ride_id'  => $ride->id,
                'actor_id' => $ride->driver_id,
                'payload'  => [
                    'request_id'     => $request->id,
                    'price_per_seat' => (float) $ride->price_per_seat,
                    'departure_at'   => $ride->departure_at->toIso8601String(),
                ],
            ]);

            $request->forceFill([
                'matched_ride_id' => $ride->id,
                'notified_at'     => now(),
            ])->save();
        }

        return $requests->count();
    }

    /**
     * Yeni tələb elanı — bu marşrutu tanıyan sürücülərə xəbər ver.
     *
     * Hədəf qəsdən dardır: **həmin marşrutda əvvəllər səfər vermiş** sürücülər.
     * Şəhər üzrə və ya bütün sürücülərə göndərmək kütləvi reklam olardı və
     * bir həftəyə hamı bildirişləri söndürərdi. Marşrutu sürmüş adam üçün isə
     * bu, spam deyil — işidir.
     *
     * @return int  xəbər verilən sürücü sayı
     */
    public function notifyRouteDrivers(RideRequest $request): int
    {
        [$from, $to] = $request->dateRange();

        // Artıq uyğun aktiv elanı olan sürücüyə yazmırıq: sərnişin onu onsuz
        // da axtarışda görür və sürücünün görəcəyi yeni bir iş yoxdur.
        $alreadyOffering = Ride::query()
            ->active()
            ->where('from_city_id', $request->from_city_id)
            ->where('to_city_id', $request->to_city_id)
            ->whereBetween('departure_at', [$from, $to])
            ->pluck('driver_id');

        $drivers = Ride::query()
            ->where('from_city_id', $request->from_city_id)
            ->where('to_city_id', $request->to_city_id)
            ->where('driver_id', '!=', $request->user_id)
            ->whereNotIn('driver_id', $alreadyOffering)
            ->whereHas('driver', fn ($q) => $q->where('is_blocked', false))
            ->groupBy('driver_id')
            ->orderByRaw('MAX(departure_at) DESC')
            ->limit(self::DRIVER_FANOUT)
            ->pluck('driver_id');

        foreach ($drivers as $driverId) {
            $this->notifications->send($driverId, 'rideRequestPosted', [
                'actor_id' => $request->user_id,
                'payload'  => [
                    'request_id'   => $request->id,
                    'from_city'    => $request->fromCity?->name,
                    'to_city'      => $request->toCity?->name,
                    'wanted_date'  => $request->wanted_date->toDateString(),
                    'seats'        => $request->seats,
                ],
            ]);
        }

        return $drivers->count();
    }

    /**
     * Tələb elanına uyğun mövcud səfərlər.
     *
     * Elan yaradılan anda da işlədilir: sərnişin "tələbini yazdım" deyib
     * gözləməməlidir — həmin saniyə uyğun səfər varsa, ona göstərilir.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Ride>
     */
    public function matchingRides(RideRequest $request, int $limit = 20)
    {
        [$from, $to] = $request->dateRange();

        return Ride::query()
            ->active()
            ->upcoming()
            ->where('from_city_id', $request->from_city_id)
            ->where('to_city_id', $request->to_city_id)
            ->where('driver_id', '!=', $request->user_id)
            ->whereBetween('departure_at', [$from, $to])
            ->whereRaw('total_seats - booked_seats >= ?', [$request->seats])
            // `driver.driverProfile` — `RideController::RELATIONS` ilə eyni: bu
            // siyahı PRO-nu öndə sıralayır, nişanı da göstərməlidir.
            ->with(['driver.stats', 'driver.driverProfile', 'vehicle', 'fromCity', 'toCity'])
            ->boostedFirst()
            ->orderBy('departure_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Sərnişin bron edəndə onun həmin marşrutdakı tələb elanı bağlanır.
     *
     * Açıq qalsa, sürücülərin siyahısında artıq həll olunmuş tələb görünərdi
     * və növbəti sürücü boş yerə elan verərdi.
     */
    public function markFulfilled(int $userId, Ride $ride): void
    {
        RideRequest::query()
            ->where('user_id', $userId)
            ->where('from_city_id', $ride->from_city_id)
            ->where('to_city_id', $ride->to_city_id)
            ->open()
            ->whereRaw('? BETWEEN DATE_SUB(wanted_date, INTERVAL flexible_days DAY) AND DATE_ADD(wanted_date, INTERVAL flexible_days DAY)', [LocalTime::toLocal($ride->departure_at)->toDateString()])
            ->update([
                'status'          => 'fulfilled',
                'matched_ride_id' => $ride->id,
                'updated_at'      => now(),
            ]);
    }

    /**
     * Vaxtı keçmiş tələbləri bağlayır — `rides:expire-requests` əmri çağırır.
     *
     * @return int  bağlanan sətir sayı
     */
    public function expireOld(): int
    {
        return RideRequest::query()
            ->open()
            ->whereRaw('DATE_ADD(wanted_date, INTERVAL flexible_days DAY) < ?', [now()->toDateString()])
            ->update(['status' => 'expired', 'updated_at' => now()]);
    }

    /**
     * Marşrut üzrə açıq tələblərin sayı — sürücüyə göstərilən rəqəm.
     *
     * @return array{requests: int, seats: int}
     */
    public function openDemand(int $fromCityId, int $toCityId, ?string $date = null): array
    {
        $query = RideRequest::query()
            ->open()
            ->upcoming()
            ->where('from_city_id', $fromCityId)
            ->where('to_city_id', $toCityId);

        if ($date !== null) {
            $query->whereRaw('? BETWEEN DATE_SUB(wanted_date, INTERVAL flexible_days DAY) AND DATE_ADD(wanted_date, INTERVAL flexible_days DAY)', [$date]);
        }

        $row = $query->select(
            DB::raw('COUNT(*) as requests'),
            DB::raw('COALESCE(SUM(seats), 0) as seats')
        )->first();

        return [
            'requests' => (int) ($row->requests ?? 0),
            'seats'    => (int) ($row->seats ?? 0),
        ];
    }
}
