<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** Elanlarda və rezervasiyalarda görünən ictimai profil — telefon daxil deyil. */
class UserBriefResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'full_name'          => $this->full_name,
            'photo_url'          => $this->photo_url,
            'gender'             => $this->gender,
            'birth_year'         => $this->birth_year,
            'has_driver_profile' => (bool) $this->has_driver_profile,

            // Sənədləri təsdiqlənmiş sürücü. İndiyə qədər bu nişan yalnız
            // istifadəçinin öz profilində görünürdü — axtarış nəticəsində
            // görünməyən nişan sürücü üçün sənəd yükləməyi mənasız edirdi.
            //
            // Əlaqə yüklənməyibsə açar cavabda **ümumiyyətlə olmur**, `false`
            // yox: "bilinmir" ilə "təsdiqlənməyib" fərqli şeylərdir və
            // ikincisini səhvən göstərmək təsdiqlənmiş sürücünü cəzalandırardı
            // (API.md §16.5 — absent açarlar).
            'is_verified' => $this->whenLoaded(
                'driverProfile',
                fn () => $this->driverProfile?->status === 'approved'
            ),

            'stats' => $this->whenLoaded('stats', fn () => [
                'driver_rating'          => (float) $this->stats->driver_rating,
                'driver_review_count'    => $this->stats->driver_review_count,
                'driver_trip_count'      => $this->stats->driver_trip_count,
                'passenger_rating'       => (float) $this->stats->passenger_rating,
                'passenger_review_count' => $this->stats->passenger_review_count,
                'passenger_trip_count'   => $this->stats->passenger_trip_count,

                // Sərnişin üçün ən vacib naməlum: müraciət göndərsəm cavab
                // gələcəkmi və nə vaxt. Az sayda sorğuda `null` qayıdır —
                // bir sorğuya cavab vermiş sürücünün "100%" görünməsi yalan
                // siqnaldır (bax `UserStat::getDriverResponseRateAttribute`).
                'driver_response_rate'    => $this->stats->driver_response_rate,
                'driver_response_minutes' => $this->stats->driver_response_minutes,

                // new | rising | trusted — reytinq və səfər sayının birlikdə
                // oxunan qarşılığı.
                'driver_tier' => $this->stats->driver_tier,
            ]),
        ];
    }
}
