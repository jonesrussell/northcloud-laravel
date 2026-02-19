<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;
use JonesRussell\NorthCloud\Models\Tag;

it('resolves Article factory', function () {
    $article = Article::factory()->make();
    expect($article)->toBeInstanceOf(Article::class);
    expect($article->title)->toBeString();
});

it('resolves NewsSource factory', function () {
    $source = NewsSource::factory()->make();
    expect($source)->toBeInstanceOf(NewsSource::class);
    expect($source->name)->toBeString();
});

it('resolves Tag factory', function () {
    $tag = Tag::factory()->make();
    expect($tag)->toBeInstanceOf(Tag::class);
    expect($tag->name)->toBeString();
});
