<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'author'            => new UserBriefResource($this->whenLoaded('author')),
            'rating'            => $this->rating,
            'author_was_driver' => (bool) $this->author_was_driver,
            'comment'           => $this->comment,
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
