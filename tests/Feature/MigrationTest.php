<?php

use Illuminate\Support\Facades\Schema;

it('creates the news_sources table', function () {
    expect(Schema::hasTable('news_sources'))->toBeTrue();
    expect(Schema::hasColumns('news_sources', [
        'id', 'name', 'slug', 'url', 'is_active', 'metadata',
    ]))->toBeTrue();
});

it('creates the tags table', function () {
    expect(Schema::hasTable('tags'))->toBeTrue();
    expect(Schema::hasColumns('tags', [
        'id', 'name', 'slug', 'type', 'article_count',
    ]))->toBeTrue();
});

it('creates the articles table', function () {
    expect(Schema::hasTable('articles'))->toBeTrue();
    expect(Schema::hasColumns('articles', [
        'id', 'news_source_id', 'title', 'slug', 'excerpt', 'content',
        'url', 'external_id', 'image_url', 'author', 'published_at',
        'crawled_at', 'metadata', 'view_count', 'is_featured', 'deleted_at',
    ]))->toBeTrue();
});

it('creates the article_tag pivot table', function () {
    expect(Schema::hasTable('article_tag'))->toBeTrue();
    expect(Schema::hasColumns('article_tag', [
        'id', 'article_id', 'tag_id', 'confidence',
    ]))->toBeTrue();
});
