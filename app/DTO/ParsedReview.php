<?php

namespace App\DTO;

use Carbon\CarbonImmutable;

final readonly class ParsedReview
{
    public function __construct(
        public string $externalId,
        public ?string $author,
        public ?CarbonImmutable $publishedAt,
        public string $text,
        public int $rating,
    )
    {
    }
}

