<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStat extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_id',
        'driver_rating',
        'driver_review_count',
        'driver_trip_count',
        'passenger_rating',
        'passenger_review_count',
        'passenger_trip_count',
        'driver_requests_total',
        'driver_decided_count',
        'driver_decision_minutes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sürücünün cavab faizi (0–100) — gələn sorğuların neçəsinə cavab verib.
     *
     * Az sayda sorğuda rəqəm mənasızdır: bir sorğuya cavab verən sürücü
     * "100% cavab verir" görünərdi. Ona görə üç sorğudan azda `null`, yəni
     * klient heç nə göstərmir.
     */
    public function getDriverResponseRateAttribute(): ?int
    {
        if ($this->driver_requests_total < 3) {
            return null;
        }

        return (int) round($this->driver_decided_count / $this->driver_requests_total * 100);
    }

    /** Orta cavab vaxtı (dəqiqə). Cavablandırılmış sorğu yoxdursa `null`. */
    public function getDriverResponseMinutesAttribute(): ?int
    {
        if ($this->driver_decided_count < 3) {
            return null;
        }

        return (int) round($this->driver_decision_minutes / $this->driver_decided_count);
    }

    /**
     * Sürücünün etibar pilləsi — `new` | `rising` | `trusted`.
     *
     * Reytinq təkbaşına aldadıcıdır: bir səfər etmiş 5.0 sürücü on beş səfər
     * etmiş 4.7-dən yaxşı görünür. Pillə hər ikisini birləşdirir və sərnişinə
     * bir sözlə oxunan siqnal verir.
     */
    public function getDriverTierAttribute(): string
    {
        if ($this->driver_trip_count >= 15 && $this->driver_rating >= 4.5) {
            return 'trusted';
        }

        if ($this->driver_trip_count >= 5 && $this->driver_rating >= 4.0) {
            return 'rising';
        }

        return 'new';
    }
}
