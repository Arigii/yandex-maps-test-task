<?php

namespace App\Services\YandexMaps\DTO;

final class ParsedOrganization
{
    public function __construct(
        public readonly string $yandexOrgId,
        public readonly ?string $name,
        public readonly ?string $address,
        public readonly ?float $rating,
        public readonly ?int $ratingsCount,
        public readonly ?int $reviewsCount,
        public array $reviews = [],
    ) {}
}
