<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gallery extends Model
{
    protected $fillable = [
        'tenant_id',
        'client_id',
        'title',
        'name',
        'event_date',
        'cover_image',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_public' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'gallery_id');
    }
}
