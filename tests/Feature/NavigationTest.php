<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\ResponseFactory;
use JonesRussell\NorthCloud\Facades\NorthCloud;
use JonesRussell\NorthCloud\Tests\Fixtures\User;

function setRequestWithUser(?User $user): void
{
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);
    app()->instance('request', $request);
    if ($user !== null) {
        Auth::guard()->setUser($user);
    } else {
        Auth::logout();
    }
}

it('shares config-based navigation items via Inertia when user is admin', function () {
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);
    setRequestWithUser($admin);

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

it('merges programmatically registered navigation items when user is admin', function () {
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin2@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);
    setRequestWithUser($admin);

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

it('shares empty navigation when user is not admin', function () {
    $user = User::create([
        'name' => 'User',
        'email' => 'user@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);
    setRequestWithUser($user);

    config()->set('northcloud.navigation.items', [
        ['title' => 'Articles', 'route' => 'dashboard.articles.index', 'icon' => 'FileText'],
    ]);
    \Illuminate\Support\Facades\Route::get('/dashboard/articles', fn () => 'ok')
        ->name('dashboard.articles.index')
        ->middleware('web');

    $shared = app(ResponseFactory::class)->getShared();
    $northcloud = value($shared['northcloud']);

    expect($northcloud['navigation'])->toBeArray();
    expect($northcloud['navigation'])->toHaveCount(0);
});

it('shares empty navigation when user is unauthenticated', function () {
    setRequestWithUser(null);

    config()->set('northcloud.navigation.items', [
        ['title' => 'Articles', 'route' => 'dashboard.articles.index', 'icon' => 'FileText'],
    ]);
    \Illuminate\Support\Facades\Route::get('/dashboard/articles', fn () => 'ok')
        ->name('dashboard.articles.index')
        ->middleware('web');

    $shared = app(ResponseFactory::class)->getShared();
    $northcloud = value($shared['northcloud']);

    expect($northcloud['navigation'])->toBeArray();
    expect($northcloud['navigation'])->toHaveCount(0);
});
