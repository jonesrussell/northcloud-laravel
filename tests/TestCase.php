<?php

namespace JonesRussell\NorthCloud\Tests;

use JonesRussell\NorthCloud\NorthCloudServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            NorthCloudServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('auth.providers.users.model', \JonesRussell\NorthCloud\Tests\Fixtures\User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->createUsersTable();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/admin-migrations');
    }

    protected function createUsersTable(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
