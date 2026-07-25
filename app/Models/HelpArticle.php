<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpArticle extends Model
{
    protected $fillable = [
        'slug', 'title', 'category', 'summary', 'content',
        'video_url', 'tags', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return ['tags' => 'array', 'is_published' => 'boolean'];
    }

    public function scopePublished($query) { return $query->where('is_published', true); }

    public function scopeSearch($query, string $term)
    {
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);
        return $query->where(fn ($q) => $q
            ->where('title', 'ilike', "%{$escaped}%")
            ->orWhere('summary', 'ilike', "%{$escaped}%")
            ->orWhere('content', 'ilike', "%{$escaped}%")
        );
    }
}
