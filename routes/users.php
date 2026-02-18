<?php

use Illuminate\Support\Facades\Route;

if (! config('northcloud.users.enabled', true)) {
    return;
}

$middleware = config('northcloud.users.middleware', ['web', 'auth', 'northcloud-admin']);
$prefix = config('northcloud.users.prefix', 'dashboard/users');
$namePrefix = config('northcloud.users.name_prefix', 'dashboard.users.');
$controller = config('northcloud.users.controller');

Route::middleware($middleware)->prefix($prefix)->name($namePrefix)->group(function () use ($controller) {
    // Bulk actions (must be before {user} routes)
    Route::post('bulk-delete', [$controller, 'bulkDelete'])->name('bulk-delete');
    Route::post('bulk-toggle-admin', [$controller, 'bulkToggleAdmin'])->name('bulk-toggle-admin');
    Route::post('{user}/toggle-admin', [$controller, 'toggleAdmin'])->name('toggle-admin');

    // Resource routes (explicit to avoid Route::resource naming issues)
    Route::get('/', [$controller, 'index'])->name('index');
    Route::get('create', [$controller, 'create'])->name('create');
    Route::post('/', [$controller, 'store'])->name('store');
    Route::get('{user}', [$controller, 'show'])->name('show');
    Route::get('{user}/edit', [$controller, 'edit'])->name('edit');
    Route::match(['put', 'patch'], '{user}', [$controller, 'update'])->name('update');
    Route::delete('{user}', [$controller, 'destroy'])->name('destroy');
});
