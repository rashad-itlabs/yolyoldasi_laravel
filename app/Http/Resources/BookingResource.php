<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        $viewer = $request->user();

        // Təsdiqlənmiş rezervasiyada tərəflər bir-birinin nömrəsini görür.
        $contactVisible = in_array($this->status, ['confirmed', 'completed'], true)
            && $viewer
            && in_array($viewer->id, [$this->passenger_id, $this->driver_id], true);

        return [
            'id'                  => $this->id,
            'ride'                => new RideResource($this->whenLoaded('ride')),
            'passenger'           => new UserBriefResource($this->whenLoaded('passenger')),
            'driver'              => new UserBriefResource($this->whenLoaded('driver')),
            'seats'               => $this->seats,
            'total_price'         => (float) $this->total_price,
            'status'              => $this->status,
            'message'             => $this->message,
            'decided_at'          => $this->decided_at?->toIso8601String(),
            'cancelled_at'        => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'passenger_reviewed'  => (bool) $this->passenger_reviewed,
            'driver_reviewed'     => (bool) $this->driver_reviewed,
            'conversation_id'     => $this->whenLoaded('conversation', fn () => $this->conversation?->id),
            'contact_phone'       => $this->when($contactVisible, function () use ($viewer) {
                return $viewer->id === $this->passenger_id
                    ? $this->driver?->phone
                    : $this->passenger?->phone;
            }),
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
