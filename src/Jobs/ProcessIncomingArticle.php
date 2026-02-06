<?php

namespace JonesRussell\NorthCloud\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use JonesRussell\NorthCloud\Events\ArticleProcessed;
use JonesRussell\NorthCloud\Processing\ProcessorPipeline;

class ProcessIncomingArticle implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $articleData,
    ) {}

    public function handle(ProcessorPipeline $pipeline): void
    {
        $startTime = microtime(true);

        try {
            $article = $pipeline->run($this->articleData);

            if ($article) {
                ArticleProcessed::dispatch($article);

                $elapsed = round((microtime(true) - $startTime) * 1000, 1);
                Log::info('Article processed', [
                    'external_id' => $this->articleData['id'] ?? 'unknown',
                    'title' => $article->title,
                    'elapsed_ms' => $elapsed,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process article', [
                'external_id' => $this->articleData['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
