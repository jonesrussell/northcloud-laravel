<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use JonesRussell\NorthCloud\Contracts\ArticleModel;

class Article extends Model implements ArticleModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'news_source_id', 'title', 'slug', 'excerpt', 'content',
        'url', 'external_id', 'image_url', 'author', 'status',
        'published_at', 'crawled_at', 'metadata', 'view_count', 'is_featured',
        'articleable_type', 'articleable_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'crawled_at' => 'datetime',
            'metadata' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    public function newsSource(): BelongsTo
    {
        $newsSourceModel = config('northcloud.models.news_source', NewsSource::class);

        return $this->belongsTo($newsSourceModel);
    }

    public function tags(): BelongsToMany
    {
        $tagModel = config('northcloud.models.tag', Tag::class);

        return $this->belongsToMany($tagModel, 'article_tag')
            ->withPivot('confidence')
            ->withTimestamps();
    }

    public function articleable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getExternalId(): string
    {
        return $this->external_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getStatus(): string
    {
        return $this->status ?? 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeWithTag(Builder $query, string $tagSlug): Builder
    {
        return $query->whereHas('tags', fn ($q) => $q->where('slug', $tagSlug));
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);

        return $query->where(function ($q) use ($escaped) {
            $q->where('title', 'LIKE', "%{$escaped}%")
                ->orWhere('excerpt', 'LIKE', "%{$escaped}%")
                ->orWhere('content', 'LIKE', "%{$escaped}%");
        });
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    protected static function newFactory()
    {
        return \JonesRussell\NorthCloud\Database\Factories\ArticleFactory::new();
    }
}
