<?php

namespace App\Services\Yandex;

use App\Services\Yandex\Exceptions\YandexMapsException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class YandexMapsClient
{
    private const MAX_ATTEMPTS = 3;

    public function getReviewsPage(string $url, int $page = 1): string
    {
        $requestUrl = $this->buildPageUrl($url, $page);

        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::timeout(20)
                    ->connectTimeout(10)
                    ->withHeaders([
                        'User-Agent' => $this->userAgent(),
                        'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    ])
                    ->get($requestUrl);

                if ($response->status() === 403) {
                    throw new YandexMapsException(
                        'Yandex Maps denied the request (HTTP 403). Please try again later.'
                    );
                }

                if ($response->status() === 429) {
                    if ($attempt === self::MAX_ATTEMPTS) {
                        throw new YandexMapsException(
                            'Yandex Maps rate limit exceeded (HTTP 429). Please try again later.'
                        );
                    }

                    $this->sleepBeforeRetry(
                        $attempt,
                        $response->header('Retry-After'),
                    );

                    continue;
                }

                if ($response->serverError()) {
                    if ($attempt === self::MAX_ATTEMPTS) {
                        throw new YandexMapsException(
                            "Yandex Maps returned HTTP {$response->status()}."
                        );
                    }

                    $this->sleepBeforeRetry($attempt);

                    continue;
                }

                if ($response->failed()) {
                    throw new YandexMapsException(
                        "Yandex Maps returned HTTP {$response->status()}."
                    );
                }

                $body = $response->body();

                if ($body === '') {
                    throw new YandexMapsException(
                        'Yandex Maps returned an empty response.'
                    );
                }

                return $body;
            } catch (ConnectionException $e) {
                $lastException = $e;

                if ($attempt === self::MAX_ATTEMPTS) {
                    throw new YandexMapsException(
                        'Unable to connect to Yandex Maps.'
                    );
                }

                $this->sleepBeforeRetry($attempt);
            }
        }

        throw new YandexMapsException(
            $lastException?->getMessage()
            ?? 'Unable to load data from Yandex Maps.'
        );
    }

    private function sleepBeforeRetry(
        int $attempt,
        ?string $retryAfter = null,
    ): void {
        if ($retryAfter !== null && is_numeric($retryAfter)) {
            $seconds = min((int) $retryAfter, 30);

            sleep($seconds);

            return;
        }

        $baseDelay = match ($attempt) {
            1 => 2,
            2 => 5,
            default => 10,
        };

        $jitter = random_int(0, 1000);

        usleep(($baseDelay * 1_000_000) + ($jitter * 1_000));
    }

    private function userAgent(): string
    {
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
            . 'AppleWebKit/537.36 '
            . 'Chrome/140.0.0.0 Safari/537.36';
    }

    private function buildPageUrl(string $url, int $page): string
    {
        if ($page <= 1) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'page=' . $page;
    }
}
