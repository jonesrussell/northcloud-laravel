<?php

namespace JonesRussell\NorthCloud\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ArticleProcessor
{
    /**
     * Process article data. Return the model to continue the pipeline, or null to skip.
     */
    public function process(array $data, ?Model $article): ?Model;

    /**
     * Whether this processor should run for the given data.
     */
    public function shouldProcess(array $data): bool;
}
