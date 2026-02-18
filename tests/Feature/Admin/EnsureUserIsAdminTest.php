<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Tests\Fixtures\User;

it('blocks non-admin users with 403', function () {
    $user = User::create([
        'name' => 'User',
        'email' => 'user@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);
    $response = $this->actingAs($user)->get('/dashboard/articles');
    $response->assertForbidden();
});

it('uses isAdmin() method when trait is applied', function () {
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);
    expect(method_exists($admin, 'isAdmin'))->toBeTrue();
    expect($admin->isAdmin())->toBeTrue();
});
