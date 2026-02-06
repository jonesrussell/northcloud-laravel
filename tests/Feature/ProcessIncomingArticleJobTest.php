<?php

use Illuminate\Support\Facades\Event;
use JonesRussell\NorthCloud\Events\ArticleProcessed;
use JonesRussell\NorthCloud\Jobs\ProcessIncomingArticle;
use JonesRussell\NorthCloud\Models\Article;

it('processes valid article data into a database record', function () {
    $data = [
        'id' => 'job-test-001',
        'title' => 'Job Test Article',
        'canonical_url' => 'https://example.com/job-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
        'body' => '<p>Content</p>',
        'topics' => ['crime'],
    ];

    ProcessIncomingArticle::dispatchSync($data);

    expect(Article::count())->toBe(1);
    expect(Article::first()->title)->toBe('Job Test Article');
});

it('fires ArticleProcessed event after processing', function () {
    Event::fake([ArticleProcessed::class]);

    $data = [
        'id' => 'job-event-001',
        'title' => 'Event Test',
        'canonical_url' => 'https://example.com/event-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
    ];

    ProcessIncomingArticle::dispatchSync($data);

    Event::assertDispatched(ArticleProcessed::class);
});

it('does not fire event when article is skipped', function () {
    Event::fake([ArticleProcessed::class]);

    $data = [
        'id' => 'job-dup-001',
        'title' => 'Duplicate Test',
        'canonical_url' => 'https://example.com/dup-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
    ];

    // First call creates the article
    ProcessIncomingArticle::dispatchSync($data);
    Event::assertDispatchedTimes(ArticleProcessed::class, 1);

    // Second call should be skipped (duplicate)
    ProcessIncomingArticle::dispatchSync($data);
    Event::assertDispatchedTimes(ArticleProcessed::class, 1);
});
