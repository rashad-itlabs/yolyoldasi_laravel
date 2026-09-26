<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Telefon təsdiq kodu. Cədvəldə `updated_at` yoxdur — kod yaranandan sonra
 * yalnız `attempts` və `consumed_at` dəyişir, ona görə UPDATED_AT söndürülüb.
 */
class PhoneVerificationCode extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'phone',
        'code_hash',
        'attempts',
        'expires_at',
        'consumed_at',
        'ip',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'consumed_at' => 'datetime',
    ];

    protected $hidden = [
        'code_hash',
    ];
}
