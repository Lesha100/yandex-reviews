<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewController extends Controller
{
    private const REVIEWS_PER_PAGE = 50;

    public function index(Request $request): ReviewResource|JsonResource|JsonResponse
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

        $reviews = $organization->reviews()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(self::REVIEWS_PER_PAGE);

        return ReviewResource::collection($reviews);
    }
}
