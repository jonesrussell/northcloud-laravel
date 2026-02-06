<?php

namespace JonesRussell\NorthCloud\Processing;

use Illuminate\Database\Eloquent\Model;
use JonesRussell\NorthCloud\Contracts\ArticleProcessor;
use JonesRussell\NorthCloud\Services\ArticleIngestionService;

class DefaultArticleProcessor implements ArticleProcessor
{
    public function __construct(
        protected ArticleIngestionService $ingestionService,
    ) {}

    public function process(array $data, ?Model $article): ?Model
    {
        return $this->ingestionService->ingest($data);
    }

    public function shouldProcess(array $data): bool
    {
        return true;
    }
}
