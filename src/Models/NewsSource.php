<?php

namespace JonesRussell\NorthCloud\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JonesRussell\NorthCloud\Database\Factories\NewsSourceFactory;

class NewsSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'url', 'logo_url', 'description',
        'credibility_score', 'bias_rating', 'factual_reporting_score',
        'ownership', 'country', 'is_active', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function articles(): HasMany
    {
        $articleModel = config('northcloud.models.article', Article::class);

        return $this->hasMany($articleModel);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): NewsSourceFactory
    {
        return NewsSourceFactory::new();
    }
}
