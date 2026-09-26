<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RideResource extends JsonResource
{
    public function toArray($request): array
    {
        $viewer   = $request->user();
        $isDriver = $viewer && $viewer->id === $this->driver_id;

        return [
            'id'              => $this->id,
            'driver'          => new UserBriefResource($this->whenLoaded('driver')),
            'vehicle'         => $this->whenLoaded(
                'vehicle',
                fn () => (new VehicleResource($this->vehicle))->withPlate($isDriver)
            ),
            'from_city'       => new CityResource($this->whenLoaded('fromCity')),
            'to_city'         => new CityResource($this->whenLoaded('toCity')),
            'departure_at'    => $this->departure_at?->toIso8601String(),
            'total_seats'     => $this->total_seats,
            'booked_seats'    => $this->booked_seats,
            'seats_left'      => $this->seats_left,
            'price_per_seat'  => (float) $this->price_per_seat,
            'status'          => $this->status,
            'note'            => $this->note,
            'pickup_point'    => $this->pickup_point,
            'dropoff_point'   => $this->dropoff_point,
            'instant_booking' => (bool) $this->instant_booking,

            // Yalnız qadın sərnişin qəbul edən səfər. Qadağanı server tətbiq
            // edir (`BookingController`), klient isə nişanı göstərir və
            // uyğun olmayan istifadəçiyə bron düyməsini açmır.
            'women_only' => (bool) $this->women_only,

            // Dəvət mükafatı ilə axtarışın başına çıxmış elan. Klientə göndərilir
            // ki, sürücü öz elanında mükafatın işlədiyini görsün.
            'is_boosted' => $this->is_boosted,

            // Paylaşma linki. Yol razılaşmaları Azərbaycanda hələ də WhatsApp
            // və Telegram qruplarında olur — sürücünün elanını ora ata bilməsi
            // ən ucuz müştəri kanalıdır.
            'share_url' => url('/r/'.$this->id),

            'is_mine'    => $isDriver,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
