<?php

namespace App\Services\YandexMaps\Fetchers;

use App\Services\YandexMaps\Contracts\OrganizationFetcherInterface;

class MockFetcher implements OrganizationFetcherInterface
{
    public function fetchOrganizationPayload(string $yandexOrgId): array
    {
        mt_srand(crc32($yandexOrgId));

        $reviewsCount = 520 + (crc32($yandexOrgId) % 80);
        $ratingsCount = $reviewsCount + 120;

        return [
            'name' => 'Кофейня «Бодрое утро» (демо, oid '.$yandexOrgId.')',
            'address' => 'г. Москва, ул. Тестовая, д. 1',
            'rating' => round(3.8 + (crc32($yandexOrgId) % 12) / 10, 2),
            'ratingsCount' => $ratingsCount,
            'reviewsCount' => $reviewsCount,
        ];
    }

    public function fetchReviewsPage(string $yandexOrgId, int $offset, int $limit): array
    {
        mt_srand(crc32($yandexOrgId));
        $total = 520 + (crc32($yandexOrgId) % 80);

        $items = [];
        $authors = ['Анна К.', 'Дмитрий', 'Ольга П.', 'Сергей', 'Мария', 'Игорь Волков', 'Наталья'];
        $texts = [
            'Отличное место, всё понравилось, обязательно вернёмся.',
            'Долго ждали заказ, но качество на высоте.',
            'Средне. Ожидал большего за эти деньги.',
            'Прекрасный сервис и приветливый персонал!',
            'Не понравилось — было шумно и тесно.',
        ];

        for ($i = $offset; $i < min($offset + $limit, $total); $i++) {
            $items[] = [
                'id' => 'mock-review-'.$yandexOrgId.'-'.$i,
                'author' => $authors[$i % count($authors)],
                'avatarUrl' => null,
                'rating' => 1 + ($i % 5),
                'text' => $texts[$i % count($texts)],
                'publishedAt' => now()->subDays($i)->toAtomString(),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'hasMore' => $offset + $limit < $total,
        ];
    }
}
