<?php

namespace JonesRussell\NorthCloud\Processing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use JonesRussell\NorthCloud\Contracts\ArticleProcessor;

class ProcessorPipeline
{
    public function run(array $data): ?Model
    {
        $processors = config('northcloud.processors', [DefaultArticleProcessor::class]);
        $article = null;

        foreach ($processors as $processorClass) {
            $processor = app($processorClass);

            if (! $processor instanceof ArticleProcessor) {
                Log::warning("Processor {$processorClass} does not implement ArticleProcessor, skipping.");

                continue;
            }

            if (! $processor->shouldProcess($data)) {
                continue;
            }

            $article = $processor->process($data, $article);

            if ($article === null) {
                return null;
            }
        }

        return $article;
    }
}
