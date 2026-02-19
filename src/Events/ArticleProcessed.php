<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class ArticleProcessed
{
    use Dispatchable;

    public function __construct(
        public Model $article,
    ) {}
}
