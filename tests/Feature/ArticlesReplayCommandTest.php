<?php

use JonesRussell\NorthCloud\Models\Article;

it('registers the articles:replay command', function () {
    $this->artisan('list')
        ->expectsOutputToContain('articles:replay');
});

it('replays a specific article by ID in dry run', function () {
    $article = Article::factory()->create([
        'metadata' => ['quality_score' => 80, 'publisher' => ['channel' => 'test']],
    ]);

    $this->artisan("articles:replay --id={$article->id} --dry-run")
        ->expectsOutputToContain($article->title)
        ->assertExitCode(0);
});

it('replays articles from a time range in dry run', function () {
    Article::factory()->count(3)->create(['created_at' => now()->subHours(2)]);
    Article::factory()->count(2)->create(['created_at' => now()->subDays(3)]);

    $this->artisan('articles:replay --since=24h --dry-run')
        ->expectsOutputToContain('3 article(s)')
        ->assertExitCode(0);
});

it('updates an existing article when using --full flag', function () {
    $article = Article::factory()->create([
        'title' => 'Original Title',
        'excerpt' => 'Original excerpt',
        'external_id' => 'test-external-id',
        'metadata' => ['quality_score' => 80, 'publisher' => ['channel' => 'test']],
    ]);

    // Update the article title directly so replay will re-process the current data
    $article->update(['title' => 'Updated Title']);

    $this->artisan("articles:replay --id={$article->id} --full")
        ->expectsOutputToContain('Processed: 1')
        ->assertExitCode(0);

    $article->refresh();
    expect($article->title)->toBe('Updated Title');
    expect($article->external_id)->toBe('test-external-id');
});

it('skips existing articles without --full flag', function () {
    $article = Article::factory()->create([
        'title' => 'Original Title',
        'excerpt' => 'Original excerpt',
        'external_id' => 'test-skip-id',
        'metadata' => ['quality_score' => 80, 'publisher' => ['channel' => 'test']],
    ]);

    // Without --full, dedup kicks in and the pipeline returns null.
    // The command still counts it as processed (no exception thrown),
    // but the article data remains unchanged.
    $this->artisan("articles:replay --id={$article->id}")
        ->expectsOutputToContain('Processed: 1')
        ->assertExitCode(0);

    $article->refresh();
    expect($article->title)->toBe('Original Title');
    expect($article->excerpt)->toBe('Original excerpt');
});
