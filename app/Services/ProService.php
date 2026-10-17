<?php

namespace App\Services;

use App\Models\DriverProfile;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sürücü PRO-su.
 *
 * Bu mərhələdə PRO iki şey verir: sürücünün elanları axtarışın başında çıxır
 * (boost) və sürücü göründüyü yerlərdə PRO nişanı daşıyır. Əsas funksiyalar
 * hamı üçün pulsuz və komissiyasız qalır — PRO onların heç birini bağlamır.
 *
 * Ödəniş qatı yoxdur və qəsdən əlavə olunmayıb. İndilik PRO-ya iki yol var:
 * sürücünün özünün bir dəfə başlatdığı pulsuz sınaq və admin panelindən əl
 * ilə vermə/ləğv. Pullu abunə gələndə `pro_until`-i uzadan üçüncü yol olacaq;
 * qalan hər şey — boost, nişan, `summary` — olduğu kimi qalır.
 *
 * Boost mexanizmi yeni deyil: dəvət mükafatının `rides.boosted_until`
 * sütunudur. PRO sürücünün boost ehtiyatına ikinci mənbə əlavə edir
 * (bax `DriverProfile::boostReserveUntil`).
 */
class ProService
{
    /** Pulsuz sınağın müddəti (gün). */
    public const TRIAL_DAYS = 30;

    /** Admin panelindəki düymənin bir dəfəyə əlavə etdiyi müddət (gün). */
    public const GRANT_DAYS = 30;

    /**
     * Sürücü pulsuz sınağı özü başladır — hesab başına bir dəfə.
     *
     * Yalnız sənədləri təsdiqlənmiş sürücü üçün — `RideController::ensureApprovedDriver`
     * ilə eyni qayda. Elan verə bilməyən sürücünün boost-u heç nəyi qaldırmır,
     * və sınaq günləri təsdiqi gözləyərkən boş yerə yanardı.
     */
    public function startTrial(User $driver): DriverProfile
    {
        $profile = $driver->driverProfile;
        $status  = $profile?->status;

        abort_unless($status === 'approved', 422, match ($status) {
            'pending'  => 'Sənədləriniz yoxlanılır. Təsdiqdən sonra PRO sınağını başlada biləcəksiniz.',
            'rejected' => 'Sənədləriniz rədd edilib. Yenidən yükləyin.',
            default    => 'PRO üçün əvvəlcə sürücü sənədlərinizi yükləyin.',
        });

        abort_if($profile->pro_trial_used_at !== null, 409, 'Pulsuz PRO sınağından artıq istifadə etmisiniz.');

        DB::transaction(function () use ($profile) {
            // Şərt sorğunun içindədir (`ReferralService::reward` ilə eyni səbəb):
            // iki paralel toxunuşda PHP tərəfdəki yoxlama ikisini də buraxardı
            // və sınaq iki dəfə verilərdi.
            $claimed = DriverProfile::whereKey($profile->id)
                ->whereNull('pro_trial_used_at')
                ->update(['pro_trial_used_at' => now()]);

            abort_if($claimed === 0, 409, 'Pulsuz PRO sınağından artıq istifadə etmisiniz.');

            $profile->refresh();

            $this->extend($profile, self::TRIAL_DAYS);
        });

        return $profile;
    }

    /**
     * Admin PRO verir və ya uzadır.
     *
     * Qalan müddətin üstünə gəlinir: sınağın ortasında verilən günlər sınağın
     * qalanını əvəz etməməlidir.
     */
    public function grant(DriverProfile $profile, int $days): DriverProfile
    {
        DB::transaction(fn () => $this->extend($profile, $days));

        return $profile;
    }

    /**
     * Admin PRO-nu dərhal bitirir.
     *
     * `pro_trial_used_at`-a toxunulmur: ləğvdən sonra sürücü sınağı yenidən
     * başlada bilsəydi, ləğvin mənası qalmazdı.
     */
    public function revoke(DriverProfile $profile): DriverProfile
    {
        DB::transaction(function () use ($profile) {
            $profile->forceFill(['pro_until' => null])->save();

            $this->reboost($profile);
        });

        return $profile;
    }

    /**
     * Sürücünün açıq elanlarının `boosted_until`-ini yenidən hesablayır — elan
     * indi verilsəydi, nə alardı.
     *
     * Ehtiyat elana elan veriləndə köçürülür, ona görə PRO dəyişəndə mövcud
     * elanlar özü-özünə yenilənmir: sınağı başladan sürücü artıq verdiyi
     * elanların da qalxmasını gözləyir, ləğv edilmiş PRO isə köhnə elanlarda
     * qalmamalıdır. Dəyər sıfırdan hesablanır, PRO hissəsi "çıxılmır" — dəvət
     * mükafatı varsa, elanda o qalır.
     *
     * `inactive` elanlar da daxildir: admin onları yenidən aktivləşdirə bilir
     * və köhnəlmiş boost ilə qayıtmamalıdırlar.
     *
     * Tək `UPDATE ... LEAST(departure_at, ?)` yox, sətir-sətir: qayda
     * (`DriverProfile::boostedUntilFor`) PHP-də bir yerdədir, SQL-də ikinci
     * nüsxəsini yazmaq ikisinin zamanla ayrılması deməkdir. Bir sürücünün
     * gələcək elanları azdır (təkrar elan ən çox 8 həftə), dövr ucuzdur.
     */
    public function reboost(DriverProfile $profile): void
    {
        $rides = Ride::query()
            ->where('driver_id', $profile->user_id)
            ->whereIn('status', ['active', 'inactive'])
            ->where('departure_at', '>', now())
            ->get();

        foreach ($rides as $ride) {
            // Dəyər dəyişməyibsə `save()` sorğu göndərmir.
            $ride->forceFill([
                'boosted_until' => $profile->boostedUntilFor($ride->departure_at),
            ])->save();
        }
    }

    /** `GET /driver/profile` cavabındakı `pro` bloku. */
    public function summary(DriverProfile $profile): array
    {
        $active = $profile->isPro();

        return [
            'active'          => $active,
            // Bitmiş tarix göndərilmir — klient onu səhvən "PRO-dur" kimi oxuya bilərdi.
            'until'           => $active ? $profile->pro_until->toIso8601String() : null,
            'trial_available' => $profile->pro_trial_used_at === null,
            'trial_days'      => self::TRIAL_DAYS,
        ];
    }

    /** Qalan PRO-nun üstünə gəlinir; bitibsə və ya heç olmayıbsa, indidən sayılır. */
    private function extend(DriverProfile $profile, int $days): void
    {
        $from = $profile->isPro() ? $profile->pro_until : now();

        $profile->forceFill(['pro_until' => $from->copy()->addDays($days)])->save();

        $this->reboost($profile);
    }
}
