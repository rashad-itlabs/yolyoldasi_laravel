<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'booking_id'      => $this->booking_id,
            'ride_id'         => $this->ride_id,
            'last_message'    => $this->last_message,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'last_sender_id'  => $this->last_sender_id,
            'is_locked'       => (bool) $this->is_locked,
            // Qarşı tərəf və oxunmamış sayı controller tərəfindən doldurulur.
            'other_user'      => $this->other_user ? new UserBriefResource($this->other_user) : null,
            'unread_count'    => (int) ($this->unread_count ?? 0),
        ];
    }
}
