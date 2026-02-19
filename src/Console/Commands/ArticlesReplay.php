<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;
use JonesRussell\NorthCloud\Console\Commands\Concerns\ParsesSince;
use JonesRussell\NorthCloud\Processing\ProcessorPipeline;

class ArticlesReplay extends Command
{
    use ParsesSince;

    protected $signature = 'articles:replay
        {--id= : Replay a specific article by ID}
        {--since= : Replay articles from the last N hours/days (e.g., 24h, 7d)}
        {--full : Update existing articles instead of skipping duplicates}
        {--dry-run : Show which articles would be replayed without processing}';

    protected $description = 'Re-process existing articles through the processor pipeline';

    public function handle(ProcessorPipeline $pipeline): int
    {
        $articleModel = config('northcloud.models.article');
        $query = $articleModel::query();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } elseif ($since = $this->option('since')) {
            $from = $this->parseSince($since);
            if ($from) {
                $query->where('created_at', '>=', $from);
            }
        } else {
            $this->error('Specify --id or --since to select articles.');

            return self::FAILURE;
        }

        $articles = $query->get();

        if ($this->option('dry-run')) {
            $this->info("{$articles->count()} article(s) would be replayed:");
            foreach ($articles as $article) {
                $this->line("  [{$article->id}] {$article->title}");
            }

            return self::SUCCESS;
        }

        $this->info("Replaying {$articles->count()} article(s)...");
        $processed = 0;
        $errors = 0;
        $full = $this->option('full');

        foreach ($articles as $article) {
            try {
                $data = $this->reconstructData($article, $full);
                $pipeline->run($data);
                $processed++;

                if ($this->option('verbose')) {
                    $this->line("  Replayed: {$article->title}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("  Failed [{$article->id}]: {$e->getMessage()}");
            }
        }

        $this->info("Done. Processed: {$processed}, Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function reconstructData($article, bool $full = false): array
    {
        $metadata = $article->metadata ?? [];

        $data = [
            'id' => $article->external_id ?? "replay-{$article->id}",
            'title' => $article->title,
            'canonical_url' => $article->url,
            'intro' => $article->excerpt,
            'body' => $article->content,
            'author' => $article->author,
            'published_date' => $article->published_at?->toIso8601String(),
            'quality_score' => $metadata['quality_score'] ?? null,
            'publisher' => $metadata['publisher'] ?? [],
            'crime_relevance' => $metadata['crime_relevance'] ?? null,
            'mining' => $metadata['mining'] ?? null,
            'image_url' => $article->image_url,
            'topics' => $article->tags->pluck('slug')->all(),
            '_existing_article' => $article,
        ];

        if ($full) {
            $data['_replay'] = true;
        }

        return $data;
    }
}
