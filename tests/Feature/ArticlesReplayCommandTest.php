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
