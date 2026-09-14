<?php

namespace App\Services\Yandex;

use App\DTO\ParsedOrganization;

final class YandexReviewsService
{
    private const REVIEWS_PER_PAGE = 50;
    private const MAX_PAGES = 12;

    public function __construct(
        private readonly YandexMapsClient $client,
        private readonly YandexMapsParser $parser,
        private readonly YandexMapsUrlResolver $urlResolver,
    ) {
    }

    public function parse(
        string $url,
        ?callable $onProgress = null,
    ): ParsedOrganization {
        $url = $this->urlResolver->resolve($url);

        $organization = null;
        $reviews = [];

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $html = $this->client->getReviewsPage($url, $page);

            $parsed = $this->parser->parse($html);

            if ($organization === null) {
                $organization = $parsed;
            }

            foreach ($parsed->reviews as $review) {
                $reviews[$review->externalId] = $review;
            }

            $progress = (int) round(
                ($page / self::MAX_PAGES) * 100
            );

            if ($onProgress !== null) {
                $onProgress($progress);
            }

            if (count($parsed->reviews) < self::REVIEWS_PER_PAGE) {
                break;
            }
        }

        return new ParsedOrganization(
            name: $organization->name,
            yandexId: $organization->yandexId,
            rating: $organization->rating,
            ratingsCount: $organization->ratingsCount,
            reviewsCount: $organization->reviewsCount,
            reviews: array_values($reviews),
        );
    }
}
