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

it('prevents admin from deleting self', function () {
    $this->actingAs($this->admin)
        ->delete(route('dashboard.users.destroy', $this->admin))
        ->assertForbidden();

    $this->assertDatabaseHas('users', [
        'email' => 'admin@test.com',
    ]);
});

it('prevents admin from toggling own admin status', function () {
    $this->actingAs($this->admin)
        ->post(route('dashboard.users.toggle-admin', $this->admin))
        ->assertForbidden();

    $this->admin->refresh();
    expect($this->admin->is_admin)->toBeTrue();
});

it('bulk delete filters out current user', function () {
    $otherUser = User::create([
        'name' => 'Other',
        'email' => 'other@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $this->actingAs($this->admin)
        ->post(route('dashboard.users.bulk-delete'), [
            'ids' => [$this->admin->id, $otherUser->id],
        ])
        ->assertRedirect(route('dashboard.users.index'));

    // Other user should be deleted, admin should remain
    $this->assertDatabaseMissing('users', ['email' => 'other@test.com']);
    $this->assertDatabaseHas('users', ['email' => 'admin@test.com']);
});

it('bulk delete returns warning when only contains current user', function () {
    $this->actingAs($this->admin)
        ->post(route('dashboard.users.bulk-delete'), [
            'ids' => [$this->admin->id],
        ])
        ->assertRedirect(route('dashboard.users.index'))
        ->assertSessionHas('warning');

    $this->assertDatabaseHas('users', ['email' => 'admin@test.com']);
});

it('update silently ignores is_admin change for self', function () {
    $this->actingAs($this->admin)
        ->put(route('dashboard.users.update', $this->admin), [
            'name' => 'Updated Admin',
            'email' => 'admin@test.com',
            'is_admin' => false,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $this->admin->refresh();
    expect($this->admin->name)->toBe('Updated Admin');
    expect($this->admin->is_admin)->toBeTrue();
});
