<?php

return [
    'migrations' => [
        'enabled' => (bool) env('NORTHCLOUD_MIGRATIONS_ENABLED', true),
    ],

    'redis' => [
        'connection' => env('NORTHCLOUD_REDIS_CONNECTION', 'northcloud'),
        'channels' => array_filter(array_map(
            'trim',
            explode(',', env('NORTHCLOUD_CHANNELS', 'articles:default'))
        )),
    ],

    'quality' => [
        'min_score' => (int) env('NORTHCLOUD_MIN_QUALITY_SCORE', 0),
        'enabled' => (bool) env('NORTHCLOUD_QUALITY_FILTER', false),
    ],

    'models' => [
        'article' => \JonesRussell\NorthCloud\Models\Article::class,
        'news_source' => \JonesRussell\NorthCloud\Models\NewsSource::class,
        'tag' => \JonesRussell\NorthCloud\Models\Tag::class,
    ],

    'processors' => [
        \JonesRussell\NorthCloud\Processing\DefaultArticleProcessor::class,
    ],

    'processing' => [
        'sync' => (bool) env('NORTHCLOUD_PROCESS_SYNC', true),
    ],

    'content' => [
        'allowed_tags' => ['p', 'br', 'a', 'strong', 'em', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
    ],

    'tags' => [
        'default_type' => 'topic',
        'auto_create' => true,
        'allowed' => [],  // Empty array means all tags allowed
    ],

    'navigation' => [
        'enabled' => true,
        'items' => [
            [
                'title' => 'Articles',
                'route' => 'dashboard.articles.index',
                'icon' => 'FileText',
            ],
            [
                'title' => 'Users',
                'route' => 'dashboard.users.index',
                'icon' => 'Users',
            ],
        ],
    ],

    'articleable' => [
        'enabled' => false,
        'models' => [],
    ],

    'users' => [
        'enabled' => true,
        'middleware' => ['web', 'auth', 'northcloud-admin'],
        'prefix' => 'dashboard/users',
        'name_prefix' => 'dashboard.users.',
        'resource' => \JonesRussell\NorthCloud\Admin\UserResource::class,
        'controller' => \JonesRussell\NorthCloud\Http\Controllers\Admin\UserController::class,
        'policy' => null,  // null = is_admin check; set to policy class for custom auth
        'views' => [
            'prefix' => 'dashboard/users',
        ],
    ],

    'admin' => [
        'middleware' => ['web', 'auth', 'northcloud-admin'],
        'prefix' => 'dashboard/articles',
        'name_prefix' => 'dashboard.articles.',
        'resource' => \JonesRussell\NorthCloud\Admin\ArticleResource::class,
        'controller' => \JonesRussell\NorthCloud\Http\Controllers\Admin\ArticleController::class,
        'policy' => null,  // null = is_admin check; set to policy class for custom auth
        'views' => [
            'prefix' => 'dashboard/articles',
        ],
    ],

    'mail' => [
        'sendgrid' => [
            'api_key' => env('SENDGRID_API_KEY'),
            'set_as_default' => true,
        ],
    ],
];
