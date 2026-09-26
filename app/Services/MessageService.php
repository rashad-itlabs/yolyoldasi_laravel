<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessageService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Mesaj göndərmək üç yazı tələb edir və hamısı bir tranzaksiyada olmalıdır,
     * yoxsa söhbətlər siyahısı və oxunmamış sayğac həqiqətdən ayrılır.
     */
    public function send(Conversation $conversation, User $sender, string $text): Message
    {
        abort_if($conversation->is_locked, 422, 'Bu söhbət bağlanıb.');

        $recipientId = $this->recipientId($conversation, $sender);

        return DB::transaction(function () use ($conversation, $sender, $text, $recipientId) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $sender->id,
                'text'            => $text,
            ]);

            $conversation->update([
                'last_message'    => Str::limit($text, 200),
                'last_message_at' => $message->created_at,
                'last_sender_id'  => $sender->id,
            ]);

            ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', $recipientId)
                ->increment('unread_count');

            $this->notifications->send($recipientId, 'newMessage', [
                'ride_id'         => $conversation->ride_id,
                'booking_id'      => $conversation->booking_id,
                'conversation_id' => $conversation->id,
                'actor_id'        => $sender->id,
                'payload'         => ['preview' => Str::limit($text, 80)],
            ]);

            return $message;
        });
    }

    /** Oxundu kimi işarələ: sayğacı sıfırla, mesajlara read_at qoy. */
    public function markRead(Conversation $conversation, User $user): void
    {
        DB::transaction(function () use ($conversation, $user) {
            ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->update(['unread_count' => 0, 'last_read_at' => now()]);

            Message::where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        });
    }

    public function recipientId(Conversation $conversation, User $sender): int
    {
        $booking = $conversation->booking()->firstOrFail();

        abort_unless(
            in_array($sender->id, [$booking->passenger_id, $booking->driver_id], true),
            403,
            'Bu söhbət sənin deyil.'
        );

        return $sender->id === $booking->passenger_id
            ? $booking->driver_id
            : $booking->passenger_id;
    }
}
