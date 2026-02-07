<?php

use Illuminate\Support\Facades\Route;

$middleware = config('northcloud.admin.middleware', ['auth', 'northcloud-admin']);
$prefix = config('northcloud.admin.prefix', 'dashboard/articles');
$namePrefix = config('northcloud.admin.name_prefix', 'dashboard.articles.');
$controller = config('northcloud.admin.controller');

Route::middleware($middleware)->prefix($prefix)->name($namePrefix)->group(function () use ($controller) {
    // Trashed articles management (must be before {article} routes)
    Route::get('trashed', [$controller, 'trashed'])->name('trashed');
    Route::post('bulk-restore', [$controller, 'bulkRestore'])->name('bulk-restore');
    Route::post('bulk-force-delete', [$controller, 'bulkForceDelete'])->name('bulk-force-delete');
    Route::post('{id}/restore', [$controller, 'restore'])->name('restore');
    Route::delete('{id}/force-delete', [$controller, 'forceDelete'])->name('force-delete');

    // Bulk actions
    Route::post('bulk-delete', [$controller, 'bulkDelete'])->name('bulk-delete');
    Route::post('bulk-publish', [$controller, 'bulkPublish'])->name('bulk-publish');
    Route::post('bulk-unpublish', [$controller, 'bulkUnpublish'])->name('bulk-unpublish');
    Route::post('{article}/toggle-publish', [$controller, 'togglePublish'])->name('toggle-publish');

    // Resource routes (explicit to avoid Route::resource naming issues)
    Route::get('/', [$controller, 'index'])->name('index');
    Route::get('create', [$controller, 'create'])->name('create');
    Route::post('/', [$controller, 'store'])->name('store');
    Route::get('{article}', [$controller, 'show'])->name('show');
    Route::get('{article}/edit', [$controller, 'edit'])->name('edit');
    Route::match(['put', 'patch'], '{article}', [$controller, 'update'])->name('update');
    Route::delete('{article}', [$controller, 'destroy'])->name('destroy');
});
