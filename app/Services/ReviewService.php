<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\UserStat;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function create(Booking $booking, User $author, int $rating, ?string $comment): Review
    {
        $isDriver    = $booking->driver_id === $author->id;
        $isPassenger = $booking->passenger_id === $author->id;

        abort_unless($isDriver || $isPassenger, 403, 'Bu rezervasiya sənin deyil.');
        abort_unless($booking->status === 'completed', 422, 'Yalnız tamamlanmış səfərə rəy yazmaq olar.');

        $alreadyWrote = $isDriver ? $booking->driver_reviewed : $booking->passenger_reviewed;
        abort_if($alreadyWrote, 409, 'Bu səfərə artıq rəy yazmısan.');

        $targetId = $isDriver ? $booking->passenger_id : $booking->driver_id;

        return DB::transaction(function () use ($booking, $author, $rating, $comment, $isDriver, $targetId) {
            $review = Review::create([
                'booking_id'        => $booking->id,
                'ride_id'           => $booking->ride_id,
                'author_id'         => $author->id,
                'target_id'         => $targetId,
                'rating'            => $rating,
                'author_was_driver' => $isDriver,
                'comment'           => $comment,
            ]);

            $booking->update([
                $isDriver ? 'driver_reviewed' : 'passenger_reviewed' => true,
            ]);

            $this->recalculate($targetId, aboutDriver: ! $isDriver);

            $this->notifications->send($targetId, 'reviewRequest', [
                'ride_id'    => $booking->ride_id,
                'booking_id' => $booking->id,
                'actor_id'   => $author->id,
                'payload'    => ['rating' => $rating],
            ]);

            return $review;
        });
    }

    /**
     * Ortanı hər dəfə AVG ilə yenidən hesablayırıq — artımlı düstur
     * rəy silinəndə geri qaytarıla bilmir.
     *
     * Diqqət: author_was_driver MÜƏLLİFİN rolunu bildirir, hədəfin yox.
     * Hədəf sürücü kimi qiymətləndirilibsə, müəllif sərnişin olub.
     */
    public function recalculate(int $userId, bool $aboutDriver): void
    {
        $query = $aboutDriver
            ? Review::aboutDriver($userId)
            : Review::aboutPassenger($userId);

        $agg = $query->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')->first();

        $stats = UserStat::firstOrCreate(['user_id' => $userId]);

        if ($aboutDriver) {
            $stats->driver_rating       = round((float) $agg->avg_rating, 2);
            $stats->driver_review_count = (int) $agg->total;
        } else {
            $stats->passenger_rating       = round((float) $agg->avg_rating, 2);
            $stats->passenger_review_count = (int) $agg->total;
        }

        $stats->save();
    }
}
