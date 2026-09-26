<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function __construct(private OneSignalService $push)
    {
    }

    /**
     * Bildiriş sətrini yaradır və push göndərir.
     *
     * Sətir hər halda yazılır: push getməsə də (icazə yoxdur, OneSignal
     * əlçatmazdır, istifadəçi susdurub) bildiriş tətbiqin siyahısında
     * görünməlidir. Buna görə `send()` heç vaxt istisna atmır və nəticəsi
     * yoxlanmır — push mesajın özündən vacib deyil.
     *
     * `afterCommit` iki səbəbə görə vacibdir. `MessageService::send()` hər şeyi
     * bir tranzaksiyaya salır: birincisi, HTTP sorğusu içəridə qalsa,
     * `conversation_participants` sətrinin kilidi OneSignal cavab verənə qədər
     * — beş saniyəyə qədər — açılmır. İkincisi, tranzaksiya sonra geri
     * qayıtsa, istifadəçi mövcud olmayan mesaj barədə bildiriş almış olardı.
     * Tranzaksiya yoxdursa Laravel funksiyanı elə həmin an işlədir, yəni
     * qalan çağıranlar üçün heç nə dəyişmir.
     */
    public function send(User|int $user, string $type, array $attributes = []): Notification
    {
        $notification = Notification::create(array_merge([
            'user_id' => $user instanceof User ? $user->id : $user,
            'type'    => $type,
        ], $attributes));

        DB::afterCommit(fn () => $this->push->send($notification));

        return $notification;
    }
}
