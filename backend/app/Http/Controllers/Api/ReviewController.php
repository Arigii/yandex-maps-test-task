<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Organization $organization, Request $request): JsonResponse
    {
        abort_unless($organization->user_id === $request->user()->id, 403);

        $paginator = $organization->reviews()
            ->orderByDesc('published_at')
            ->paginate(self::PER_PAGE);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
            'summary' => [
                'rating' => $organization->rating,
                'ratings_count' => $organization->ratings_count,
                'reviews_count' => $organization->reviews_count,
            ],
        ]);
    }
}
