<?php

namespace App\Models;

use App\Support\LocalTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Ride extends Model
{
    protected $fillable = [
        'driver_id', 'vehicle_id', 'from_city_id', 'to_city_id', 'departure_at',
        'total_seats', 'booked_seats', 'price_per_seat', 'status', 'note',
        'pickup_point', 'dropoff_point', 'instant_booking', 'women_only',
    ];

    /*
     | `boosted_until` qəsdən `fillable` deyil — dəyəri yalnız `RideController`
     | (yeni elan) və `ProService::reboost` (PRO dəyişəndə mövcud elanlar)
     | sürücünün ehtiyatından götürüb `forceFill` ilə yazır. Kütləvi doldurmaya
     | açıq olsa, `POST /rides` sorğusuna əl ilə əlavə edilmiş bir sahə
     | istənilən elanı axtarışın başına çıxarardı.
     */

    protected $casts = [
        'departure_at'    => 'datetime',
        'price_per_seat'  => 'decimal:2',
        'instant_booking' => 'boolean',
        'women_only'      => 'boolean',
        'boosted_until'   => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function fromCity()
    {
        return $this->belongsTo(City::class, 'from_city_id');
    }

    public function toCity()
    {
        return $this->belongsTo(City::class, 'to_city_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function openBookings()
    {
        return $this->bookings()->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Qarşıdakı səfərin açıq rezervasiyası varsa, sərnişinlər onu gözləyir —
     * admin əvvəlcə ləğv etməlidir ki, onlara bildiriş getsin. Səssiz silmə
     * bunu etmir.
     *
     * `withCount('openBookings')` yüklənibsə, əlavə sorğu getmir.
     */
    public function mustBeCancelledBeforeDelete(): bool
    {
        if (! $this->departure_at->isFuture()) {
            return false;
        }

        return ($this->open_bookings_count ?? $this->openBookings()->count()) > 0;
    }

    public function getSeatsLeftAttribute(): int
    {
        return max(0, $this->total_seats - $this->booked_seats);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('departure_at', '>=', now());
    }

    /**
     * Yola düşmə vaxtı Bakı saatı ilə — admin panelində və saytın paylaşım
     * səhifəsində göstərmək üçün. Sütun UTC-dədir (bax `LocalTime`); birbaşa
     * `departure_at->format()` Bakıda 4 saat erkən vaxt yazardı.
     */
    public function departureLocal(): ?Carbon
    {
        return LocalTime::toLocal($this->departure_at);
    }

    public function getIsBoostedAttribute(): bool
    {
        return $this->boosted_until !== null && $this->boosted_until->isFuture();
    }

    /**
     * Axtarış sıralaması: boost-lu elanlar başda, sonra istənilən meyar.
     *
     * `orderByRaw` ilə yazılır, çünki `boosted_until` null ola bilər və
     * MySQL-də `ORDER BY boosted_until DESC` null-ları sona atır — bizə isə
     * "vaxtı keçməmiş boost varmı" sualının cavabı lazımdır, tarixin özü yox.
     */
    public function scopeBoostedFirst($query)
    {
        return $query->orderByRaw('(boosted_until IS NOT NULL AND boosted_until > ?) DESC', [now()]);
    }
}
