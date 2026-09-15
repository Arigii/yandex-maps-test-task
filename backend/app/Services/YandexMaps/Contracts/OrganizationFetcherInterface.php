<?php

namespace App\Services\YandexMaps\Contracts;

use App\Services\YandexMaps\DTO\ParsedOrganization;

interface OrganizationFetcherInterface
{
    public function fetchOrganizationPayload(string $yandexOrgId): array;

    public function fetchReviewsPage(string $yandexOrgId, int $offset, int $limit): array;
}
