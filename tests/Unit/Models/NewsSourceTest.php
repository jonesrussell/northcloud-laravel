<?php

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;

it('has many articles', function () {
    $source = NewsSource::factory()->create();
    Article::factory()->for($source, 'newsSource')->count(3)->create();

    expect($source->articles)->toHaveCount(3);
});

it('scopes to active sources', function () {
    NewsSource::factory()->create(['is_active' => true]);
    NewsSource::factory()->create(['is_active' => false]);

    expect(NewsSource::active()->count())->toBe(1);
});

it('casts metadata to array', function () {
    $source = NewsSource::factory()->create(['metadata' => ['key' => 'value']]);

    expect($source->metadata)->toBeArray();
});
