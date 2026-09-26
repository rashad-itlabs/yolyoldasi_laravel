<?php

namespace App\Services;

use App\Models\City;
use App\Models\Ride;

/**
 * Marşrut üçün qiymət təklifi və qazanc hesabı.
 *
 * İlk dəfə elan verən sürücünün ən çox ilişdiyi yer qiymətdir. Çox yazsa heç
 * kim müraciət etmir və o, bir daha qayıtmır; az yazsa yanacağını çıxarmır.
 * İkisi də itirilmiş sürücüdür.
 *
 * Təklif iki mənbədən çıxır və ardıcıllıq vacibdir:
 *
 *   1. **Real tarixçə** — həmin marşrutda verilmiş elanların medianı. Ən
 *      dəqiq siqnal budur, çünki bazarın özünün razılaşdığı rəqəmdir.
 *   2. **Məsafə** — tarixçə yoxdursa (yeni marşrut) kilometrə görə hesab.
 *
 * Median götürülür, orta yox: bir sürücünün səhvən yazdığı 500 manat ortanı
 * yerindən oynadar, medianı isə tərpətməz.
 */
class PriceSuggestionService
{
    /** Kilometr başına təxmini pay (AZN) — tarixçə olmayanda istifadə olunur. */
    private const AZN_PER_KM = 0.09;

    /** Yaxın rəqəmə yuvarlaqlaşdırma addımı. 14.37 manat heç kimin yazmadığı rəqəmdir. */
    private const ROUNDING = 0.5;

    /** Tarixçənin etibarlı sayılması üçün lazım olan minimum elan sayı. */
    private const MIN_SAMPLES = 3;

    /**
     * @return array{
     *     suggested: ?float, min: ?float, max: ?float,
     *     source: string, sample_size: int, distance_km: ?float,
     *     fuel_estimate: ?float
     * }
     */
    public function forRoute(City $from, City $to): array
    {
        $distance = City::roadDistanceKm($from, $to);

        $prices = Ride::query()
            ->where('from_city_id', $from->id)
            ->where('to_city_id', $to->id)
            ->whereIn('status', ['active', 'completed'])
            ->where('created_at', '>=', now()->subMonths(6))
            ->orderBy('price_per_seat')
            ->pluck('price_per_seat')
            ->map(fn ($price) => (float) $price)
            ->values();

        if ($prices->count() >= self::MIN_SAMPLES) {
            return [
                'suggested'     => $this->round($this->median($prices->all())),
                'min'           => $this->round($this->percentile($prices->all(), 0.25)),
                'max'           => $this->round($this->percentile($prices->all(), 0.75)),
                'source'        => 'history',
                'sample_size'   => $prices->count(),
                'distance_km'   => $distance,
                'fuel_estimate' => $this->fuel($distance),
            ];
        }

        if ($distance === null) {
            // Nə tarixçə, nə koordinat. Uydurma rəqəm göstərmək susmaqdan
            // pisdir — sürücü ona inanıb səhv qiymət qoyar.
            return [
                'suggested'     => null,
                'min'           => null,
                'max'           => null,
                'source'        => 'none',
                'sample_size'   => $prices->count(),
                'distance_km'   => null,
                'fuel_estimate' => null,
            ];
        }

        $estimate = $distance * self::AZN_PER_KM;

        return [
            'suggested'     => $this->round($estimate),
            'min'           => $this->round($estimate * 0.8),
            'max'           => $this->round($estimate * 1.25),
            'source'        => 'distance',
            'sample_size'   => $prices->count(),
            'distance_km'   => $distance,
            'fuel_estimate' => $this->fuel($distance),
        ];
    }

    /**
     * Marşrutun təxmini yanacaq xərci.
     *
     * Sürücüyə göstərilən əsas müqayisə budur: "3 yer × 15 ₼ = 45 ₼, yanacaq
     * isə təxminən 30 ₼". Rəqəm dəqiq deyil və olmamalıdır — məqsəd xərcin
     * böyüklük sırasını göstərməkdir.
     *
     * 100 km-də 8 litr, litri 1.20 ₼ (AI-95). Dəyişəndə yalnız bu sətir.
     */
    public function fuel(?float $distanceKm): ?float
    {
        if ($distanceKm === null) {
            return null;
        }

        return round($distanceKm / 100 * 8 * 1.20, 1);
    }

    private function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2
            ? $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
    }

    private function percentile(array $values, float $p): float
    {
        sort($values);
        $index = (int) round(($count = count($values)) * $p);

        return $values[max(0, min($count - 1, $index))];
    }

    private function round(float $value): float
    {
        return max(1.0, round($value / self::ROUNDING) * self::ROUNDING);
    }
}
