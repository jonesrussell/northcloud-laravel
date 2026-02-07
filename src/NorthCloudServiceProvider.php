<?php

namespace JonesRussell\NorthCloud;

use Illuminate\Support\ServiceProvider;

class NorthCloudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/northcloud.php', 'northcloud');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\SubscribeToArticleFeed::class,
                Console\Commands\ArticlesStatus::class,
                Console\Commands\ArticlesStats::class,
                Console\Commands\ArticlesTestPublish::class,
                Console\Commands\ArticlesReplay::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/northcloud.php' => config_path('northcloud.php'),
            ], 'northcloud-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'northcloud-migrations');
        }
    }
}
