<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Ride;
use App\Services\NotificationService;
use App\Services\RideMatchService;
use Illuminate\Console\Command;

/**
 * Gündəlik təmizlik və xatırlatma.
 *
 * Üç iş görür və üçü də bir əmrdə saxlanılır ki, serverdə bir cron sətri
 * kifayət etsin:
 *
 *   1. vaxtı keçmiş tələb elanlarını bağlayır — açıq qalsa, sürücülərin
 *      siyahısı keçmiş tarixlərlə dolardı;
 *   2. sabahkı səfərlər üçün `rideReminder` göndərir — enum-da növ vardı,
 *      amma onu yaradan heç nə yox idi;
 *   3. yola düşmüş, amma tamamlanmamış elanları sürücüyə xatırladır, çünki
 *      `complete` çağırılmayan səfər nə rəy tələbi yaradır, nə də sayğac.
 *
 * Cron (server):
 *
 *     0 9 * * *  cd /var/www/... && php artisan demand:tidy >> /dev/null 2>&1
 */
class CloseStaleDemand extends Command
{
    protected $signature = 'demand:tidy';

    protected $description = 'Vaxtı keçmiş tələb elanlarını bağlayır və səfər xatırlatmalarını göndərir';

    public function handle(RideMatchService $matcher, NotificationService $notifications): int
    {
        $expired = $matcher->expireOld();
        $this->info("Bağlanan tələb elanı: {$expired}");

        $reminded = $this->sendDepartureReminders($notifications);
        $this->info("Göndərilən xatırlatma: {$reminded}");

        return self::SUCCESS;
    }

    /**
     * Sabah yola düşən səfərlər üçün hər iki tərəfə xatırlatma.
     *
     * Təkrarın qarşısını `notifications` cədvəlinin özü alır: eyni səfər üçün
     * eyni istifadəçiyə `rideReminder` sətri varsa, ikincisi yazılmır. Ayrıca
     * "göndərildi" sütunu əlavə etməkdənsə mövcud cədvəli soruşmaq daha
     * etibarlıdır — sütun və sətir bir-birindən ayrı düşə bilməz.
     */
    private function sendDepartureReminders(NotificationService $notifications): int
    {
        $rides = Ride::query()
            ->active()
            ->whereBetween('departure_at', [now(), now()->addDay()])
            ->with('bookings')
            ->get();

        $sent = 0;

        foreach ($rides as $ride) {
            $recipients = $ride->bookings
                ->where('status', 'confirmed')
                ->pluck('passenger_id')
                ->push($ride->driver_id)
                ->unique();

            foreach ($recipients as $userId) {
                $already = Notification::where('user_id', $userId)
                    ->where('ride_id', $ride->id)
                    ->where('type', 'rideReminder')
                    ->exists();

                if ($already) {
                    continue;
                }

                $notifications->send($userId, 'rideReminder', [
                    'ride_id' => $ride->id,
                    'payload' => [
                        'departure_at' => $ride->departure_at->toIso8601String(),
                    ],
                ]);

                $sent++;
            }
        }

        return $sent;
    }
}
