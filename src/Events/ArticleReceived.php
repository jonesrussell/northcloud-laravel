<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ArticleReceived
{
    use Dispatchable;

    public function __construct(
        public array $articleData,
        public string $channel,
    ) {}
}
