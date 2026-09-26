<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    /**
     * Dövlət nişanı həssas məlumatdır: yalnız sürücünün özünə və
     * rezervasiyası təsdiqlənmiş sərnişinə göstərilir.
     * İstifadə: (new VehicleResource($vehicle))->withPlate($canSee)
     */
    protected bool $showPlate = false;

    public function withPlate(bool $show = true): static
    {
        $this->showPlate = $show;

        return $this;
    }

    public function toArray($request): array
    {
        return [
            'id'    => $this->id,
            'brand' => $this->brand,
            'model' => $this->model,
            'color' => $this->color,
            'year'  => $this->year,
            'seats' => $this->seats,
            'plate' => $this->when($this->showPlate, $this->plate),
        ];
    }
}
