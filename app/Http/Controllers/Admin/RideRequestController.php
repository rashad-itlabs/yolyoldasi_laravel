<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\RideRequest;
use Illuminate\Http\Request;

/**
 * Sərnişin tələb elanları — bazarın sürücü panelindən görünməyən yarısı.
 *
 * Səhifə yalnız baxış üçündür: elanı sərnişin özü yaradır və ləğv edir,
 * adminin müdaxiləsi üçün səbəb yoxdur. Buradakı dəyər idarəetmədə yox,
 * **hansı marşrutda tələb olduğunu, amma səfər olmadığını** görməkdədir —
 * sürücü cəlb etməyin ən dəqiq göstəricisi budur.
 */
class RideRequestController extends Controller
{
    /** Status => [başlıq]. Sıra səhifədəki tabların sırasıdır. */
    public const STATUSES = [
        'open'      => 'Açıq',
        'fulfilled' => 'Matçlanıb',
        'expired'   => 'Vaxtı keçib',
        'cancelled' => 'Ləğv edilib',
    ];

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'status', 'from_city_id', 'to_city_id', 'date_from', 'date_to']);

        $requests = RideRequest::query()
            ->with(['user', 'fromCity', 'toCity', 'matchedRide'])
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(
                fn ($inner) => $inner->where('id', $q)
                    ->orWhereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%"))
            ))
            ->when($filters['status'] ?? null, fn ($query, $s) => $query->where('status', $s))
            ->when($filters['from_city_id'] ?? null, fn ($query, $c) => $query->where('from_city_id', $c))
            ->when($filters['to_city_id'] ?? null, fn ($query, $c) => $query->where('to_city_id', $c))
            ->when($filters['date_from'] ?? null, fn ($query, $d) => $query->whereDate('wanted_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($query, $d) => $query->whereDate('wanted_date', '<=', $d))
            ->latest('wanted_date')
            ->paginate(25)
            ->withQueryString();

        $cities = City::orderBy('name')->get();

        $counts = RideRequest::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return view('admin.ride-requests.index', [
            'requests' => $requests,
            'filters'  => $filters,
            'cities'   => $cities,
            'counts'   => $counts,
            'statuses' => self::STATUSES,
        ]);
    }
}
