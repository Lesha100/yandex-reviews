<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'author' => $this->author,
            'published_at' => $this->published_at?->toISOString(),
            'text' => $this->text,
            'rating' => $this->rating,
        ];
    }
}
