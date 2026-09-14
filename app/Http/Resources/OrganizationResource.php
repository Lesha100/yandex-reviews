<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'yandex_url' => $this->yandex_url,
            'yandex_id' => $this->yandex_id,
            'name' => $this->name,
            'rating' => (float) $this->rating,
            'ratings_count' => $this->ratings_count,
            'reviews_count' => $this->reviews_count,
            'last_parsed_at' => $this->last_parsed_at?->toISOString(),
            'parse_status' => $this->parse_status,
            'parse_progress' => $this->parse_progress,
            'parse_error' => $this->parse_error,
        ];
    }
}
