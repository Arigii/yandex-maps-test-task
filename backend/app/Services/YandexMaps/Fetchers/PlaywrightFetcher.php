<?php

namespace App\Services\YandexMaps\Fetchers;

use App\Services\YandexMaps\Contracts\OrganizationFetcherInterface;
use App\Services\YandexMaps\Exceptions\SourceUnavailableException;
use App\Services\YandexMaps\Exceptions\StructureChangedException;
use Symfony\Component\Process\Process;

class PlaywrightFetcher implements OrganizationFetcherInterface
{
    public function fetchOrganizationPayload(string $yandexOrgId): array
    {
        return $this->runScript(['--mode=org', "--id={$yandexOrgId}"]);
    }

    public function fetchReviewsPage(string $yandexOrgId, int $offset, int $limit): array
    {
        return $this->runScript([
            '--mode=reviews', "--id={$yandexOrgId}", "--offset={$offset}", "--limit={$limit}",
        ]);
    }

    private function runScript(array $args): array
    {
        $process = new Process(['node', base_path('resources/scripts/yandex-scrape.mjs'), ...$args]);
        $process->setTimeout(120);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new SourceUnavailableException(
                'Headless-браузер завершился с ошибкой: '.$process->getErrorOutput()
            );
        }

        $data = json_decode($process->getOutput(), true);

        if (! is_array($data)) {
            throw new StructureChangedException(
                'Playwright-скрипт вернул не-JSON.',
                ['stderr' => $process->getErrorOutput(), 'stdout_snippet' => substr($process->getOutput(), 0, 500)]
            );
        }

        return $data;
    }
}
