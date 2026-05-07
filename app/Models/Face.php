<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Face extends Model
{
    protected $fillable = [
        'media_id',
        'x',
        'y',
        'width',
        'height',
        'confidence',
        'bounding_box',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'bounding_box' => 'array',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
