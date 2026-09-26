<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Ride;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    /** Sərnişinin öz rezervasiyaları. */
    public function index(Request $request)
    {
        $bookings = $request->user()->bookings()
            ->with(['ride.fromCity', 'ride.toCity', 'ride.vehicle', 'driver.stats', 'driver.driverProfile', 'conversation'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        return BookingResource::collection($bookings);
    }

    /** Sürücüyə gələn rezervasiyalar. */
    public function incoming(Request $request)
    {
        $bookings = $request->user()->incomingBookings()
            ->with(['ride.fromCity', 'ride.toCity', 'passenger.stats', 'conversation'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        return BookingResource::collection($bookings);
    }

    public function show(Request $request, Booking $booking)
    {
        $this->authorizeParty($request, $booking);

        return new BookingResource(
            $booking->load(['ride.fromCity', 'ride.toCity', 'ride.vehicle', 'passenger.stats', 'driver.stats', 'driver.driverProfile', 'conversation'])
        );
    }

    public function store(Request $request, Ride $ride)
    {
        $data = $request->validate([
            'seats'   => ['required', 'integer', 'min:1', 'max:4'],
            'message' => ['nullable', 'string', 'max:400'],
        ]);

        $booking = $this->bookings->request(
            $ride,
            $request->user(),
            $data['seats'],
            $data['message'] ?? null
        );

        return (new BookingResource($booking->load(['ride', 'driver.stats', 'driver.driverProfile', 'conversation'])))
            ->response()
            ->setStatusCode(201);
    }

    public function confirm(Request $request, Booking $booking)
    {
        return new BookingResource($this->bookings->confirm($booking, $request->user()));
    }

    public function reject(Request $request, Booking $booking)
    {
        return new BookingResource($this->bookings->reject($booking, $request->user()));
    }

    public function cancel(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        return new BookingResource(
            $this->bookings->cancel($booking, $request->user(), $data['reason'] ?? null)
        );
    }

    private function authorizeParty(Request $request, Booking $booking): void
    {
        abort_unless(
            in_array($request->user()->id, [$booking->passenger_id, $booking->driver_id], true),
            403,
            'Bu rezervasiya sənin deyil.'
        );
    }
}
