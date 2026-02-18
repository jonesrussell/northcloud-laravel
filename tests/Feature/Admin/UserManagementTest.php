<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use JonesRussell\NorthCloud\Tests\Fixtures\User;

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);
});

// ── Authorization ──────────────────────────────────────────────────────

it('redirects guests to login', function () {
    $this->get(route('dashboard.users.index'))
        ->assertRedirect('/login');
});

it('returns 403 for non-admin users', function () {
    $user = User::create([
        'name' => 'Regular',
        'email' => 'regular@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.users.index'))
        ->assertForbidden();
});

// ── Index ──────────────────────────────────────────────────────────────

it('admin can list users', function () {
    User::create([
        'name' => 'Another User',
        'email' => 'another@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('dashboard.users.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard/users/Index')
            ->has('users.data', 2)
            ->has('stats')
            ->has('filters')
            ->has('columns')
        );
});

it('admin can filter users by search', function () {
    User::create([
        'name' => 'John Smith',
        'email' => 'john@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('dashboard.users.index', ['search' => 'John']))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard/users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'John Smith')
        );
});

it('admin can filter users by admin status', function () {
    User::create([
        'name' => 'Regular User',
        'email' => 'regular@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    // Filter admins only - should just be the admin from beforeEach
    $this->actingAs($this->admin)
        ->get(route('dashboard.users.index', ['admin_status' => 'admin']))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard/users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.email', 'admin@test.com')
        );

    // Filter non-admins only
    $this->actingAs($this->admin)
        ->get(route('dashboard.users.index', ['admin_status' => 'non-admin']))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard/users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.email', 'regular@test.com')
        );
});

// ── Create / Store ─────────────────────────────────────────────────────

it('admin can view create form', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard.users.create'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard/users/Create')
            ->has('fields')
        );
});

it('admin can create a user with valid data', function () {
    $this->actingAs($this->admin)
        ->post(route('dashboard.users.store'), [
            'name' => 'New User',
            'email' => 'new@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => false,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'new@test.com',
        'is_admin' => false,
    ]);
});

it('validates required fields when storing a user', function () {
    $this->actingAs($this->admin)
        ->post(route('dashboard.users.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

it('validates email uniqueness when storing a user', function () {
    $this->actingAs($this->admin)
        ->post(route('dashboard.users.store'), [
            'name' => 'Duplicate',
            'email' => 'admin@test.com', // already exists
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors(['email']);
});

it('validates password minimum length', function () {
    $this->actingAs($this->admin)
        ->post(route('dashboard.users.store'), [
            'name' => 'Short Pass',
            'email' => 'shortpass@test.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors(['password']);
});

it('hashes the password when creating a user', function () {
    $this->actingAs($this->admin)
        ->post(route('dashboard.users.store'), [
            'name' => 'Hashed User',
            'email' => 'hashed@test.com',
            'password' => 'plaintext123',
            'password_confirmation' => 'plaintext123',
            'is_admin' => false,
        ]);

    $user = User::where('email', 'hashed@test.com')->first();

    expect($user)->not->toBeNull();
    expect($user->password)->not->toBe('plaintext123');
    expect(Hash::check('plaintext123', $user->password))->toBeTrue();
});

// ── Show ───────────────────────────────────────────────────────────────

it('admin can view user details', function () {
    $user = User::create([
        'name' => 'Viewable User',
        'email' => 'viewable@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('dashboard.users.show', $user))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard/users/Show')
            ->has('user')
            ->where('user.email', 'viewable@test.com')
        );
});

// ── Edit / Update ──────────────────────────────────────────────────────

it('admin can view edit form for another user', function () {
    $user = User::create([
        'name' => 'Editable User',
        'email' => 'editable@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('dashboard.users.edit', $user))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard/users/Edit')
            ->has('user')
            ->where('isSelf', false)
        );
});

it('admin can view edit form for self with isSelf true', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard.users.edit', $this->admin))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard/users/Edit')
            ->has('user')
            ->where('isSelf', true)
        );
});

it('admin can update a user without changing password', function () {
    $user = User::create([
        'name' => 'Original Name',
        'email' => 'original@test.com',
        'password' => bcrypt('originalpass'),
        'is_admin' => false,
    ]);

    $originalPassword = $user->password;

    $this->actingAs($this->admin)
        ->put(route('dashboard.users.update', $user), [
            'name' => 'Updated Name',
            'email' => 'original@test.com',
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->password)->toBe($originalPassword);
});

it('admin can update a user with a new password', function () {
    $user = User::create([
        'name' => 'Password User',
        'email' => 'passuser@test.com',
        'password' => bcrypt('oldpassword'),
        'is_admin' => false,
    ]);

    $oldPassword = $user->password;

    $this->actingAs($this->admin)
        ->put(route('dashboard.users.update', $user), [
            'name' => 'Password User',
            'email' => 'passuser@test.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();

    expect($user->password)->not->toBe($oldPassword);
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
});

// ── Delete ─────────────────────────────────────────────────────────────

it('admin can delete a user', function () {
    $user = User::create([
        'name' => 'Deletable User',
        'email' => 'deletable@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('dashboard.users.destroy', $user))
        ->assertRedirect(route('dashboard.users.index'));

    $this->assertDatabaseMissing('users', [
        'email' => 'deletable@test.com',
    ]);
});
