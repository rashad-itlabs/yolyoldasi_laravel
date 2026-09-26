<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\City;
use App\Models\Ride;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'status']);

        $cities = City::query()
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where('name', 'like', "%{$q}%"))
            ->when($filters['status'] ?? null, fn ($query, $s) => $query->where('is_active', $s === 'active'))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        // Hansı şəhərin nə qədər işlədildiyini göstərmək silmə qərarını asanlaşdırır.
        $departures = Ride::selectRaw('from_city_id id, COUNT(*) c')->groupBy('from_city_id')->pluck('c', 'id');
        $arrivals   = Ride::selectRaw('to_city_id id, COUNT(*) c')->groupBy('to_city_id')->pluck('c', 'id');

        $usage = $cities->getCollection()->mapWithKeys(fn ($city) => [
            $city->id => (int) ($departures[$city->id] ?? 0) + (int) ($arrivals[$city->id] ?? 0),
        ]);

        return view('admin.cities.index', compact('cities', 'filters', 'usage'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100', Rule::unique('cities', 'name')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $city = DB::transaction(function () use ($data) {
            $city = City::create([
                'name'      => $data['name'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            AdminActivityLog::record('city.created', $city, ['name' => $city->name]);

            return $city;
        });

        return back()->with('success', "«{$city->name}» əlavə edildi.");
    }

    public function update(Request $request, City $city)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100', Rule::unique('cities', 'name')->ignore($city->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($city, $data) {
            $before = $city->name;

            $city->update([
                'name'      => $data['name'],
                'is_active' => $data['is_active'] ?? false,
            ]);

            AdminActivityLog::record('city.updated', $city, [
                'from' => $before,
                'to'   => $city->name,
            ]);
        });

        return back()->with('success', "«{$city->name}» yeniləndi.");
    }

    public function toggle(City $city)
    {
        DB::transaction(function () use ($city) {
            $city->update(['is_active' => ! $city->is_active]);

            AdminActivityLog::record(
                $city->is_active ? 'city.activated' : 'city.deactivated',
                $city,
                ['name' => $city->name]
            );
        });

        return back()->with(
            'success',
            "«{$city->name}» ".($city->is_active ? 'aktivləşdirildi' : 'deaktiv edildi').'.'
        );
    }

    /**
     * Şəhərə bağlı səfər varsa silmək olmaz — rides.from_city_id açarı
     * onsuz da imtina edərdi, amma səbəbi anlaşıqlı demək daha yaxşıdır.
     */
    public function destroy(City $city)
    {
        $inUse = Ride::where('from_city_id', $city->id)->orWhere('to_city_id', $city->id)->exists();

        if ($inUse) {
            return back()->withErrors([
                'city' => "«{$city->name}» səfərlərdə işlədilir — silmək əvəzinə deaktiv edin.",
            ]);
        }

        DB::transaction(function () use ($city) {
            AdminActivityLog::record('city.deleted', $city, ['name' => $city->name]);
            $city->delete();
        });

        return back()->with('success', "«{$city->name}» silindi.");
    }
}
