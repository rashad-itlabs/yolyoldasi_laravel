<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * E-poçta gedən birdəfəlik kod — sayt qeydiyyatı (`register`) və şifrə
 * bərpası (`reset`).
 *
 * Qaydalar tətbiqin SMS kodu ilə eynidir (`Api\AuthController`): 6 rəqəm,
 * hash-lənmiş saxlanılır, 5 səhv cəhddən sonra yanır. Müddət isə 10 dəqiqədir,
 * 5 yox — məktub SMS-dən gec çatır və bəzən spam qovluğundan çıxarılır.
 */
class EmailCode extends Model
{
    public const UPDATED_AT = null;

    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /** Eyni ünvana iki kod arasında ən az bu qədər saniyə. */
    public const RESEND_AFTER = 60;

    protected $fillable = ['email', 'purpose', 'code_hash', 'payload', 'expires_at'];

    protected $casts = [
        // Qeydiyyatda ad və şifrənin hash-i burada gözləyir — bazanı oxuyan
        // adam da onları görməsin deyə şifrələnir.
        'payload'    => 'encrypted:array',
        'expires_at' => 'datetime',
    ];

    /**
     * Yeni kod yaradır və açıq mətnini qaytarır. Həmin ünvan və məqsəd üçün
     * əvvəlki kod silinir — işləyən yalnız sonuncu məktubdakıdır.
     */
    public static function issue(string $email, string $purpose, ?array $payload = null): string
    {
        $code = (string) random_int(100000, 999999);

        static::where('email', $email)->where('purpose', $purpose)->delete();

        static::create([
            'email'      => $email,
            'purpose'    => $purpose,
            'code_hash'  => Hash::make($code),
            'payload'    => $payload,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return $code;
    }

    /** Hələ etibarlı olan sonuncu kod, varsa. */
    public static function current(string $email, string $purpose): ?self
    {
        return static::where('email', $email)
            ->where('purpose', $purpose)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    /** Yenidən göndərmək üçün neçə saniyə gözləmək lazımdır (0 = olar). */
    public static function cooldown(string $email, string $purpose): int
    {
        $last = static::where('email', $email)->where('purpose', $purpose)->latest('id')->first();

        if (! $last?->created_at) {
            return 0;
        }

        return max(0, self::RESEND_AFTER - (int) $last->created_at->diffInSeconds(now()));
    }

    /**
     * Kodu yoxlayır. Uğurlu olanda qeydi qaytarır (çağıran onu silir), səhv
     * olanda cəhdi sayır və null qaytarır.
     */
    public static function verify(string $email, string $purpose, string $code): ?self
    {
        $record = static::current($email, $purpose);

        if (! $record || $record->attempts >= self::MAX_ATTEMPTS) {
            return null;
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');

            return null;
        }

        return $record;
    }
}
