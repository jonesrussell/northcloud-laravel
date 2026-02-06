<?php

namespace JonesRussell\NorthCloud\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JonesRussell\NorthCloud\Database\Factories\TagFactory;

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

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderByDesc('article_count')->limit($limit);
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }
}
