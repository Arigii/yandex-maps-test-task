<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Exceptions\InvalidOrganizationUrlException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UrlValidator
{
    private const ALLOWED_HOSTS = ['yandex.ru', 'maps.yandex.ru', 'yandex.com'];
    private const MAX_REDIRECT_HOPS = 5;

    /** @throws InvalidOrganizationUrlException */
    public function extractOrgId(string $url, int $redirectsFollowed = 0): string
    {
        $url = trim($url);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidOrganizationUrlException('Строка не похожа на URL.');
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($host, self::ALLOWED_HOSTS, true)) {
            throw new InvalidOrganizationUrlException(
                "Ссылка должна вести на yandex.ru/maps (получен хост: {$host})."
            );
        }

        $path = $parts['path'] ?? '';

        if (preg_match('#/maps/org/(?:[^/]+/)?(\d+)#', $path, $m)) {
            return $m[1];
        }

        parse_str($parts['query'] ?? '', $query);
        if (! empty($query['oid']) && ctype_digit((string) $query['oid'])) {
            return (string) $query['oid'];
        }

        if (! empty($query['poi']['uri']) && preg_match('/[?&]oid=(\d+)/', (string) $query['poi']['uri'], $m2)) {
            return $m2[1];
        }

        if (str_starts_with($path, '/maps/-/')) {
            if ($redirectsFollowed >= self::MAX_REDIRECT_HOPS) {
                throw new InvalidOrganizationUrlException('Слишком много редиректов при переходе по короткой ссылке.');
            }

            $resolved = $this->followRedirect($url);

            return $this->extractOrgId($resolved, $redirectsFollowed + 1);
        }

        throw new InvalidOrganizationUrlException(
            'Не удалось найти идентификатор организации в ссылке. Ожидается ссылка вида '.
            'yandex.ru/maps/org/название/1234567890/ (короткие ссылки yandex.ru/maps/-/... тоже поддерживаются).'
        );
    }

    /** @throws InvalidOrganizationUrlException */
    private function followRedirect(string $url): string
    {
        try {
            $response = Http::withOptions(['allow_redirects' => false])
                ->timeout(10)
                ->get($url);
        } catch (ConnectionException $e) {
            Log::warning('yandex_url_validator.redirect_failed', ['url' => $url, 'message' => $e->getMessage()]);

            throw new InvalidOrganizationUrlException(
                'Не удалось перейти по короткой ссылке (источник недоступен). Попробуйте вставить полную ссылку из адресной строки браузера.'
            );
        }

        if (! $response->redirect() || ! $response->header('Location')) {
            Log::warning('yandex_url_validator.no_redirect', ['url' => $url, 'status' => $response->status()]);

            throw new InvalidOrganizationUrlException(
                'Короткая ссылка не привела к редиректу — возможно, она устарела или недействительна.'
            );
        }

        $location = $response->header('Location');

        if (! str_starts_with($location, 'http')) {
            $location = 'https://yandex.ru'.(str_starts_with($location, '/') ? '' : '/').$location;
        }

        return $location;
    }
}
