<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sərnişinin tələb elanı — "bu marşrutda, bu tarixdə yer axtarıram".
 *
 * Bazarın ikinci yarısıdır: `rides` təklifi, bu cədvəl tələbi saxlayır.
 * Sürücü öz marşrutunda kimin nə istədiyini buradan görür, sərnişin isə uyğun
 * elan yaranan kimi buradan xəbər alır.
 */
class RideRequest extends Model
{
    protected $fillable = [
        'user_id', 'from_city_id', 'to_city_id', 'wanted_date',
        'flexible_days', 'seats', 'note', 'status', 'matched_ride_id', 'notified_at',
    ];

    protected $casts = [
        'wanted_date' => 'date',
        'notified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fromCity()
    {
        return $this->belongsTo(City::class, 'from_city_id');
    }

    public function toCity()
    {
        return $this->belongsTo(City::class, 'to_city_id');
    }

    public function matchedRide()
    {
        return $this->belongsTo(Ride::class, 'matched_ride_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /** Tarixi keçməmiş tələblər — `flexible_days` sonuncu günü uzadır. */
    public function scopeUpcoming($query)
    {
        return $query->whereRaw('DATE_ADD(wanted_date, INTERVAL flexible_days DAY) >= ?', [now()->toDateString()]);
    }

    /**
     * Axtarılan tarix aralığının sərhədləri.
     *
     * `flexible_days` hər iki tərəfə açılır: "cümə ±1 gün" cümə axşamı da,
     * şənbə də deməkdir. Bir tərəfə açsaq, rayona gedən adamın real planına
     * uyğun gəlməzdi — o, gedişi bir gün qabağa da, bir gün geriyə də çəkir.
     */
    public function dateRange(): array
    {
        return [
            $this->wanted_date->copy()->subDays($this->flexible_days)->startOfDay(),
            $this->wanted_date->copy()->addDays($this->flexible_days)->endOfDay(),
        ];
    }
}
