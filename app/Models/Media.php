<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'gallery_id',
        'path',
        'url',
        'thumbnail_path',
        'thumbnail_url',
        'type',
        'title',
        'description',
        'has_face',
        'face_processed',
        'faces_detected',
    ];

    protected function casts(): array
    {
        return [
            'has_face' => 'boolean',
            'face_processed' => 'boolean',
        ];
    }

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    public function faces(): HasMany
    {
        return $this->hasMany(Face::class, 'media_id');
    }

    public function getFileUrlAttribute(): ?string
    {
        $url = $this->attributes['url'] ?? null;
        return $url ?: ($this->path ? Storage::url($this->path) : null);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        $url = $this->attributes['thumbnail_url'] ?? null;
        return $url ?: ($this->thumbnail_path ? Storage::url($this->thumbnail_path) : null);
    }
}
