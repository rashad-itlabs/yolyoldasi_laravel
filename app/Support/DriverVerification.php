<?php

namespace App\Support;

use App\Models\DriverProfile;
use App\Models\VerificationDocument;

/**
 * Sürücünün ümumi statusu sənədlərdən törəyir.
 *
 * Qayda iki yerdən çağırılır — sürücü sənəd yükləyəndə (API) və admin sənədi
 * təsdiqləyəndə (panel). İki nüsxə saxlansaydı, biri dəyişəndə statuslar
 * bir-birindən ayrılardı, ona görə tək mənbə buradır.
 */
class DriverVerification
{
    public static function statusFor(DriverProfile $profile): string
    {
        $statuses = VerificationDocument::where('driver_profile_id', $profile->id)
            ->pluck('status', 'type');

        $all = collect(VerificationDocument::TYPES);

        return match (true) {
            $all->every(fn ($t) => ($statuses[$t] ?? null) === 'approved')    => 'approved',
            $all->contains(fn ($t) => ($statuses[$t] ?? null) === 'rejected') => 'rejected',
            $all->every(fn ($t) => isset($statuses[$t]))                      => 'pending',
            default                                                           => 'not_uploaded',
        };
    }

    /** Statusu yenidən hesablayıb yazır və yeni statusu qaytarır. */
    public static function refresh(DriverProfile $profile): string
    {
        $status = static::statusFor($profile);

        $profile->forceFill([
            'status'       => $status,
            'submitted_at' => $status === 'pending'
                ? ($profile->submitted_at ?? now())
                : $profile->submitted_at,
        ])->save();

        return $status;
    }
}
