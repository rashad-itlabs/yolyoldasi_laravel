<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Dəvət sistemi.
 *
 * Pul mükafatı yoxdur və qəsdən yoxdur: ödəniş qatı olmayan bir məhsulda
 * nağd mükafat həm saxta hesab axını yaradar, həm də dayandırılan gün
 * istifadəçini incidər. Sürücünün əsl ehtiyacı **görünürlükdür**, ona görə
 * mükafat da odur — elanları müəyyən müddət axtarışın başında çıxır.
 *
 * Mükafat qeydiyyatda yox, dəvət olunanın **ilk real səfərində** verilir.
 * Bu fərq bütün sistemi saxtakarlıqdan qoruyur: on nömrə ilə qeydiyyatdan
 * keçmək asandır, on dəfə səfər edib rəy almaq isə yox.
 */
class ReferralService
{
    /** Bir uğurlu dəvətə verilən boost (gün). */
    private const REWARD_DAYS = 7;

    /** Yığıla bilən maksimum boost (gün) — bir nəfər axtarışı tamamilə tutmasın. */
    private const MAX_DAYS = 60;

    /**
     * Qeydiyyat anında dəvət kodunu bağlayır.
     *
     * Yalnız **yeni** hesab üçün işləyir: mövcud istifadəçi sonradan kod
     * yazıb kiməsə mükafat qazandıra bilməz, əks halda kodlar Telegram
     * qruplarında bir-birinə "geriyə doğru" yazılardı.
     */
    public function attach(User $user, ?string $code): void
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '' || $user->referred_by !== null) {
            return;
        }

        $referrer = User::where('referral_code', $code)->first();

        // Öz kodunu yazmaq — ən sadə sui-istifadə yolu.
        if (! $referrer || $referrer->id === $user->id) {
            return;
        }

        $user->forceFill([
            'referred_by' => $referrer->id,
            'referred_at' => now(),
        ])->save();
    }

    /**
     * Dəvət olunan ilk real hərəkətini etdi — dəvət edənə mükafat.
     *
     * Həm elan verməkdən (sürücü), həm də bron etməkdən (sərnişin) çağırılır:
     * hansı tərəfə keçməsindən asılı olmayaraq bazara dəyər gətirib.
     *
     * Bir hesab üçün bir dəfə işləyir — `referral_rewarded_at` buna nəzarət
     * edir. Şərt `whereNull` ilə **sorğunun içindədir**: iki paralel sorğu
     * eyni anda gəlsə, PHP tərəfdəki `if` ikisini də buraxardı və mükafat iki
     * dəfə verilərdi.
     */
    public function reward(User $user): void
    {
        if ($user->referred_by === null || $user->referral_rewarded_at !== null) {
            return;
        }

        DB::transaction(function () use ($user) {
            $claimed = User::whereKey($user->id)
                ->whereNull('referral_rewarded_at')
                ->update(['referral_rewarded_at' => now()]);

            if ($claimed === 0) {
                return;
            }

            $referrer = User::find($user->referred_by);
            if (! $referrer) {
                return;
            }

            $profile = $referrer->driverProfile()->firstOrCreate([]);

            // Qalan boost-un üstünə gəlinir, sıfırdan başlamır: ardıcıl iki
            // dəvət edən adam ikinci mükafatını itirməməlidir.
            $from = $profile->boost_until && $profile->boost_until->isFuture()
                ? $profile->boost_until
                : now();

            $capped = min(
                $from->copy()->addDays(self::REWARD_DAYS),
                now()->addDays(self::MAX_DAYS)
            );

            $profile->forceFill(['boost_until' => $capped])->save();
        });
    }

    /**
     * Sürücünün boost ehtiyatından yeni elana düşən pay.
     *
     * Elanın `boosted_until`-i sürücünün ehtiyatı ilə elanın yola düşmə vaxtı
     * arasından kiçiyidir: yola düşmüş elanı axtarışın başında saxlamağın
     * mənası yoxdur, o onsuz da siyahıdan çıxır.
     */
    public function boostFor(User $driver, \DateTimeInterface $departureAt): ?\Illuminate\Support\Carbon
    {
        $until = $driver->driverProfile?->boost_until;

        if (! $until || $until->isPast()) {
            return null;
        }

        return $until->lessThan($departureAt) ? $until : \Illuminate\Support\Carbon::parse($departureAt);
    }

    /** "Dostunu dəvət et" ekranının məlumatı. */
    public function summary(User $user): array
    {
        $invited = User::where('referred_by', $user->id)->count();
        $active  = User::where('referred_by', $user->id)->whereNotNull('referral_rewarded_at')->count();
        $boost   = $user->driverProfile?->boost_until;

        return [
            'code'           => $user->referral_code,
            'invited_count'  => $invited,
            'active_count'   => $active,
            'reward_days'    => self::REWARD_DAYS,
            'boost_until'    => $boost && $boost->isFuture() ? $boost->toIso8601String() : null,
        ];
    }
}
