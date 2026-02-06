<?php

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\Tag;

it('has many articles with confidence pivot', function () {
    $tag = Tag::factory()->create();
    $article = Article::factory()->create();

    $tag->articles()->attach($article->id, ['confidence' => 0.9]);

    expect($tag->articles)->toHaveCount(1);
    expect($tag->articles->first()->pivot->confidence)->toBe(0.9);
});

it('scopes by type', function () {
    Tag::factory()->create(['type' => 'crime']);
    Tag::factory()->create(['type' => 'topic']);

    expect(Tag::type('crime')->count())->toBe(1);
});

it('scopes popular tags', function () {
    Tag::factory()->create(['article_count' => 100]);
    Tag::factory()->create(['article_count' => 50]);
    Tag::factory()->create(['article_count' => 200]);

    $popular = Tag::popular(2)->get();
    expect($popular)->toHaveCount(2);
    expect($popular->first()->article_count)->toBe(200);
});
