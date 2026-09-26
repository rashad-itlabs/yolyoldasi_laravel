<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use Illuminate\Http\Request;

class RecentSearchController extends Controller
{
    public function index(Request $request)
    {
        $searches = $request->user()->recentSearches()
            ->with(['fromCity', 'toCity'])
            ->orderByDesc('searched_at')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => $searches->map(fn ($s) => [
                'from_city'     => new CityResource($s->fromCity),
                'to_city'       => new CityResource($s->toCity),
                'searched_date' => $s->searched_date?->toDateString(),
                'seats'         => $s->seats,
                'searched_at'   => $s->searched_at?->toIso8601String(),
            ]),
        ]);
    }

    public function destroy(Request $request)
    {
        $request->user()->recentSearches()->delete();

        return response()->json(['message' => 'Axtarış tarixçəsi silindi.']);
    }
}
