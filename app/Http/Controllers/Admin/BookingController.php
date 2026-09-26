<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public const STATUS_LABELS = [
        'pending'                => 'Gözləyir',
        'confirmed'              => 'Təsdiqlənib',
        'rejected'               => 'Rədd edilib',
        'cancelled_by_passenger' => 'Sərnişin ləğv edib',
        'cancelled_by_driver'    => 'Sürücü ləğv edib',
        'completed'              => 'Tamamlanıb',
    ];

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'status']);

        $bookings = Booking::query()
            ->with(['passenger', 'driver', 'ride.fromCity', 'ride.toCity'])
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(
                fn ($inner) => $inner->where('id', $q)
                    ->orWhere('ride_id', $q)
                    ->orWhereHas('passenger', fn ($u) => $u->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%"))
                    ->orWhereHas('driver', fn ($u) => $u->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%"))
            ))
            ->when($filters['status'] ?? null, fn ($query, $s) => $query->where('status', $s))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $counts = Booking::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return view('admin.bookings.index', compact('bookings', 'filters', 'counts'));
    }

    public function show(Booking $booking)
    {
        $booking->load([
            'passenger', 'driver', 'ride.fromCity', 'ride.toCity', 'ride.vehicle',
            'reviews.author', 'conversation',
        ]);

        return view('admin.bookings.show', compact('booking'));
    }
}
