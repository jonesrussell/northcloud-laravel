<?php

use Illuminate\Database\Eloquent\Relations\MorphTo;
use JonesRussell\NorthCloud\Models\Article;

it('has an articleable morph-to relationship', function () {
    $article = new Article;

    expect($article->articleable())->toBeInstanceOf(MorphTo::class);
});

it('allows null articleable for standalone articles', function () {
    $article = Article::factory()->create();

    expect($article->articleable)->toBeNull();
    expect($article->articleable_type)->toBeNull();
    expect($article->articleable_id)->toBeNull();
});
