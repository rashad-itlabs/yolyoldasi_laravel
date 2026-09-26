<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bir mobil platformanın versiya qaydası. Açar `platform` sütunudur, ona görə
 * cədvəldə həmişə ən çoxu iki sətir olur.
 */
class AppVersion extends Model
{
    public const CREATED_AT = null;

    /** Tətbiqin göndərə biləcəyi platformalar. */
    public const PLATFORMS = ['android', 'ios'];

    /** Cavabdakı `status` sahəsinin ala biləcəyi dəyərlər. */
    public const STATUS_OK = 'ok';
    public const STATUS_OPTIONAL = 'optional';
    public const STATUS_REQUIRED = 'required';

    protected $primaryKey = 'platform';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'platform',
        'min_build',
        'min_version',
        'latest_build',
        'latest_version',
        'store_url',
        'message_az',
        'message_ru',
        'message_en',
        'is_enabled',
    ];

    protected $casts = [
        'min_build'    => 'integer',
        'latest_build' => 'integer',
        'is_enabled'   => 'boolean',
    ];

    /**
     * Verilmiş build üçün qərar.
     *
     * Müqayisə build nömrəsi ilədir: o, hər buraxılışda mütləq artır, halbuki
     * `1.0.10` ilə `1.0.9`-u sətir kimi müqayisə etmək səhv nəticə verir.
     */
    public function statusFor(int $build): string
    {
        if (! $this->is_enabled || $build < 1) {
            return self::STATUS_OK;
        }

        if ($build < $this->min_build) {
            return self::STATUS_REQUIRED;
        }

        if ($build < $this->latest_build) {
            return self::STATUS_OPTIONAL;
        }

        return self::STATUS_OK;
    }

    /** İstənilən dildəki mətn; tərcümə yoxdursa Azərbaycan dilinə düşür. */
    public function message(string $lang): ?string
    {
        $value = $this->{'message_'.$lang} ?? null;

        return ($value === null || $value === '') ? ($this->message_az ?: null) : $value;
    }
}
