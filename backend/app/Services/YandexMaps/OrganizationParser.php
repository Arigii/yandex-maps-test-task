<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Contracts\OrganizationFetcherInterface;
use App\Services\YandexMaps\DTO\ParsedOrganization;
use App\Services\YandexMaps\DTO\ParsedReview;
use App\Services\YandexMaps\Exceptions\StructureChangedException;

class OrganizationParser
{
    private const PAGE_SIZE = 50;
    private const HARD_LIMIT = 700;

    public function __construct(
        private readonly OrganizationFetcherInterface $fetcher,
    ) {}

    public function parse(string $yandexOrgId, ?callable $onProgress = null): ParsedOrganization
    {
        $payload = $this->fetcher->fetchOrganizationPayload($yandexOrgId);

        $org = new ParsedOrganization(
            yandexOrgId: $yandexOrgId,
            name: $payload['name'] ?? null,
            address: $payload['address'] ?? null,
            rating: isset($payload['rating']) ? (float) $payload['rating'] : null,
            ratingsCount: isset($payload['ratingsCount']) ? (int) $payload['ratingsCount'] : null,
            reviewsCount: isset($payload['reviewsCount']) ? (int) $payload['reviewsCount'] : null,
        );

        $offset = 0;

        do {
            $page = $this->fetcher->fetchReviewsPage($yandexOrgId, $offset, self::PAGE_SIZE);

            foreach ($page['items'] as $raw) {
                $org->reviews[] = $this->mapReview($raw);
            }

            $offset += self::PAGE_SIZE;

            if ($onProgress) {
                $onProgress(count($org->reviews), $page['total'] ?? $org->reviewsCount ?? 0);
            }
        } while (($page['hasMore'] ?? false) && $offset < self::HARD_LIMIT);

        return $org;
    }

    private function mapReview(array $raw): ParsedReview
    {
        foreach (['id', 'rating'] as $key) {
            if (! array_key_exists($key, $raw)) {
                throw new StructureChangedException(
                    "В объекте отзыва отсутствует поле '{$key}' — формат отзывов изменился.",
                    ['keys_present' => array_keys($raw)]
                );
            }
        }

        return new ParsedReview(
            externalId: (string) $raw['id'],
            author: $raw['author'] ?? null,
            authorAvatarUrl: $raw['avatarUrl'] ?? null,
            rating: (int) $raw['rating'],
            text: $raw['text'] ?? null,
            publishedAt: isset($raw['publishedAt']) ? new \DateTimeImmutable($raw['publishedAt']) : null,
        );
    }
}
