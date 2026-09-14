<?php

namespace App\Services\Yandex;

use App\DTO\ParsedOrganization;
use App\DTO\ParsedReview;
use App\Services\Yandex\Exceptions\YandexMapsStructureChangedException;
use Carbon\CarbonImmutable;

final class YandexMapsParser
{
    public function parse(string $html): ParsedOrganization
    {
        $state = $this->extractState($html);
        $organization = $this->extractOrganization($state);

        return new ParsedOrganization(
            name: $organization['name'],
            yandexId: $organization['yandexId'],
            rating: $organization['rating'],
            ratingsCount: $organization['ratingsCount'],
            reviewsCount: $organization['reviewsCount'],
            reviews: array_map(
                fn (array $review): ParsedReview => $this->parseReview($review),
                $organization['reviews'],
            ),
        );
    }

    private function extractState(string $html): array
    {
        $pattern = '~<script\s+type="application/json"\s+class="state-view">(.*?)</script>~s';

        if (!preg_match($pattern, $html, $matches)) {
            throw new YandexMapsStructureChangedException(
                'Yandex Maps state-view was not found.'
            );
        }

        try {
            $state = json_decode(
                $matches[1],
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new YandexMapsStructureChangedException(
                'Yandex Maps returned invalid state JSON.',
                previous: $e,
            );
        }

        if (!is_array($state)) {
            throw new YandexMapsStructureChangedException(
                'Yandex Maps state has an invalid format.'
            );
        }

        return $state;
    }

    private function extractOrganization(array $state): array
    {
        $organization = $state['stack'][0]['results']['items'][0] ?? null;

        if (!is_array($organization)) {
            throw new YandexMapsStructureChangedException(
                'Organization data was not found in Yandex Maps state.'
            );
        }

        $name = $organization['title'] ?? null;
        $yandexId = $organization['id'] ?? null;

        $ratingData = $organization['ratingData'] ?? null;
        $reviewsData = $organization['reviewResults'] ?? null;

        $rating = $ratingData['ratingValue'] ?? null;
        $ratingsCount = $ratingData['ratingCount'] ?? null;
        $reviewsCount = $ratingData['reviewCount'] ?? null;

        $reviews = $reviewsData['reviews'] ?? null;

        if (
            !is_string($name)
            || $name === ''
            || !is_array($ratingData)
            || !is_numeric($rating)
            || !is_numeric($ratingsCount)
            || !is_numeric($reviewsCount)
            || !is_array($reviews)
        ) {
            throw new YandexMapsStructureChangedException(
                'Yandex Maps organization structure has changed.'
            );
        }

        return [
            'name' => $name,
            'yandexId' => is_scalar($yandexId)
                ? (string) $yandexId
                : null,
            'rating' => (float) $rating,
            'ratingsCount' => (int) $ratingsCount,
            'reviewsCount' => (int) $reviewsCount,
            'reviews' => $reviews,
        ];
    }

    private function parseReview(array $review): ParsedReview
    {
        $externalId = $review['reviewId'] ?? null;
        $author = $review['author']['name'] ?? null;
        $text = $review['text'] ?? null;
        $rating = $review['rating'] ?? null;

        if (!is_string($externalId) || $externalId === '') {
            throw new YandexMapsStructureChangedException(
                'Yandex Maps review has invalid reviewId.'
            );
        }

        if (!is_string($text)) {
            throw new YandexMapsStructureChangedException(
                'Yandex Maps review has invalid text.'
            );
        }

        if (!is_numeric($rating)) {
            throw new YandexMapsStructureChangedException(
                'Yandex Maps review has invalid rating.'
            );
        }

        if (!is_string($author) || $author === '') {
            $author = null;
        }

        $publishedAt = null;

        if (!empty($review['updatedTime'])) {
            try {
                $publishedAt = CarbonImmutable::parse(
                    $review['updatedTime']
                );
            } catch (\Throwable) {
            }
        }

        return new ParsedReview(
            externalId: $externalId,
            author: $author,
            publishedAt: $publishedAt,
            text: $text,
            rating: (int) $rating,
        );
    }
}
