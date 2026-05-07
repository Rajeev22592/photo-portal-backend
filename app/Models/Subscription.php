<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan',
        'plan_name',
        'amount',
        'interval',
        'status',
        'expires_at',
        'starts_at',
        'ends_at',
        'max_galleries',
        'max_media_per_gallery',
        'max_media_total',
        'max_face_searches_per_month',
        'face_recognition_enabled',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'face_recognition_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
