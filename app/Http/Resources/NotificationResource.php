<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'ride_id'         => $this->ride_id,
            'booking_id'      => $this->booking_id,
            'conversation_id' => $this->conversation_id,
            'actor'           => new UserBriefResource($this->whenLoaded('actor')),
            'payload'         => $this->payload,
            'read_at'         => $this->read_at?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
