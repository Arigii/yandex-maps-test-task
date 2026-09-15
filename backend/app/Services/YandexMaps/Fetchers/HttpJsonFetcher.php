<?php

namespace App\Services\YandexMaps\Fetchers;

use App\Services\YandexMaps\Contracts\OrganizationFetcherInterface;
use App\Services\YandexMaps\Exceptions\BannedException;
use App\Services\YandexMaps\Exceptions\SourceUnavailableException;
use App\Services\YandexMaps\Exceptions\StructureChangedException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpJsonFetcher implements OrganizationFetcherInterface
{
    private array $cachedReviews = [];

    private ?array $orgPayload = null;

    private ?array $lastParams = null;

    public function __construct(
        private readonly int $requestDelayMs = 800,
    ) {}

    public function fetchOrganizationPayload(string $yandexOrgId): array
    {
        $state = $this->fetchStatePage($yandexOrgId, 2);
        $item = $this->extractItem($state, $yandexOrgId);

        $rating = $item['ratingData'] ?? null;
        if ($rating === null) {
            throw new StructureChangedException(
                'В состоянии страницы нет ratingData — формат карточки организации изменился.',
                ['item_keys' => array_keys($item)]
            );
        }

        $this->orgPayload = [
            'name' => $item['title'] ?? null,
            'address' => $item['address'] ?? null,
            'rating' => $rating['ratingValue'] ?? null,
            'ratingsCount' => $rating['ratingCount'] ?? null,
            'reviewsCount' => $rating['reviewCount'] ?? null,
        ];

        $this->cacheReviewsFromItem($item);

        return $this->orgPayload;
    }

    public function fetchReviewsPage(string $yandexOrgId, int $offset, int $limit): array
    {
        while (
            count($this->cachedReviews) < $offset + $limit
            && ($this->lastParams['reviewsRemained'] ?? 0) > 0
        ) {
            $nextPage = ($this->lastParams['page'] ?? 1) + 1;
            $state = $this->fetchStatePage($yandexOrgId, $nextPage);
            $item = $this->extractItem($state, $yandexOrgId);

            $before = count($this->cachedReviews);
            $this->cacheReviewsFromItem($item);

            if (count($this->cachedReviews) === $before) {
                Log::warning('yandex_parser.reviews_page_no_progress', [
                    'org' => $yandexOrgId, 'page' => $nextPage, 'params' => $this->lastParams,
                ]);
                break;
            }
        }

        $all = array_values($this->cachedReviews);
        $slice = array_slice($all, $offset, $limit);

        return [
            'items' => $slice,
            'total' => $this->orgPayload['reviewsCount'] ?? count($all),
            'hasMore' => ($offset + $limit) < count($all) || ($this->lastParams['reviewsRemained'] ?? 0) > 0,
        ];
    }

    private function fetchStatePage(string $yandexOrgId, int $page): array
    {
        $this->throttle();

        $query = http_build_query(['reviews' => ['page' => $page]]);
        $url = "https://yandex.ru/maps/org/{$yandexOrgId}/reviews/?{$query}";

        $response = $this->request('GET', $url);

        if (! preg_match(
            '#<script type="application/json" class="state-view"[^>]*>(.*?)</script>#s',
            $response->body(),
            $m
        )) {
            throw new StructureChangedException(
                'Не нашли <script class="state-view"> в HTML страницы — Яндекс изменил вёрстку/способ встраивания данных.',
                ['org' => $yandexOrgId, 'page' => $page, 'status' => $response->status()]
            );
        }

        $state = json_decode($m[1], true);

        if (! is_array($state)) {
            throw new StructureChangedException(
                'Содержимое state-view не является валидным JSON — формат изменился.',
                ['org' => $yandexOrgId, 'page' => $page, 'json_error' => json_last_error_msg()]
            );
        }

        return $state;
    }

    private function extractItem(array $state, string $yandexOrgId): array
    {
        $item = $state['stack'][0]['results']['items'][0] ?? null;

        if (! is_array($item)) {
            throw new StructureChangedException(
                'Не нашли stack[0].results.items[0] в состоянии страницы — структура изменилась.',
                ['org' => $yandexOrgId, 'top_level_keys' => array_keys($state)]
            );
        }

        return $item;
    }

    private function cacheReviewsFromItem(array $item): void
    {
        $rr = $item['reviewResults'] ?? null;

        if (! is_array($rr) || ! isset($rr['reviews'])) {
            throw new StructureChangedException(
                'В карточке нет reviewResults.reviews — формат блока отзывов изменился.',
                ['item_keys' => array_keys($item)]
            );
        }

        $this->lastParams = $rr['params'] ?? null;

        foreach ($rr['reviews'] as $raw) {
            $id = $raw['reviewId'] ?? null;
            if ($id === null) {
                continue;
            }

            $avatar = $raw['author']['avatarUrl'] ?? null;

            $this->cachedReviews[$id] = [
                'id' => $id,
                'author' => $raw['author']['name'] ?? null,
                'avatarUrl' => $avatar ? str_replace('{size}', 'islands-68', $avatar) : null,
                'rating' => $raw['rating'] ?? null,
                'text' => $raw['text'] ?? null,
                'publishedAt' => $raw['updatedTime'] ?? null,
            ];
        }
    }

    private function request(string $method, string $url, array $options = [])
    {
        $options['headers'] ??= [];
        $options['headers'] += [
            'User-Agent' => $this->pickUserAgent(),
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'ru-RU,ru;q=0.9',
        ];

        try {
            $response = Http::withOptions(['proxy' => $this->pickProxy()])
                ->timeout(15)
                ->retry(2, 500)
                ->send($method, $url, $options);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new SourceUnavailableException("Сетевая ошибка при обращении к Яндекс.Картам: {$e->getMessage()}", 0, $e);
        }

        if ($response->status() === 429 || $this->looksLikeCaptcha($response->body())) {
            Log::warning('yandex_parser.banned', ['url' => $url]);
            throw new BannedException('Похоже на бан/капчу от Яндекса (429 или капча-страница).');
        }

        if ($response->serverError() || in_array($response->status(), [403, 404, 502, 503])) {
            throw new SourceUnavailableException("Источник ответил статусом {$response->status()} на {$url}");
        }

        return $response;
    }

    private function looksLikeCaptcha(string $body): bool
    {
        return str_contains($body, 'showCaptcha') || str_contains($body, 'SmartCaptcha');
    }

    private function pickUserAgent(): string
    {
        $pool = array_filter(explode('|', (string) config('yandex-parser.user_agent_pool')));

        return $pool ? $pool[array_rand($pool)] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    }

    private function pickProxy(): ?string
    {
        $pool = array_filter(explode(',', (string) config('yandex-parser.proxy_pool')));

        return $pool ? $pool[array_rand($pool)] : null;
    }

    private function throttle(): void
    {
        usleep($this->requestDelayMs * 1000);
    }
}
