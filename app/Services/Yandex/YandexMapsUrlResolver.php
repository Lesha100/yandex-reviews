<?php

namespace App\Services\Yandex;

use App\Services\Yandex\Exceptions\YandexMapsException;

final class YandexMapsUrlResolver
{
    public function resolve(string $url): string
    {
        $parts = parse_url($url);

        if (!is_array($parts)) {
            throw new YandexMapsException(
                'Invalid Yandex Maps URL.'
            );
        }

        $query = $parts['query'] ?? '';

        parse_str($query, $params);

        $poiUri = $params['poi']['uri'] ?? null;

        if (is_string($poiUri)) {
            $organizationId = $this->extractOrganizationId($poiUri);

            if ($organizationId !== null) {
                $host = $parts['host'] ?? 'yandex.ru';

                return "https://{$host}/maps/org/{$organizationId}/reviews/";
            }
        }

        if (preg_match('~/maps/org/[^/]+/(\d+)~', $url)) {
            return $url;
        }

        throw new YandexMapsException(
            'Yandex Maps organization could not be identified from this URL.'
        );
    }

    private function extractOrganizationId(string $poiUri): ?string
    {
        $decoded = urldecode($poiUri);

        if (preg_match('/[?&]oid=(\d+)/', $decoded, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
