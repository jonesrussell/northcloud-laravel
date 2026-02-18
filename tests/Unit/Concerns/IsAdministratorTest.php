<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Tests\Fixtures\User;

it('returns true for admin users via isAdmin()', function () {
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);
    expect($user->isAdmin())->toBeTrue();
});

it('returns false for non-admin users via isAdmin()', function () {
    $user = User::create([
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);
    expect($user->isAdmin())->toBeFalse();
});

it('casts is_admin to boolean automatically', function () {
    $user = User::create([
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
        'is_admin' => 1,
    ]);
    expect($user->is_admin)->toBeBool()->toBeTrue();
});

it('filters admin users with scopeAdmin', function () {
    User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('pw'), 'is_admin' => true]);
    User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);
    $admins = User::admin()->get();
    expect($admins)->toHaveCount(1);
    expect($admins->first()->email)->toBe('admin@test.com');
});

it('filters non-admin users with scopeNonAdmin', function () {
    User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('pw'), 'is_admin' => true]);
    User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);
    $nonAdmins = User::nonAdmin()->get();
    expect($nonAdmins)->toHaveCount(1);
    expect($nonAdmins->first()->email)->toBe('user@test.com');
});

it('defaults is_admin to false', function () {
    $user = User::create([
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
    ]);
    expect($user->fresh()->isAdmin())->toBeFalse();
});
