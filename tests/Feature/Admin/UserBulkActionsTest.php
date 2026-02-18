<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Tests\Fixtures\User;

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);
});

it('bulk deletes selected users', function () {
    $user1 = User::create([
        'name' => 'User One',
        'email' => 'user1@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $user2 = User::create([
        'name' => 'User Two',
        'email' => 'user2@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $this->actingAs($this->admin)
        ->post(route('dashboard.users.bulk-delete'), [
            'ids' => [$user1->id, $user2->id],
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $this->assertDatabaseMissing('users', ['email' => 'user1@test.com']);
    $this->assertDatabaseMissing('users', ['email' => 'user2@test.com']);
});

it('validates ids required for bulk delete', function () {
    $this->actingAs($this->admin)
        ->post(route('dashboard.users.bulk-delete'), [])
        ->assertSessionHasErrors(['ids']);
});

it('bulk grants admin access', function () {
    $user1 = User::create([
        'name' => 'User One',
        'email' => 'user1@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $user2 = User::create([
        'name' => 'User Two',
        'email' => 'user2@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $this->actingAs($this->admin)
        ->post(route('dashboard.users.bulk-toggle-admin'), [
            'ids' => [$user1->id, $user2->id],
            'is_admin' => true,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user1->refresh();
    $user2->refresh();

    expect($user1->is_admin)->toBeTrue();
    expect($user2->is_admin)->toBeTrue();
});

it('bulk revokes admin access', function () {
    $user1 = User::create([
        'name' => 'User One',
        'email' => 'user1@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);

    $user2 = User::create([
        'name' => 'User Two',
        'email' => 'user2@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);

    $this->actingAs($this->admin)
        ->post(route('dashboard.users.bulk-toggle-admin'), [
            'ids' => [$user1->id, $user2->id],
            'is_admin' => false,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user1->refresh();
    $user2->refresh();

    expect($user1->is_admin)->toBeFalse();
    expect($user2->is_admin)->toBeFalse();
});

it('toggles admin status on for a single user', function () {
    $user = User::create([
        'name' => 'Regular User',
        'email' => 'regular@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $this->actingAs($this->admin)
        ->post(route('dashboard.users.toggle-admin', $user))
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();
    expect($user->is_admin)->toBeTrue();
});

it('toggles admin status off for a single user', function () {
    $user = User::create([
        'name' => 'Admin User',
        'email' => 'adminuser@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);

    $this->actingAs($this->admin)
        ->post(route('dashboard.users.toggle-admin', $user))
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();
    expect($user->is_admin)->toBeFalse();
});

it('bulk toggle admin filters out current user', function () {
    $otherUser = User::create([
        'name' => 'Other User',
        'email' => 'other@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);

    $this->actingAs($this->admin)
        ->post(route('dashboard.users.bulk-toggle-admin'), [
            'ids' => [$this->admin->id, $otherUser->id],
            'is_admin' => false,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $this->admin->refresh();
    $otherUser->refresh();

    // Admin should still be admin (filtered out), other user should be revoked
    expect($this->admin->is_admin)->toBeTrue();
    expect($otherUser->is_admin)->toBeFalse();
});
