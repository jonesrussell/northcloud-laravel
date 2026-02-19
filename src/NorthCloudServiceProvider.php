<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use JonesRussell\NorthCloud\Admin\ArticleResource;
use JonesRussell\NorthCloud\Admin\UserResource;
use JonesRussell\NorthCloud\Mail\Transport\SendGridTransport;
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
        $this->deepMergeConfigKey('northcloud.users');
        $this->deepMergeConfigKey('northcloud.users.views');
        $this->deepMergeConfigKey('northcloud.articleable');

        $this->registerRedisConnection();

        $this->app->singleton(NorthCloud::class);
        $this->app->singleton(NewsSourceResolver::class);
        $this->app->singleton(ArticleIngestionService::class);

        $this->app->singleton(ArticleResource::class, function ($app) {
            $resourceClass = config('northcloud.admin.resource', ArticleResource::class);

            return new $resourceClass;
        });

        $this->app->singleton(UserResource::class, function ($app) {
            $resourceClass = config('northcloud.users.resource', UserResource::class);

            return new $resourceClass;
        });
    }

    public function boot(): void
    {
        $this->registerSendGridTransport();

        if (config('northcloud.migrations.enabled', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/users.php');
        $this->app['router']->aliasMiddleware('northcloud-admin',
            Http\Middleware\EnsureUserIsAdmin::class);

        app(Support\ConfigValidator::class)->validate();

        $this->shareNavigation();

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerPublishableAssets();
        }
    }

    protected function registerRedisConnection(): void
    {
        $connectionName = config('northcloud.redis.connection', 'northcloud');

        // Don't override if the app already defines this connection
        if (config("database.redis.{$connectionName}")) {
            return;
        }

        config(["database.redis.{$connectionName}" => [
            'prefix' => '',
            'url' => env('NORTHCLOUD_REDIS_URL'),
            'host' => env('NORTHCLOUD_REDIS_HOST', env('REDIS_HOST', '127.0.0.1')),
            'username' => env('NORTHCLOUD_REDIS_USERNAME', env('REDIS_USERNAME')),
            'password' => env('NORTHCLOUD_REDIS_PASSWORD', env('REDIS_PASSWORD')),
            'port' => env('NORTHCLOUD_REDIS_PORT', env('REDIS_PORT', '6379')),
            'database' => env('NORTHCLOUD_REDIS_DB', '0'),
            'read_timeout' => env('NORTHCLOUD_REDIS_READ_TIMEOUT', 30),
        ]]);
    }

    protected function registerSendGridTransport(): void
    {
        $apiKey = config('northcloud.mail.sendgrid.api_key');

        if (! $apiKey) {
            return;
        }

        Mail::extend('sendgrid', function () use ($apiKey) {
            return new SendGridTransport(new \SendGrid($apiKey));
        });

        config(['mail.mailers.sendgrid' => ['transport' => 'sendgrid']]);

        if (config('northcloud.mail.sendgrid.set_as_default', true)) {
            config(['mail.default' => 'sendgrid']);
        }
    }

    protected function shareNavigation(): void
    {
        if (! config('northcloud.navigation.enabled', true)) {
            return;
        }

        if (! class_exists(Inertia::class)) {
            return;
        }

        Inertia::share('northcloud', fn () => [
            'navigation' => collect(config('northcloud.navigation.items', []))
                ->merge(app(NorthCloud::class)->getRegisteredNavigation())
                ->map(fn (array $item) => [
                    'title' => $item['title'],
                    'href' => route($item['route']),
                    'icon' => $item['icon'],
                ])
                ->all(),
        ]);
    }

    protected function registerCommands(): void
    {
        $this->commands([
            Console\Commands\NorthCloudDoctor::class,
            Console\Commands\NorthCloudInstall::class,
            Console\Commands\SubscribeToArticleFeed::class,
            Console\Commands\ArticlesStatus::class,
            Console\Commands\ArticlesStats::class,
            Console\Commands\ArticlesTestPublish::class,
            Console\Commands\ArticlesReplay::class,
        ]);
    }

    protected function registerPublishableAssets(): void
    {
        $this->publishes([
            __DIR__.'/../config/northcloud.php' => config_path('northcloud.php'),
        ], 'northcloud-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'northcloud-migrations');

        $this->publishes([
            __DIR__.'/../database/admin-migrations/2025_01_01_000005_add_is_admin_to_users_table.php' => database_path('migrations/2025_01_01_000005_add_is_admin_to_users_table.php'),
        ], 'northcloud-admin-migrations');

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
            __DIR__.'/../resources/js/pages/dashboard/users' => resource_path('js/pages/dashboard/users'),
        ], 'northcloud-user-views');

        $this->publishes([
            __DIR__.'/../resources/js/components/admin/UserForm.vue' => resource_path('js/components/admin/UserForm.vue'),
            __DIR__.'/../resources/js/components/admin/UsersTable.vue' => resource_path('js/components/admin/UsersTable.vue'),
        ], 'northcloud-user-components');

        $this->publishes([
            __DIR__.'/../resources/js/components/ui' => resource_path('js/components/ui'),
        ], 'northcloud-ui-components');
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
}
