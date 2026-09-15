<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Jobs\ParseOrganizationReviewsJob;
use App\Models\Organization;
use App\Services\YandexMaps\Exceptions\InvalidOrganizationUrlException;
use App\Services\YandexMaps\UrlValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(private readonly UrlValidator $urlValidator) {}

    public function current(Request $request): JsonResponse
    {
        $organization = $request->user()->organizations()->latest()->first();

        return response()->json(['organization' => $organization]);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        try {
            $orgId = $this->urlValidator->extractOrgId($request->validated()['url']);
        } catch (InvalidOrganizationUrlException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $organization = Organization::updateOrCreate(
            ['user_id' => $request->user()->id, 'yandex_org_id' => $orgId],
            ['source_url' => $request->validated()['url'], 'status' => 'queued', 'progress' => 0, 'last_error' => null]
        );

        ParseOrganizationReviewsJob::dispatch($organization->id)->onQueue('parsing');

        return response()->json(['organization' => $organization], 202);
    }

    public function status(Organization $organization, Request $request): JsonResponse
    {
        $this->authorizeOwnership($organization, $request);

        return response()->json([
            'status' => $organization->status,
            'progress' => $organization->progress,
            'last_error' => $organization->last_error,
            'last_parsed_at' => $organization->last_parsed_at,
        ]);
    }

    public function reparse(Organization $organization, Request $request): JsonResponse
    {
        $this->authorizeOwnership($organization, $request);

        $organization->update(['status' => 'queued', 'progress' => 0, 'last_error' => null]);
        ParseOrganizationReviewsJob::dispatch($organization->id)->onQueue('parsing');

        return response()->json(['organization' => $organization], 202);
    }

    private function authorizeOwnership(Organization $organization, Request $request): void
    {
        abort_unless($organization->user_id === $request->user()->id, 403);
    }
}
