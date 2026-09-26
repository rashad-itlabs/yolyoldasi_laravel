<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'is_active', 'lat', 'lng'];

    protected $casts = [
        'is_active' => 'boolean',
        'lat'       => 'float',
        'lng'       => 'float',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * İki şəhər arasındakı yol məsafəsi (km).
     *
     * Haversine düz xətt verir, yol isə həmişə uzundur — 1.25 əmsalı
     * Azərbaycan şəraitində orta fərqdir və tətbiqdəki `az_cities.dart` ilə
     * eyni rəqəmi verir. Koordinat yoxdursa `null`, çünki uydurma məsafə
     * qiymət təklifini də uydurma edərdi.
     */
    public static function roadDistanceKm(?self $from, ?self $to): ?float
    {
        // `null` yoxlanışı açıqdır: `!$from->lat` sıfır enliyi də boş sayardı.
        // Azərbaycanda belə şəhər yoxdur, amma qayda kodun özündə görünməlidir.
        if ($from === null || $to === null
            || $from->lat === null || $from->lng === null
            || $to->lat === null || $to->lng === null) {
            return null;
        }

        $earthKm = 6371.0;

        $dLat = deg2rad($to->lat - $from->lat);
        $dLng = deg2rad($to->lng - $from->lng);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($from->lat)) * cos(deg2rad($to->lat)) * sin($dLng / 2) ** 2;

        return round($earthKm * 2 * atan2(sqrt($a), sqrt(1 - $a)) * 1.25, 1);
    }
}
