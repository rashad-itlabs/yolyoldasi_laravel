<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Models\City;
use App\Services\DemandService;
use App\Services\PriceSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Sürücüyə tələbi göstərən endpoint-lər.
 *
 * Elan vermək indiyə qədər qaranlıqda atış idi. Bu iki sorğu onu cavab
 * verməyə çevirir: sürücü hansı marşrutda nə qədər adamın yer axtardığını
 * və həmin marşrutda nə qədər qiymət qoyulduğunu görür.
 */
class DemandController extends Controller
{
    /** Konkret marşrutun tələb/təklif mənzərəsi. */
    public function show(Request $request, DemandService $demand)
    {
        $data = $request->validate([
            'from_city_id' => ['required', 'integer', 'exists:cities,id'],
            'to_city_id'   => ['required', 'integer', 'exists:cities,id', 'different:from_city_id'],
        ]);

        return response()->json([
            'data' => $demand->forRoute($data['from_city_id'], $data['to_city_id']),
        ]);
    }

    /**
     * Sürücünün ana ekranı üçün ən çox tələb olunan marşrutlar.
     *
     * Şəhər sürücünün profilindən götürülür, sorğudan yox — başqa bölgənin
     * marşrutunu ona təklif etməyin mənası yoxdur. Profilində şəhər yoxdursa
     * ölkə üzrə ən "ac" marşrutlar qayıdır.
     */
    public function top(Request $request, DemandService $demand)
    {
        $routes = $demand->topRoutes(5, $request->user()?->city_id);

        if ($routes === []) {
            return response()->json(['data' => []]);
        }

        // Şəhər adları bir sorğu ilə gətirilir — sətir başına iki sorğu
        // etsək, beş marşrut on sorğu edərdi.
        $cityIds = collect($routes)
            ->flatMap(fn ($route) => [$route['from_city_id'], $route['to_city_id']])
            ->unique();

        $cities = City::whereIn('id', $cityIds)->get()->keyBy('id');

        $data = collect($routes)->map(fn ($route) => array_merge($route, [
            'from_city' => new CityResource($cities[$route['from_city_id']] ?? null),
            'to_city'   => new CityResource($cities[$route['to_city_id']] ?? null),
        ]));

        return response()->json(['data' => $data]);
    }

    /**
     * Marşrut üçün təklif olunan qiymət və təxmini yanacaq xərci.
     *
     * Elan formasının üçüncü addımında göstərilir: sürücü rəqəmi görməmiş
     * qiymət qoya bilmir və çox vaxt ya bahaya, ya da ucuza yazır.
     */
    public function price(Request $request, PriceSuggestionService $prices)
    {
        $data = $request->validate([
            'from_city_id' => ['required', 'integer', 'exists:cities,id'],
            'to_city_id'   => ['required', 'integer', 'exists:cities,id'],
        ]);

        if ((int) $data['from_city_id'] === (int) $data['to_city_id']) {
            throw ValidationException::withMessages([
                'to_city_id' => 'Gediş və çatış şəhəri eyni ola bilməz.',
            ]);
        }

        $from = City::findOrFail($data['from_city_id']);
        $to   = City::findOrFail($data['to_city_id']);

        return response()->json(['data' => $prices->forRoute($from, $to)]);
    }
}
