<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DriverProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'instant_booking_default',
    ];

    /*
     | `boost_until`, `pro_until` və `pro_trial_used_at` qəsdən `fillable` deyil.
     | Üçü də sürücünün axtarışdakı yerini müəyyən edir və yalnız servislər
     | (`ReferralService`, `ProService`) `forceFill` ilə yazır. Kütləvi
     | doldurmaya açıq olsaydı, profil sorğusuna əl ilə əlavə edilmiş bir açar
     | sürücüyə müddətsiz PRO verə bilərdi.
     */

    protected $casts = [
        'submitted_at'            => 'datetime',
        'reviewed_at'             => 'datetime',
        'instant_booking_default' => 'boolean',
        // Dəvət mükafatının bitmə vaxtı. Cast olmadan sütun sətir kimi gəlir
        // və `ReferralService`-dəki `isFuture()` çağırışı xəta verərdi.
        'boost_until'             => 'datetime',
        // PRO-nun bitmə vaxtı və sınağın başladığı an — eyni səbəbdən cast.
        'pro_until'               => 'datetime',
        'pro_trial_used_at'       => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function documents()
    {
        return $this->hasMany(VerificationDocument::class);
    }

    /** PRO müddəti hələ bitməyib. */
    public function isPro(): bool
    {
        return $this->pro_until !== null && $this->pro_until->isFuture();
    }

    /**
     * Sürücünün hazırkı boost ehtiyatının sonu — dəvət mükafatı (`boost_until`)
     * ilə PRO-dan (`pro_until`) hansı gec bitirsə.
     *
     * Toplanmır, böyüyü götürülür: hər ikisi "bu tarixə qədər elanların
     * başdadır" deməkdir, eyni günləri iki dəfə saymaq ehtiyatı süni uzadardı.
     */
    public function boostReserveUntil(): ?Carbon
    {
        $until = collect([$this->boost_until, $this->pro_until])
            ->filter(fn ($date) => $date !== null && $date->isFuture())
            ->max();

        return $until?->copy();
    }

    /**
     * Verilmiş yola düşmə vaxtlı elana düşən `boosted_until`.
     *
     * Ehtiyatla yola düşmə vaxtından kiçiyidir: yola düşmüş elanı axtarışın
     * başında saxlamağın mənası yoxdur, o onsuz da siyahıdan çıxır.
     *
     * Qayda yalnız buradadır — yeni elan (`ReferralService::boostFor`) də,
     * PRO dəyişəndə mövcud elanlar (`ProService::reboost`) da bunu çağırır.
     * İki nüsxə olsaydı, ləğv edilmiş PRO köhnə elanlarda qalıb yenilərdə
     * itə bilərdi.
     */
    public function boostedUntilFor(\DateTimeInterface $departureAt): ?Carbon
    {
        $until = $this->boostReserveUntil();

        if (! $until) {
            return null;
        }

        return $until->lessThan($departureAt) ? $until : Carbon::instance($departureAt);
    }
}
