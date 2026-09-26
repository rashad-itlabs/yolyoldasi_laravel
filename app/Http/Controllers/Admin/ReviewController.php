<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviews)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'rating', 'role']);

        $reviews = Review::query()
            ->with(['author', 'target', 'booking'])
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(
                fn ($inner) => $inner->where('comment', 'like', "%{$q}%")
                    ->orWhereHas('target', fn ($u) => $u->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%"))
                    ->orWhereHas('author', fn ($u) => $u->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%"))
            ))
            ->when($filters['rating'] ?? null, fn ($query, $r) => $query->where('rating', $r))
            ->when(
                isset($filters['role']) && $filters['role'] !== '',
                fn ($query) => $query->where('author_was_driver', $filters['role'] === 'driver')
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'filters'));
    }

    /**
     * Rəy silinəndə hədəfin ortalaması yenidən hesablanmalıdır, yoxsa
     * reytinq silinmiş rəyi əks etdirməyə davam edir.
     *
     * Bookings-dəki `*_reviewed` bayrağı qəsdən toxunulmur — əks halda
     * silinən təhqiramiz rəy yenidən yazıla bilər.
     */
    public function destroy(Review $review)
    {
        DB::transaction(function () use ($review) {
            $targetId    = $review->target_id;
            $aboutDriver = ! $review->author_was_driver;

            AdminActivityLog::record('review.deleted', $review, [
                'target_id' => $targetId,
                'author_id' => $review->author_id,
                'rating'    => $review->rating,
                'comment'   => $review->comment,
            ]);

            $review->delete();

            $this->reviews->recalculate($targetId, aboutDriver: $aboutDriver);
        });

        return back()->with('success', 'Rəy silindi və reytinq yeniləndi.');
    }
}
