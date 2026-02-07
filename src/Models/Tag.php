<?php

namespace JonesRussell\NorthCloud\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'type', 'color', 'description', 'article_count',
    ];

    public function articles(): BelongsToMany
    {
        $articleModel = config('northcloud.models.article', Article::class);

        return $this->belongsToMany($articleModel, 'article_tag')
            ->withPivot('confidence')
            ->withTimestamps();
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('article_count')->limit($limit);
    }

    protected static function newFactory()
    {
        return \JonesRussell\NorthCloud\Database\Factories\TagFactory::new();
    }
}
