<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\UserBriefResource;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Booking $booking, ReviewService $reviews)
    {
        $data = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $review = $reviews->create(
            $booking,
            $request->user(),
            $data['rating'],
            $data['comment'] ?? null
        );

        return (new ReviewResource($review->load('author.stats')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * İstifadəçinin aldığı rəylər.
     * ?role=driver → onun sürücü kimi aldıqları (müəllif sərnişin olub).
     */
    public function index(Request $request, User $user)
    {
        $request->validate(['role' => ['nullable', 'in:driver,passenger']]);

        $query = Review::query()->with('author.stats');

        $query = match ($request->query('role')) {
            'driver'    => $query->aboutDriver($user->id),
            'passenger' => $query->aboutPassenger($user->id),
            default     => $query->where('target_id', $user->id),
        };

        return ReviewResource::collection($query->latest()->paginate(20));
    }

    /** İctimai profil — rəy siyahısının başlığında göstərmək üçün. */
    public function profile(User $user)
    {
        abort_if($user->is_blocked, 404);

        return new UserBriefResource($user->load('stats'));
    }
}
