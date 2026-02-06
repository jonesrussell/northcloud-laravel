<?php

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;
use JonesRussell\NorthCloud\Models\Tag;

it('belongs to a news source', function () {
    $source = NewsSource::factory()->create();
    $article = Article::factory()->for($source, 'newsSource')->create();

    expect($article->newsSource)->toBeInstanceOf(NewsSource::class);
    expect($article->newsSource->id)->toBe($source->id);
});

it('has many tags with confidence pivot', function () {
    $article = Article::factory()->create();
    $tag = Tag::factory()->create();

    $article->tags()->attach($tag->id, ['confidence' => 0.85]);

    expect($article->tags)->toHaveCount(1);
    expect($article->tags->first()->pivot->confidence)->toBe(0.85);
});

it('scopes to published articles', function () {
    Article::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
    Article::factory()->create(['published_at' => null, 'status' => 'draft']);

    expect(Article::published()->count())->toBe(1);
});

it('scopes to featured articles', function () {
    Article::factory()->create(['is_featured' => true]);
    Article::factory()->create(['is_featured' => false]);

    expect(Article::featured()->count())->toBe(1);
});

it('casts metadata to array', function () {
    $article = Article::factory()->create(['metadata' => ['quality_score' => 85]]);

    expect($article->metadata)->toBeArray();
    expect($article->metadata['quality_score'])->toBe(85);
});

it('uses soft deletes', function () {
    $article = Article::factory()->create();
    $article->delete();

    expect(Article::count())->toBe(0);
    expect(Article::withTrashed()->count())->toBe(1);
});

it('implements ArticleModel contract', function () {
    $article = Article::factory()->create([
        'external_id' => 'ext-123',
        'title' => 'Test Title',
        'url' => 'https://example.com/test',
        'status' => 'published',
        'published_at' => now(),
    ]);

    expect($article->getExternalId())->toBe('ext-123');
    expect($article->getTitle())->toBe('Test Title');
    expect($article->getUrl())->toBe('https://example.com/test');
    expect($article->getStatus())->toBe('published');
    expect($article->isPublished())->toBeTrue();
});

it('searches articles by keyword', function () {
    Article::factory()->create(['title' => 'Crime wave hits downtown']);
    Article::factory()->create(['title' => 'Weather forecast sunny']);

    expect(Article::search('crime')->count())->toBe(1);
});
