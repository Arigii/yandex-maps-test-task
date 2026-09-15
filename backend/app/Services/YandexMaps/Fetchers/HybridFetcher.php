<?php

namespace App\Services\YandexMaps\Fetchers;

use App\Services\YandexMaps\Contracts\OrganizationFetcherInterface;

class HybridFetcher implements OrganizationFetcherInterface
{
    public function __construct(
        private readonly HttpJsonFetcher $http,
        private readonly PlaywrightFetcher $browser,
    ) {}

    public function fetchOrganizationPayload(string $yandexOrgId): array
    {
        return $this->http->fetchOrganizationPayload($yandexOrgId);
    }

    public function fetchReviewsPage(string $yandexOrgId, int $offset, int $limit): array
    {
        $page = $this->http->fetchReviewsPage($yandexOrgId, $offset, $limit);

        if (count($page['items']) >= $limit || ! $page['hasMore']) {
            return $page;
        }

        return $this->browser->fetchReviewsPage($yandexOrgId, $offset, $limit);
    }
}
