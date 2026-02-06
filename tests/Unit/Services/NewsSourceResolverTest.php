<?php

use JonesRussell\NorthCloud\Models\NewsSource;
use JonesRussell\NorthCloud\Services\NewsSourceResolver;

it('creates a new source from URL', function () {
    $resolver = app(NewsSourceResolver::class);
    $source = $resolver->resolve('https://www.torontostar.com/article/123');

    expect($source)->toBeInstanceOf(NewsSource::class);
    expect($source->slug)->toBe('torontostar-com');
    expect($source->name)->toBe('Torontostar.com');
    expect($source->url)->toBe('https://www.torontostar.com');
});

it('returns existing source on subsequent calls', function () {
    $resolver = app(NewsSourceResolver::class);
    $first = $resolver->resolve('https://www.cbc.ca/news/article-1');
    $second = $resolver->resolve('https://www.cbc.ca/news/article-2');

    expect($first->id)->toBe($second->id);
    expect(NewsSource::count())->toBe(1);
});

it('extracts domain from canonical_url', function () {
    $resolver = app(NewsSourceResolver::class);
    $source = $resolver->resolveFromData([
        'canonical_url' => 'https://globalnews.ca/story/123',
    ]);

    expect($source->slug)->toBe('globalnews-ca');
});

it('falls back through URL fields', function () {
    $resolver = app(NewsSourceResolver::class);

    // No canonical_url, use og_url
    $source = $resolver->resolveFromData([
        'og_url' => 'https://www.reuters.com/article/123',
    ]);
    expect($source->slug)->toBe('reuters-com');

    // No og_url, use source
    $source2 = $resolver->resolveFromData([
        'source' => 'https://www.bbc.com',
    ]);
    expect($source2->slug)->toBe('bbc-com');
});

it('creates unknown source when no URL available', function () {
    $resolver = app(NewsSourceResolver::class);
    $source = $resolver->resolveFromData([]);

    expect($source->slug)->toBe('unknown');
    expect($source->name)->toBe('Unknown Source');
});
