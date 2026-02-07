<?php

namespace JonesRussell\NorthCloud\Processing;

use Illuminate\Database\Eloquent\Model;
use JonesRussell\NorthCloud\Contracts\ArticleModel;
use JonesRussell\NorthCloud\Contracts\ArticleProcessor;
use JonesRussell\NorthCloud\Services\ArticleIngestionService;

class DefaultArticleProcessor implements ArticleProcessor
{
    public function __construct(
        protected ArticleIngestionService $ingestionService,
    ) {}

    public function process(array $data, ?ArticleModel $article): ?Model
    {
        $skipDedup = ! empty($data['_replay']);

        return $this->ingestionService->ingest($data, $skipDedup);
    }

    public function shouldProcess(array $data): bool
    {
        return true;
    }
}
