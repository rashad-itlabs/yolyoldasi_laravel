<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Ride;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        // Sürücü öz nömrəsini həmişə görür, ona görə hər resurs ayrıca qurulur.
        $vehicles = $request->user()->vehicles()->get()
            ->map(fn ($vehicle) => (new VehicleResource($vehicle))->withPlate(true));

        return response()->json(['data' => $vehicles]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, required: true);

        $user = $request->user();

        $vehicle = DB::transaction(function () use ($user, $data) {
            $vehicle = $user->vehicles()->create($data + ['seats' => $data['seats'] ?? 4]);

            // İlk avtomobil avtomatik sürücü profilinə bağlanır.
            $profile = $user->driverProfile()->firstOrCreate([]);

            if (! $profile->vehicle_id) {
                $profile->forceFill(['vehicle_id' => $vehicle->id])->save();
            }

            $user->forceFill(['has_driver_profile' => true])->save();

            return $vehicle;
        });

        return (new VehicleResource($vehicle))->withPlate(true)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $this->authorizeOwner($request, $vehicle);

        $vehicle->update($this->validated($request, required: false));

        return (new VehicleResource($vehicle))->withPlate(true);
    }

    public function destroy(Request $request, Vehicle $vehicle)
    {
        $this->authorizeOwner($request, $vehicle);

        abort_if(
            Ride::where('vehicle_id', $vehicle->id)
                ->whereIn('status', ['active', 'inactive'])
                ->exists(),
            422,
            'Bu avtomobilə bağlı aktiv səfər var.'
        );

        $vehicle->delete();

        return response()->json(['message' => 'Avtomobil silindi.']);
    }

    private function validated(Request $request, bool $required): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return $request->validate([
            'brand' => [$rule, 'string', 'max:255'],
            'model' => [$rule, 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'plate' => ['nullable', 'string', 'max:15'],
            'year'  => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'seats' => ['nullable', 'integer', 'min:1', 'max:4'],
        ]);
    }

    private function authorizeOwner(Request $request, Vehicle $vehicle): void
    {
        abort_unless($vehicle->user_id === $request->user()->id, 403, 'Bu avtomobil sənin deyil.');
    }
}
