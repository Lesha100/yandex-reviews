<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\OrganizationService;
use App\Services\Yandex\YandexReviewsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ParseOrganizationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(
        public int $organizationId,
    ) {
    }

    public function handle(
        YandexReviewsService $yandexReviewsService,
        OrganizationService $organizationService,
    ): void {
        $organization = Organization::find($this->organizationId);

        if (!$organization) {
            return;
        }

        $organization->update([
            'parse_status' => 'processing',
            'parse_error' => null,
        ]);

        $parsedOrganization = $yandexReviewsService->parse(
            $organization->yandex_url,
            function (int $progress) use ($organization): void {
                $organization->update([
                    'parse_progress' => $progress,
                ]);
            },
        );

        $organizationService->saveParsedOrganization(
            $organization->user,
            $organization->yandex_url,
            $parsedOrganization,
        );

        $organization->update([
            'parse_status' => 'completed',
            'parse_progress' => 100,
            'parse_error' => null,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $organization = Organization::find($this->organizationId);

        if (!$organization) {
            return;
        }

        $organization->update([
            'parse_status' => 'failed',
            'parse_error' => $exception->getMessage(),
        ]);
    }
}
