<?php

namespace App\Services\YandexMaps;

use App\Models\Organization;
use App\Models\ParseJob;
use App\Services\YandexMaps\DTO\ParsedOrganization;
use Illuminate\Support\Facades\DB;

class OrganizationSyncService
{
    public function apply(Organization $organization, ParsedOrganization $parsed, ?ParseJob $job = null): void
    {
        DB::transaction(function () use ($organization, $parsed, $job) {
            $added = 0;
            $updated = 0;

            foreach ($parsed->reviews as $review) {
                $hash = $review->contentHash();

                $existing = $organization->reviews()
                    ->where('external_id', $review->externalId)
                    ->first();

                if (! $existing) {
                    $added++;
                } elseif ($existing->content_hash !== $hash) {
                    $updated++;
                }

                $organization->reviews()->updateOrCreate(
                    ['external_id' => $review->externalId],
                    [
                        'author' => $review->author,
                        'author_avatar_url' => $review->authorAvatarUrl,
                        'rating' => $review->rating,
                        'text' => $review->text,
                        'published_at' => $review->publishedAt,
                        'content_hash' => $hash,
                    ]
                );
            }

            $organization->update([
                'name' => $parsed->name ?? $organization->name,
                'address' => $parsed->address ?? $organization->address,
                'rating' => $parsed->rating,
                'ratings_count' => $parsed->ratingsCount,
                'reviews_count' => $parsed->reviewsCount,
                'status' => 'done',
                'progress' => 100,
                'last_error' => null,
                'last_parsed_at' => now(),
            ]);

            $organization->snapshots()->create([
                'parse_job_id' => $job?->id,
                'rating' => $parsed->rating,
                'ratings_count' => $parsed->ratingsCount,
                'reviews_count' => $parsed->reviewsCount,
                'reviews_added' => $added,
                'reviews_updated' => $updated,
            ]);
        });
    }
}
