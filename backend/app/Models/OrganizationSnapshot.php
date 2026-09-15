<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id', 'parse_job_id', 'rating', 'ratings_count',
        'reviews_count', 'reviews_added', 'reviews_updated',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
