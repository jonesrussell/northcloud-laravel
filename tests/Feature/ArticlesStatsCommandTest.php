<?php

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;
use JonesRussell\NorthCloud\Models\Tag;

it('registers the articles:stats command', function () {
    $this->artisan('list')
        ->expectsOutputToContain('articles:stats');
});

it('displays total article count', function () {
    Article::factory()->count(5)->create();

    $this->artisan('articles:stats')
        ->expectsOutputToContain('5');
});

it('displays articles by source', function () {
    $source = NewsSource::factory()->create(['name' => 'Toronto Star']);
    Article::factory()->for($source, 'newsSource')->count(3)->create();

    Illuminate\Support\Facades\Artisan::call('articles:stats', ['--sources' => true]);
    $output = Illuminate\Support\Facades\Artisan::output();

    expect($output)->toContain('Toronto Star');
    expect($output)->toContain('3');
});

it('outputs JSON format', function () {
    Article::factory()->count(2)->create();

    $this->artisan('articles:stats --json')
        ->expectsOutputToContain('"total"');
});
