<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** Yalnız istifadəçinin öz profili — telefon və şəxsi ayarlar daxildir. */
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'phone'              => $this->phone,
            'phone_verified_at'  => $this->phone_verified_at?->toIso8601String(),
            'full_name'          => $this->full_name,
            // Həmişə tam URL (API.md §1). Köhnə yükləmələr bazada nisbi yolla
            // (`/storage/avatars/…`) qalıb və tətbiq onu yükləyə bilmirdi —
            // `url()` nisbi yolu domenlə tamamlayır, tam URL-ə toxunmur.
            'photo_url'          => $this->photo_url ? url($this->photo_url) : null,
            'about'              => $this->about,
            'gender'             => $this->gender,
            'birth_year'         => $this->birth_year,
            'city'               => new CityResource($this->whenLoaded('city')),
            'active_mode'        => $this->active_mode,
            'has_driver_profile' => (bool) $this->has_driver_profile,
            'language_code'      => $this->language_code,
            'is_admin'           => (bool) $this->is_admin,

            // Dəvət kodu burada da qayıdır ki, "Dostunu dəvət et" ekranı
            // açılan kimi kodu göstərə bilsin — `GET /me/referral` yalnız
            // saylar və mükafat müddəti üçün lazımdır.
            'referral_code' => $this->referral_code,

            'stats' => $this->whenLoaded('stats', fn () => [
                'driver_rating'          => (float) $this->stats->driver_rating,
                'driver_review_count'    => $this->stats->driver_review_count,
                'driver_trip_count'      => $this->stats->driver_trip_count,
                'passenger_rating'       => (float) $this->stats->passenger_rating,
                'passenger_review_count' => $this->stats->passenger_review_count,
                'passenger_trip_count'   => $this->stats->passenger_trip_count,

                // `UserBriefResource` ilə eyni dəst: klientdə tək `UserStats`
                // modeli hər iki cavabı oxuyur, sahə siyahıları ayrılsa
                // profil ekranı ilə axtarış nəticəsi fərqli davranardı.
                'driver_response_rate'    => $this->stats->driver_response_rate,
                'driver_response_minutes' => $this->stats->driver_response_minutes,
                'driver_tier'             => $this->stats->driver_tier,
            ]),
            'notification_preferences' => $this->whenLoaded('notificationPreference'),
        ];
    }
}
