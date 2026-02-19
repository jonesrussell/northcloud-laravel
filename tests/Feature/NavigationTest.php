<?php

declare(strict_types=1);

use Inertia\ResponseFactory;
use JonesRussell\NorthCloud\Facades\NorthCloud;

it('shares config-based navigation items via Inertia', function () {
    config()->set('northcloud.navigation.items', [
        ['title' => 'Articles', 'route' => 'dashboard.articles.index', 'icon' => 'FileText'],
    ]);

    \Illuminate\Support\Facades\Route::get('/dashboard/articles', fn () => 'ok')
        ->name('dashboard.articles.index')
        ->middleware('web');

    $shared = app(ResponseFactory::class)->getShared();
    $northcloud = value($shared['northcloud']);

    expect($northcloud['navigation'])->toHaveCount(1);
    expect($northcloud['navigation'][0]['title'])->toBe('Articles');
});

it('merges programmatically registered navigation items', function () {
    config()->set('northcloud.navigation.items', [
        ['title' => 'Articles', 'route' => 'dashboard.articles.index', 'icon' => 'FileText'],
    ]);

    \Illuminate\Support\Facades\Route::get('/dashboard/articles', fn () => 'ok')
        ->name('dashboard.articles.index')
        ->middleware('web');
    \Illuminate\Support\Facades\Route::get('/dashboard/movies', fn () => 'ok')
        ->name('dashboard.movies')
        ->middleware('web');

    NorthCloud::registerNavigation([
        ['title' => 'Movies', 'route' => 'dashboard.movies', 'icon' => 'Film'],
    ]);

    $shared = app(ResponseFactory::class)->getShared();
    $northcloud = value($shared['northcloud']);

    expect($northcloud['navigation'])->toHaveCount(2);
    expect($northcloud['navigation'][1]['title'])->toBe('Movies');
});
