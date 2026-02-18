<?php

use JonesRussell\NorthCloud\Admin\ArticleResource;

it('does not include articleable field when disabled', function () {
    config(['northcloud.articleable.enabled' => false]);

    $resource = new ArticleResource;
    $fieldNames = array_column($resource->fields(), 'name');

    expect($fieldNames)->not->toContain('articleable');
});

it('includes articleable field when enabled', function () {
    config(['northcloud.articleable.enabled' => true]);
    config(['northcloud.articleable.models' => [
        'App\\Models\\Movie' => [
            'label' => 'Movie',
            'display' => 'title',
            'search' => ['title'],
        ],
    ]]);

    $resource = new ArticleResource;
    $fieldNames = array_column($resource->fields(), 'name');

    expect($fieldNames)->toContain('articleable');

    $articleableField = collect($resource->fields())->firstWhere('name', 'articleable');
    expect($articleableField['type'])->toBe('articleable');
    expect($articleableField['required'])->toBeFalse();
});
