<?php

namespace App\Services;

use App\Models\RecentSearch;
use App\Models\Ride;
use App\Models\RideRequest;
use Illuminate\Support\Facades\DB;

/**
 * "Bu marşrutda nə qədər adam yer axtarır?"
 *
 * Sürücü indiyə qədər tam qaranlıqda elan verirdi: `GET /rides` yalnız təklifi
 * göstərirdi, tələbin isə heç bir izi yox idi. Halbuki data artıq bazadadır —
 * `recent_searches` hər axtarışı yazır. Bu servis həmin datanı sürücünün
 * görəcəyi rəqəmə çevirir.
 *
 * İki mənbə birləşdirilir və fərqləri vacibdir:
 *
 *   • **axtarış** — zəif siqnal, amma çoxdur. "43 nəfər baxıb."
 *   • **tələb elanı** — güclü siqnal, amma azdır. "6 nəfər konkret yer istəyir."
 *
 * Sürücüyə hər ikisi göstərilir, çünki birincisi ölçünü, ikincisi ciddiliyi
 * bildirir.
 */
class DemandService
{
    /** Axtarış statistikasının pəncərəsi (gün). */
    private const WINDOW_DAYS = 7;

    /**
     * Bir marşrutun mənzərəsi.
     *
     * @return array{
     *     searches: int, requests: int, requested_seats: int,
     *     active_rides: int, seats_available: int, window_days: int
     * }
     */
    public function forRoute(int $fromCityId, int $toCityId): array
    {
        // `recent_searches`-də (user, from, to) unikaldır, yəni sətir sayı
        // birbaşa **fərqli istifadəçi** sayıdır. Xam axtarış sayı olsaydı,
        // bir adamın on dəfə yenilədiyi ekran tələb kimi görünərdi.
        $searches = RecentSearch::query()
            ->where('from_city_id', $fromCityId)
            ->where('to_city_id', $toCityId)
            ->where('searched_at', '>=', now()->subDays(self::WINDOW_DAYS))
            ->count();

        $requests = RideRequest::query()
            ->open()
            ->upcoming()
            ->where('from_city_id', $fromCityId)
            ->where('to_city_id', $toCityId)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(seats), 0) as seats')
            )
            ->first();

        $supply = Ride::query()
            ->active()
            ->upcoming()
            ->where('from_city_id', $fromCityId)
            ->where('to_city_id', $toCityId)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(total_seats - booked_seats), 0) as seats')
            )
            ->first();

        return [
            'searches'        => $searches,
            'requests'        => (int) ($requests->total ?? 0),
            'requested_seats' => (int) ($requests->seats ?? 0),
            'active_rides'    => (int) ($supply->total ?? 0),
            'seats_available' => (int) ($supply->seats ?? 0),
            'window_days'     => self::WINDOW_DAYS,
        ];
    }

    /**
     * Sürücünün ana ekranı üçün ən "ac" marşrutlar.
     *
     * Sıralama xam axtarış sayına görə deyil, **qarşılanmamış tələbə** görədir:
     * Bakı–Sumqayıt hər gün yüzlərlə dəfə axtarılır, amma orada onsuz da elan
     * boldur və sürücüyə orada bir elan daha vermək təklif etmək faydasızdır.
     * Maraqlı olan çox axtarılıb az təklif olan marşrutdur.
     *
     * @return array<int, array<string, mixed>>
     */
    public function topRoutes(int $limit = 5, ?int $nearCityId = null): array
    {
        $since = now()->subDays(self::WINDOW_DAYS);

        $rows = RecentSearch::query()
            ->where('searched_at', '>=', $since)
            ->when($nearCityId, fn ($q) => $q->where(function ($inner) use ($nearCityId) {
                // Sürücünün şəhəri ya çıxış, ya çatış nöqtəsidir — başqa
                // bölgənin marşrutunu ona təklif etməyin mənası yoxdur.
                $inner->where('from_city_id', $nearCityId)
                    ->orWhere('to_city_id', $nearCityId);
            }))
            ->select(
                'from_city_id',
                'to_city_id',
                DB::raw('COUNT(*) as searches')
            )
            ->groupBy('from_city_id', 'to_city_id')
            ->havingRaw('COUNT(*) >= 3')
            // Ən çox axtarılanlardan bir neçə qat artıq götürürük, çünki
            // aşağıda təklifə görə süzgəcdən keçəcəklər.
            ->orderByDesc('searches')
            ->limit($limit * 6)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Namizəd marşrutların tələb və təklifi **iki toplu sorğu** ilə
        // gətirilir. `forRoute()`-u dövrədə çağırmaq oxunaqlı olardı, amma hər
        // marşrut üç sorğu deməkdir — otuz namizədlə bu, sürücünün hər ana
        // ekran açılışında doxsan sorğuya çevrilirdi.
        $pairs = $rows
            ->map(fn ($row) => [(int) $row->from_city_id, (int) $row->to_city_id])
            ->all();

        $requests = $this->groupedRequests($pairs);
        $supply   = $this->groupedSupply($pairs);

        $routes = [];

        foreach ($rows as $row) {
            $key = $row->from_city_id.':'.$row->to_city_id;

            $demand = [
                'searches'        => (int) $row->searches,
                'requests'        => (int) ($requests[$key]['total'] ?? 0),
                'requested_seats' => (int) ($requests[$key]['seats'] ?? 0),
                'active_rides'    => (int) ($supply[$key]['total'] ?? 0),
                'seats_available' => (int) ($supply[$key]['seats'] ?? 0),
                'window_days'     => self::WINDOW_DAYS,
            ];

            // Boş yer tələbi tam qarşılayırsa, marşrut "ac" deyil.
            $unmet = $demand['searches']
                + $demand['requested_seats'] * 3
                - $demand['seats_available'] * 2;

            if ($unmet <= 0) {
                continue;
            }

            $routes[] = [
                'from_city_id' => (int) $row->from_city_id,
                'to_city_id'   => (int) $row->to_city_id,
                'score'        => $unmet,
            ] + $demand;
        }

        usort($routes, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($routes, 0, $limit);
    }

    /**
     * Verilmiş marşrutlar üzrə açıq tələblər — bir sorğu.
     *
     * @param  array<int, array{0: int, 1: int}>  $pairs
     * @return array<string, array{total: int, seats: int}>
     */
    private function groupedRequests(array $pairs): array
    {
        return RideRequest::query()
            ->open()
            ->upcoming()
            ->where(fn ($query) => $this->matchAnyRoute($query, $pairs))
            ->groupBy('from_city_id', 'to_city_id')
            ->select(
                'from_city_id',
                'to_city_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(seats), 0) as seats')
            )
            ->get()
            ->keyBy(fn ($row) => $row->from_city_id.':'.$row->to_city_id)
            ->map(fn ($row) => ['total' => (int) $row->total, 'seats' => (int) $row->seats])
            ->all();
    }

    /**
     * Eyni marşrutlar üzrə aktiv elanlar — bir sorğu.
     *
     * @param  array<int, array{0: int, 1: int}>  $pairs
     * @return array<string, array{total: int, seats: int}>
     */
    private function groupedSupply(array $pairs): array
    {
        return Ride::query()
            ->active()
            ->upcoming()
            ->where(fn ($query) => $this->matchAnyRoute($query, $pairs))
            ->groupBy('from_city_id', 'to_city_id')
            ->select(
                'from_city_id',
                'to_city_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(total_seats - booked_seats), 0) as seats')
            )
            ->get()
            ->keyBy(fn ($row) => $row->from_city_id.':'.$row->to_city_id)
            ->map(fn ($row) => ['total' => (int) $row->total, 'seats' => (int) $row->seats])
            ->all();
    }

    /**
     * `(from, to) IN (...)` — marşrut cütlərinin hər hansı biri.
     *
     * Ayrıca metoddur, çünki iki sorğu da eyni şərti qurur və `whereIn` ilə
     * tuple müqayisəsi Eloquent-də birbaşa ifadə olunmur.
     *
     * @param  array<int, array{0: int, 1: int}>  $pairs
     */
    private function matchAnyRoute($query, array $pairs): void
    {
        foreach ($pairs as [$from, $to]) {
            $query->orWhere(function ($pair) use ($from, $to) {
                $pair->where('from_city_id', $from)->where('to_city_id', $to);
            });
        }
    }
}
