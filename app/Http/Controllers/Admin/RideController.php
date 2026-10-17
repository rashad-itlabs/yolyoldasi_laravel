<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Booking;
use App\Models\City;
use App\Models\Conversation;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Ride;
use App\Models\UserStat;
use App\Services\NotificationService;
use App\Services\ReviewService;
use App\Support\LocalTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RideController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private ReviewService $reviews,
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'status', 'from_city_id', 'to_city_id', 'date_from', 'date_to']);

        $rides = Ride::query()
            ->with(['driver', 'fromCity', 'toCity'])
            ->withCount(['bookings', 'openBookings'])
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(
                fn ($inner) => $inner->where('id', $q)
                    ->orWhereHas('driver', fn ($d) => $d->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%"))
            ))
            ->when($filters['status'] ?? null, fn ($query, $s) => $query->where('status', $s))
            ->when($filters['from_city_id'] ?? null, fn ($query, $c) => $query->where('from_city_id', $c))
            ->when($filters['to_city_id'] ?? null, fn ($query, $c) => $query->where('to_city_id', $c))
            // Admin tarixi Bakı günü kimi seçir (bax `LocalTime::dayRange`).
            ->when($filters['date_from'] ?? null, fn ($query, $d) => $query->where('departure_at', '>=', LocalTime::dayRange($d)[0]))
            ->when($filters['date_to'] ?? null, fn ($query, $d) => $query->where('departure_at', '<=', LocalTime::dayRange($d)[1]))
            ->latest('departure_at')
            ->paginate(25)
            ->withQueryString();

        $cities = City::orderBy('name')->get();

        $counts = Ride::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return view('admin.rides.index', compact('rides', 'filters', 'cities', 'counts'));
    }

    public function show(Ride $ride)
    {
        $ride->load(['driver.stats', 'vehicle', 'fromCity', 'toCity'])->loadCount('openBookings');

        $bookings = $ride->bookings()->with('passenger')->latest()->get();

        return view('admin.rides.show', compact('ride', 'bookings'));
    }

    /**
     * Admin səfəri ləğv edir — açıq rezervasiyalar da bağlanmalıdır, yoxsa
     * sərnişinlər mövcud olmayan səfəri gözləyər.
     */
    public function cancel(Request $request, Ride $ride)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        abort_if(
            in_array($ride->status, ['cancelled', 'completed'], true),
            422,
            'Bu səfər artıq bağlanıb.'
        );

        DB::transaction(function () use ($ride, $data) {
            $bookings = Booking::where('ride_id', $ride->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->get();

            foreach ($bookings as $booking) {
                $booking->update([
                    'status'              => 'cancelled_by_driver',
                    'cancelled_at'        => now(),
                    'cancellation_reason' => $data['reason'],
                ]);

                Conversation::where('booking_id', $booking->id)->update(['is_locked' => true]);

                $this->notifications->send($booking->passenger_id, 'rideCancelled', [
                    'ride_id'    => $ride->id,
                    'booking_id' => $booking->id,
                    'payload'    => ['reason' => $data['reason'], 'by' => 'admin'],
                ]);
            }

            $this->notifications->send($ride->driver_id, 'rideCancelled', [
                'ride_id' => $ride->id,
                'payload' => ['reason' => $data['reason'], 'by' => 'admin'],
            ]);

            $ride->update(['status' => 'cancelled', 'booked_seats' => 0]);

            AdminActivityLog::record('ride.cancelled', $ride, [
                'reason'             => $data['reason'],
                'driver_id'          => $ride->driver_id,
                'affected_bookings'  => $bookings->count(),
            ]);
        });

        return back()->with('success', "Səfər #{$ride->id} ləğv edildi.");
    }

    /** Siyahıdan gizlətmək üçün — səfər silinmir, passiv olur. */
    public function deactivate(Ride $ride)
    {
        abort_unless($ride->status === 'active', 422, 'Yalnız aktiv səfər passivləşdirilə bilər.');

        DB::transaction(function () use ($ride) {
            $ride->update(['status' => 'inactive']);
            AdminActivityLog::record('ride.deactivated', $ride, ['driver_id' => $ride->driver_id]);
        });

        return back()->with('success', "Səfər #{$ride->id} passivləşdirildi.");
    }

    public function activate(Ride $ride)
    {
        abort_unless($ride->status === 'inactive', 422, 'Yalnız passiv səfər aktivləşdirilə bilər.');

        DB::transaction(function () use ($ride) {
            $ride->update(['status' => 'active']);
            AdminActivityLog::record('ride.activated', $ride, ['driver_id' => $ride->driver_id]);
        });

        return back()->with('success', "Səfər #{$ride->id} aktivləşdirildi.");
    }

    /**
     * Səfəri bazadan tamamilə silir. Rezervasiyalar, söhbətlər, mesajlar və
     * rəylər FK kaskadı ilə gedir. Bildirişlərdə FK `nullOnDelete`-dir —
     * onları əl ilə silirik, yoxsa lentdə heç yerə aparmayan qeydlər qalar.
     * Şikayətlər qəsdən saxlanılır: moderasiya tarixçəsidir.
     */
    public function destroy(Ride $ride)
    {
        if ($ride->mustBeCancelledBeforeDelete()) {
            return back()->withErrors([
                'ride' => "Səfər #{$ride->id}-də açıq rezervasiya var — əvvəlcə səfəri ləğv edin, sonra silin.",
            ]);
        }

        // Səfərin öz səhifəsindən silinibsə, geri qayıdacaq yer qalmır.
        $redirect = parse_url(url()->previous(), PHP_URL_PATH) === route('admin.rides.show', $ride, false)
            ? redirect()->route('admin.rides.index')
            : back();

        DB::transaction(function () use ($ride) {
            $bookings = Booking::where('ride_id', $ride->id)->get(['id', 'passenger_id', 'status']);
            $reviews  = Review::where('ride_id', $ride->id)->get(['target_id', 'author_was_driver']);

            AdminActivityLog::record('ride.deleted', $ride, [
                'driver_id'    => $ride->driver_id,
                'route'        => $ride->fromCity?->name.' → '.$ride->toCity?->name,
                'departure_at' => $ride->departure_at->toDateTimeString(),
                'status'       => $ride->status,
                'bookings'     => $bookings->count(),
                'reviews'      => $reviews->count(),
            ]);

            Notification::where('ride_id', $ride->id)
                ->orWhereIn('booking_id', $bookings->pluck('id'))
                ->delete();

            $ride->delete();

            // `completeRide`-in artırdığı səfər sayğaclarını geri alırıq —
            // silinmiş səfər sürücünün pilləsinə hesablanmamalıdır.
            $completed = $bookings->where('status', 'completed');

            foreach ($completed as $booking) {
                UserStat::where('user_id', $booking->passenger_id)
                    ->where('passenger_trip_count', '>', 0)
                    ->decrement('passenger_trip_count');
            }

            if ($completed->isNotEmpty()) {
                UserStat::where('user_id', $ride->driver_id)
                    ->where('driver_trip_count', '>', 0)
                    ->decrement('driver_trip_count');
            }

            // Rəylər kaskadla getdi — hər hədəfin ortalaması yenidən hesablanır.
            $reviews->unique(fn ($r) => $r->target_id.':'.(int) $r->author_was_driver)
                ->each(fn ($r) => $this->reviews->recalculate($r->target_id, aboutDriver: ! $r->author_was_driver));
        });

        return $redirect->with('success', "Səfər #{$ride->id} tamamilə silindi.");
    }
}
