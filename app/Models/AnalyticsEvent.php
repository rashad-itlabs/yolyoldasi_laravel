<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bir məhsul hadisəsi.
 *
 * `updated_at` yoxdur: hadisə yazıldıqdan sonra heç vaxt dəyişmir.
 */
class AnalyticsEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'anonymous_id', 'name', 'params',
        'platform', 'app_version', 'occurred_at',
    ];

    protected $casts = [
        'params'      => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * Tətbiqin göndərə biləcəyi hadisə adları.
     *
     * Ağ siyahı qəsdən var: sərbəst ad qəbul etsək, tətbiqin bir versiyasında
     * `search_empty`, digərində `searchEmpty` yazılar və funnel iki yerə
     * bölünərdi. Siyahıda olmayan ad sakitcə atılır — telemetriya heç vaxt
     * istifadəçinin işini dayandırmamalıdır.
     */
    public const NAMES = [
        // Funnel: tətbiqi açmaqdan ilk brona qədər
        'app_open',
        'onboarding_done',
        'signin_started',
        'signin_completed',
        'profile_completed',

        // Axtarış
        'search_performed',
        'search_empty',
        'ride_viewed',

        // Tələb elanı
        'ride_request_created',
        'ride_request_opened',

        // Bron
        'booking_requested',
        'booking_confirmed',
        'booking_cancelled',

        // Sürücü tərəf
        'publish_started',
        'publish_completed',
        'ride_repeated',
        'vehicle_added',
        'documents_uploaded',

        // Böyümə
        'ride_shared',
        'mode_switched',
        'referral_shared',
        'review_submitted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
