<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Jobs\ParseOrganizationJob;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService,
    ) {
    }

    public function show(Request $request): OrganizationResource|JsonResponse
    {
        $organization = $request->user()
            ->organizations()
            ->latest('id')
            ->first();

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found.',
            ], 404);
        }

        return new OrganizationResource($organization);
    }

    public function store(
        StoreOrganizationRequest $request,
    ): OrganizationResource {
        $user = $request->user();

        $url = $request->validated('yandex_url');

        $organization = $this->organizationService->createForParsing(
            $user,
            $url,
        );

        ParseOrganizationJob::dispatch($organization->id);

        return new OrganizationResource($organization);
    }
}
