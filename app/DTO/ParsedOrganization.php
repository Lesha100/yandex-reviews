<?php

namespace App\DTO;

final readonly class ParsedOrganization
{
    /**
     * @param ParsedReview[] $reviews
     */
    public function __construct(
        public string $name,
        public ?string $yandexId,
        public float $rating,
        public int $ratingsCount,
        public int $reviewsCount,
        public array $reviews,
    )
    {
    }
}
