<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'source_url', 'yandex_org_id', 'name', 'address',
        'rating', 'ratings_count', 'reviews_count',
        'status', 'progress', 'last_error', 'last_parsed_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:2',
            'last_parsed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function parseJobs(): HasMany
    {
        return $this->hasMany(ParseJob::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(OrganizationSnapshot::class);
    }

    public function latestParseJob(): ?ParseJob
    {
        return $this->parseJobs()->latest()->first();
    }
}
