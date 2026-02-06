<?php

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
