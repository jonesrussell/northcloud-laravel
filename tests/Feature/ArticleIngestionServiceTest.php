<?php

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;
use JonesRussell\NorthCloud\Models\Tag;
use JonesRussell\NorthCloud\Services\ArticleIngestionService;

beforeEach(function () {
    $this->service = app(ArticleIngestionService::class);
    $this->validData = [
        'id' => 'ext-article-001',
        'title' => 'Test Crime Article',
        'canonical_url' => 'https://www.torontostar.com/article/test-123',
        'source' => 'https://www.torontostar.com',
        'published_date' => '2026-01-15T10:30:00Z',
        'publisher' => [
            'route_id' => 'route-abc',
            'published_at' => '2026-01-15T10:30:00Z',
            'channel' => 'crime:homepage',
        ],
        'intro' => 'A test excerpt for the article.',
        'body' => '<p>Article body content here.</p>',
        'topics' => ['violent-crime', 'theft'],
        'quality_score' => 85,
    ];
});

it('creates an article from valid data', function () {
    $article = $this->service->ingest($this->validData);

    expect($article)->toBeInstanceOf(Article::class);
    expect($article->title)->toBe('Test Crime Article');
    expect($article->external_id)->toBe('ext-article-001');
    expect($article->excerpt)->toBe('A test excerpt for the article.');
    expect($article->content)->toBe('<p>Article body content here.</p>');
    expect($article->status)->toBe('published');
    expect($article->published_at)->not->toBeNull();
});

it('creates a news source from the article URL', function () {
    $this->service->ingest($this->validData);

    expect(NewsSource::count())->toBe(1);
    expect(NewsSource::first()->slug)->toBe('torontostar-com');
});

it('attaches tags from topics array', function () {
    $article = $this->service->ingest($this->validData);

    expect($article->tags)->toHaveCount(2);
    expect($article->tags->pluck('slug')->sort()->values()->all())->toBe(['theft', 'violent-crime']);
});

it('deduplicates by external_id', function () {
    $this->service->ingest($this->validData);
    $second = $this->service->ingest($this->validData);

    expect($second)->toBeNull();
    expect(Article::count())->toBe(1);
});

it('stores metadata from publisher data', function () {
    $article = $this->service->ingest($this->validData);

    expect($article->metadata)->toBeArray();
    expect($article->metadata['quality_score'])->toBe(85);
    expect($article->metadata['publisher']['channel'])->toBe('crime:homepage');
});

it('sanitizes HTML content', function () {
    $data = array_merge($this->validData, [
        'id' => 'ext-sanitize-test',
        'body' => '<p>Safe</p><script>alert("xss")</script><div>Stripped</div>',
        'canonical_url' => 'https://example.com/sanitize-test',
    ]);

    $article = $this->service->ingest($data);

    expect($article->content)->not->toContain('<script>');
    expect($article->content)->not->toContain('<div>');
    expect($article->content)->toContain('<p>Safe</p>');
});

it('falls back to og_title when title is missing', function () {
    $data = $this->validData;
    unset($data['title']);
    $data['id'] = 'ext-og-title-test';
    $data['og_title'] = 'OG Title Fallback';
    $data['canonical_url'] = 'https://example.com/og-title-test';

    $article = $this->service->ingest($data);

    expect($article->title)->toBe('OG Title Fallback');
});

it('returns null for invalid data', function () {
    $result = $this->service->ingest(['garbage' => 'data']);

    expect($result)->toBeNull();
});
