<?php

namespace App\Models;

use App\Traits\HashTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    /** @use HasFactory<\Database\Factories\PromotionFactory> */
    use HasFactory, HashTrait;

    protected $fillable = [
        'title',
        'excerpt',
        'thumbnail',
        'content',
        'type',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @param Builder<Promotion> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }
}
