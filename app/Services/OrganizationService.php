<?php

namespace App\Services;

use App\DTO\ParsedOrganization;
use App\DTO\ParsedReview;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class OrganizationService
{
    public function saveParsedOrganization(
        User $user,
        string $yandexUrl,
        ParsedOrganization $data,
    ): Organization {
        return DB::transaction(function () use ($user, $yandexUrl, $data) {
            $organization = Organization::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'yandex_url' => $yandexUrl,
                ],
                [
                    'yandex_id' => $data->yandexId,
                    'name' => $data->name,
                    'rating' => $data->rating,
                    'ratings_count' => $data->ratingsCount,
                    'reviews_count' => $data->reviewsCount,
                    'last_parsed_at' => now(),
                ],
            );

            foreach ($data->reviews as $review) {
                /** @var ParsedReview $review */
                $organization->reviews()->updateOrCreate(
                    [
                        'external_id' => $review->externalId,
                    ],
                    [
                        'author' => $review->author,
                        'published_at' => $review->publishedAt,
                        'text' => $review->text,
                        'rating' => $review->rating,
                    ],
                );
            }

            return $organization;
        });
    }

    public function createForParsing(
        User $user,
        string $yandexUrl,
    ): Organization {
        return Organization::updateOrCreate(
            [
                'user_id' => $user->id,
                'yandex_url' => $yandexUrl,
            ],
            [
                'parse_status' => 'pending',
                'parse_progress' => 0,
                'parse_error' => null,
            ],
        );
    }
}
