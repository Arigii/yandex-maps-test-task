<?php

namespace App\Providers;

use App\Services\YandexMaps\Contracts\OrganizationFetcherInterface;
use App\Services\YandexMaps\Fetchers\HttpJsonFetcher;
use App\Services\YandexMaps\Fetchers\HybridFetcher;
use App\Services\YandexMaps\Fetchers\MockFetcher;
use App\Services\YandexMaps\Fetchers\PlaywrightFetcher;
use Illuminate\Support\ServiceProvider;

class YandexParserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/yandex-parser.php', 'yandex-parser');

        $this->app->bind(OrganizationFetcherInterface::class, function () {
            if (config('yandex-parser.use_mock')) {
                return new MockFetcher();
            }

            $delay = config('yandex-parser.request_delay_ms');

            return match (config('yandex-parser.strategy')) {
                'http_json' => new HttpJsonFetcher($delay),
                'playwright' => new PlaywrightFetcher(),
                default => new HybridFetcher(new HttpJsonFetcher($delay), new PlaywrightFetcher()),
            };
        });
    }
}
