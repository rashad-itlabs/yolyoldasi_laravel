<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RideRequestResource;
use App\Http\Resources\RideResource;
use App\Models\Ride;
use App\Models\RideRequest;
use App\Services\RideMatchService;
use Illuminate\Http\Request;

/**
 * Sərnişin tələb elanları.
 *
 * Bu controller bazarın çatışmayan yarısıdır. Ondan əvvəl sərnişin boş
 * axtarış nəticəsi alanda edə biləcəyi heç nə yox idi: ekranda "səfər
 * tapılmadı" yazısı vardı və adam çıxıb gedirdi. İndi həmin an tələbə
 * çevrilir — sürücü onu görür, sərnişin isə uyğun elan yaranan gün geri
 * çağırılır.
 */
class RideRequestController extends Controller
{
    /** Sərnişinin öz tələb elanları. */
    public function index(Request $request)
    {
        $requests = $request->user()->rideRequests()
            ->with(['fromCity', 'toCity'])
            ->when(
                $request->query('status'),
                fn ($q, $status) => $q->where('status', $status),
                // Filtrsiz sorğuda bağlanmışlar göstərilmir: sərnişin üçün
                // maraqlı olan hələ gözlədikləridir.
                fn ($q) => $q->whereIn('status', ['open', 'fulfilled'])
            )
            ->orderBy('wanted_date')
            ->paginate(20);

        return RideRequestResource::collection($requests);
    }

    public function store(Request $request, RideMatchService $matcher)
    {
        $data = $request->validate([
            'from_city_id'  => ['required', 'integer', 'exists:cities,id'],
            'to_city_id'    => ['required', 'integer', 'exists:cities,id', 'different:from_city_id'],
            'wanted_date'   => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'flexible_days' => ['nullable', 'integer', 'min:0', 'max:3'],
            'seats'         => ['nullable', 'integer', 'min:1', 'max:4'],
            'note'          => ['nullable', 'string', 'max:300'],
        ]);

        $user = $request->user();

        // Eyni marşrut və tarix üçün ikinci sətir yaratmaq əvəzinə mövcudu
        // yenilənir. `updateOrCreate` seçilib, çünki unikal indeks onsuz da
        // ikinci sətri buraxmır və istifadəçiyə 409 göstərmək burada mənasız
        // olardı — o, sadəcə yer sayını dəyişmək istəyir.
        $rideRequest = RideRequest::updateOrCreate(
            [
                'user_id'      => $user->id,
                'from_city_id' => $data['from_city_id'],
                'to_city_id'   => $data['to_city_id'],
                'wanted_date'  => $data['wanted_date'],
            ],
            [
                'flexible_days' => $data['flexible_days'] ?? 0,
                'seats'         => $data['seats'] ?? 1,
                'note'          => $data['note'] ?? null,
                'status'        => 'open',
                // Yenidən açılan tələb üçün bildiriş sayğacı sıfırlanır,
                // yoxsa sərnişin bir gün ərzində xəbər ala bilməzdi.
                'notified_at'   => null,
            ]
        );

        $rideRequest->load(['fromCity', 'toCity']);

        // Marşrutu tanıyan sürücülərə xəbər. Səssizdir: bildiriş getməsə də
        // tələb elanı yaradılıb sayılır.
        $matcher->notifyRouteDrivers($rideRequest);

        // Sərnişin "yazdım, gözləyirəm" ekranında qalmamalıdır — bu saniyə
        // uyğun səfər varsa, cavabın içində gedir.
        $matches = $matcher->matchingRides($rideRequest);

        return (new RideRequestResource($rideRequest))
            ->additional(['matches' => RideResource::collection($matches)])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, RideRequest $rideRequest, RideMatchService $matcher)
    {
        // Başqasının tələbini sürücü də görə bilməlidir (siyahıdan keçid),
        // amma yalnız açıq olanı — bağlanmış tələb şəxsi məlumatdır.
        abort_unless(
            $rideRequest->user_id === $request->user()->id || $rideRequest->status === 'open',
            403,
            'Bu tələb elanı əlçatan deyil.'
        );

        $rideRequest->load(['fromCity', 'toCity', 'user.stats']);

        return (new RideRequestResource($rideRequest))
            ->additional([
                'matches' => RideResource::collection($matcher->matchingRides($rideRequest)),
            ]);
    }

    /** Sərnişin tələbini bağlayır. */
    public function destroy(Request $request, RideRequest $rideRequest)
    {
        abort_unless($rideRequest->user_id === $request->user()->id, 403, 'Bu tələb sənin deyil.');

        $rideRequest->update(['status' => 'cancelled']);

        return new RideRequestResource($rideRequest->load(['fromCity', 'toCity']));
    }

    /**
     * Sürücünün gördüyü tələblər.
     *
     * Marşrut verilməyəndə sürücünün **əvvəllər sürdüyü** marşrutlar götürülür.
     * Bütün açıq tələbləri göstərmək olardı, amma o siyahı Bakı–Sumqayıt ilə
     * dolar və Şəkiyə gedən sürücü öz marşrutunu heç vaxt görməzdi.
     */
    public function incoming(Request $request)
    {
        $filters = $request->validate([
            'from_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'to_city_id'   => ['nullable', 'integer', 'exists:cities,id'],
        ]);

        $user = $request->user();

        $query = RideRequest::query()
            ->open()
            ->upcoming()
            ->where('user_id', '!=', $user->id)
            ->with(['fromCity', 'toCity', 'user.stats']);

        if (isset($filters['from_city_id'], $filters['to_city_id'])) {
            $query->where('from_city_id', $filters['from_city_id'])
                ->where('to_city_id', $filters['to_city_id']);
        } else {
            $routes = Ride::where('driver_id', $user->id)
                ->select('from_city_id', 'to_city_id')
                ->distinct()
                ->limit(50)
                ->get();

            if ($routes->isEmpty()) {
                // Hələ elan verməmiş sürücü. Şəhəri varsa oradan keçən
                // tələbləri göstəririk — ilk elanını verməsi üçün ən yaxşı
                // səbəb məhz budur.
                if ($user->city_id) {
                    $query->where(function ($inner) use ($user) {
                        $inner->where('from_city_id', $user->city_id)
                            ->orWhere('to_city_id', $user->city_id);
                    });
                }
            } else {
                $query->where(function ($inner) use ($routes) {
                    foreach ($routes as $route) {
                        $inner->orWhere(function ($pair) use ($route) {
                            $pair->where('from_city_id', $route->from_city_id)
                                ->where('to_city_id', $route->to_city_id);
                        });
                    }
                });
            }
        }

        return RideRequestResource::collection(
            $query->orderBy('wanted_date')->paginate(20)
        );
    }
}
