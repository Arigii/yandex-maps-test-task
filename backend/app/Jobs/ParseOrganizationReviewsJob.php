<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\ParseJob;
use App\Services\YandexMaps\Exceptions\BannedException;
use App\Services\YandexMaps\Exceptions\SourceUnavailableException;
use App\Services\YandexMaps\Exceptions\StructureChangedException;
use App\Services\YandexMaps\OrganizationParser;
use App\Services\YandexMaps\OrganizationSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ParseOrganizationReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public int $timeout = 600;

    public function __construct(public readonly int $organizationId) {}

    public function handle(OrganizationParser $parser, OrganizationSyncService $sync): void
    {
        $organization = Organization::findOrFail($this->organizationId);

        $this->acquireGlobalSlot();

        $job = ParseJob::create([
            'organization_id' => $organization->id,
            'status' => 'running',
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries,
            'started_at' => now(),
        ]);

        $organization->update(['status' => 'parsing', 'progress' => 0]);

        try {
            $parsed = $parser->parse($organization->yandex_org_id, function (int $fetched, int $total) use ($organization, $job) {
                $progress = $total > 0 ? min(99, (int) round($fetched / $total * 100)) : 0;
                $organization->update(['progress' => $progress]);
                $job->update(['progress' => $progress, 'reviews_fetched' => $fetched]);
            });

            $sync->apply($organization, $parsed, $job);

            $job->update(['status' => 'done', 'progress' => 100, 'finished_at' => now()]);
        } catch (StructureChangedException $e) {
            Log::error('yandex_parser.structure_changed', ['org' => $organization->id, 'message' => $e->getMessage(), 'ctx' => $e->debugContext]);
            $organization->update(['status' => 'structure_changed', 'last_error' => $e->getMessage()]);
            $job->update(['status' => 'structure_changed', 'error' => $e->getMessage(), 'finished_at' => now()]);
            $this->fail($e);
        } catch (BannedException $e) {
            Log::warning('yandex_parser.banned', ['org' => $organization->id]);
            $organization->update(['status' => 'failed', 'last_error' => 'Временная блокировка источником, попробуем позже.']);
            $job->update(['status' => 'failed', 'error' => $e->getMessage()]);
            $this->release(300);
        } catch (SourceUnavailableException $e) {
            Log::warning('yandex_parser.source_unavailable', ['org' => $organization->id, 'message' => $e->getMessage()]);
            $organization->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
            $job->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Organization::whereKey($this->organizationId)->update([
            'status' => 'failed',
            'last_error' => $e->getMessage(),
        ]);
    }

    private function acquireGlobalSlot(): void
    {
        $delayMs = (int) config('yandex-parser.request_delay_ms', 800);
        $lockKey = 'yandex-parser:global-throttle';

        while (! Redis::set($lockKey, 1, 'PX', $delayMs, 'NX')) {
            usleep(50_000);
        }
    }
}
