<?php

namespace JonesRussell\NorthCloud;

use Illuminate\Support\ServiceProvider;
use JonesRussell\NorthCloud\Admin\ArticleResource;
use JonesRussell\NorthCloud\Services\ArticleIngestionService;
use JonesRussell\NorthCloud\Services\NewsSourceResolver;

class NorthCloudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/northcloud.php', 'northcloud');

        // Deep-merge nested config keys that mergeConfigFrom only shallow-merges.
        // This lets consumer apps override only the admin keys they need.
        $this->deepMergeConfigKey('northcloud.admin');
        $this->deepMergeConfigKey('northcloud.admin.views');

        $this->app->singleton(NewsSourceResolver::class);
        $this->app->singleton(ArticleIngestionService::class);

        $this->app->singleton(ArticleResource::class, function ($app) {
            $resourceClass = config('northcloud.admin.resource', ArticleResource::class);

            return new $resourceClass;
        });
    }

    protected function deepMergeConfigKey(string $key): void
    {
        $packageConfig = require __DIR__.'/../config/northcloud.php';

        // Navigate to the nested key in the package config
        $segments = explode('.', str_replace('northcloud.', '', $key));
        $packageDefaults = $packageConfig;
        foreach ($segments as $segment) {
            $packageDefaults = $packageDefaults[$segment] ?? [];
        }

        if (is_array($packageDefaults)) {
            $current = config($key, []);
            config([$key => array_merge($packageDefaults, $current)]);
        }
    }

    public function boot(): void
    {
        if (config('northcloud.migrations.enabled', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        // Admin routes
        $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        $this->app['router']->aliasMiddleware('northcloud-admin',
            Http\Middleware\EnsureUserIsAdmin::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\SubscribeToArticleFeed::class,
                Console\Commands\ArticlesStatus::class,
                Console\Commands\ArticlesStats::class,
                Console\Commands\ArticlesTestPublish::class,
                Console\Commands\ArticlesReplay::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/northcloud.php' => config_path('northcloud.php'),
            ], 'northcloud-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'northcloud-migrations');

            $this->publishes([
                __DIR__.'/../resources/js/pages/dashboard/articles' => resource_path('js/pages/dashboard/articles'),
            ], 'northcloud-admin-views');

            $this->publishes([
                __DIR__.'/../resources/js/components/admin' => resource_path('js/components/admin'),
            ], 'northcloud-admin-components');

            $this->publishes([
                __DIR__.'/../resources/js/layouts/AdminLayout.vue' => resource_path('js/layouts/AdminLayout.vue'),
            ], 'northcloud-admin-layout');

            $this->publishes([
                __DIR__.'/../database/admin-migrations/2025_01_01_000005_add_is_admin_to_users_table.php' => database_path('migrations/2025_01_01_000005_add_is_admin_to_users_table.php'),
            ], 'northcloud-admin-migrations');
        }
    }
}
