<?php

use Illuminate\Support\Facades\Event;
use JonesRussell\NorthCloud\Events\ArticleProcessed;
use JonesRussell\NorthCloud\Events\ArticleReceived;
use JonesRussell\NorthCloud\Models\Article;

it('creates ArticleReceived event with data and channel', function () {
    $event = new ArticleReceived(['id' => 'test'], 'crime:homepage');

    expect($event->articleData)->toBe(['id' => 'test']);
    expect($event->channel)->toBe('crime:homepage');
});

it('creates ArticleProcessed event with article model', function () {
    $article = Article::factory()->create();
    $event = new ArticleProcessed($article);

    expect($event->article)->toBeInstanceOf(Article::class);
    expect($event->article->id)->toBe($article->id);
});

it('dispatches ArticleReceived as a standard Laravel event', function () {
    Event::fake();

    ArticleReceived::dispatch(['id' => 'evt-test'], 'test-channel');

    Event::assertDispatched(ArticleReceived::class, function ($event) {
        return $event->articleData['id'] === 'evt-test' && $event->channel === 'test-channel';
    });
});
