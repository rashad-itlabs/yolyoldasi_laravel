<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** Sərnişinin tələb elanı — həm öz siyahısında, həm sürücünün gördüyü siyahıda. */
class RideRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        $viewer = $request->user();

        return [
            'id'            => $this->id,
            'passenger'     => new UserBriefResource($this->whenLoaded('user')),
            'from_city'     => new CityResource($this->whenLoaded('fromCity')),
            'to_city'       => new CityResource($this->whenLoaded('toCity')),
            'wanted_date'   => $this->wanted_date?->toDateString(),
            'flexible_days' => (int) $this->flexible_days,
            'seats'         => (int) $this->seats,
            'note'          => $this->note,
            'status'        => $this->status,
            'is_mine'       => $viewer !== null && $viewer->id === $this->user_id,

            // Sonuncu uyğun səfər — bildirişdən gələn sərnişin birbaşa ora keçir.
            'matched_ride_id' => $this->matched_ride_id,

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
