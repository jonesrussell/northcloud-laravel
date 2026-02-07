<?php

use Illuminate\Database\Eloquent\Model;
use JonesRussell\NorthCloud\Contracts\ArticleModel;
use JonesRussell\NorthCloud\Contracts\ArticleProcessor;
use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Processing\DefaultArticleProcessor;
use JonesRussell\NorthCloud\Processing\ProcessorPipeline;

it('runs default processor to create an article', function () {
    $pipeline = app(ProcessorPipeline::class);

    $data = [
        'id' => 'pipeline-test-001',
        'title' => 'Pipeline Test Article',
        'canonical_url' => 'https://example.com/pipeline-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
        'body' => '<p>Content</p>',
    ];

    $result = $pipeline->run($data);

    expect($result)->toBeInstanceOf(Article::class);
    expect($result->title)->toBe('Pipeline Test Article');
});

it('stops pipeline when a processor returns null', function () {
    // Register a custom processor that always rejects
    config(['northcloud.processors' => [RejectAllProcessor::class]]);

    $pipeline = app(ProcessorPipeline::class);

    $data = [
        'id' => 'rejected-001',
        'title' => 'Should Be Rejected',
        'canonical_url' => 'https://example.com/rejected',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
    ];

    $result = $pipeline->run($data);

    expect($result)->toBeNull();
    expect(Article::count())->toBe(0);
});

it('chains multiple processors', function () {
    config(['northcloud.processors' => [
        DefaultArticleProcessor::class,
        AppendMetadataProcessor::class,
    ]]);

    $pipeline = app(ProcessorPipeline::class);

    $data = [
        'id' => 'chain-test-001',
        'title' => 'Chain Test',
        'canonical_url' => 'https://example.com/chain-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
    ];

    $result = $pipeline->run($data);

    expect($result)->toBeInstanceOf(Article::class);
    expect($result->fresh()->metadata['enriched'])->toBeTrue();
});

// --- Test helper processors ---

class RejectAllProcessor implements ArticleProcessor
{
    public function process(array $data, ?ArticleModel $article): ?Model
    {
        return null;
    }

    public function shouldProcess(array $data): bool
    {
        return true;
    }
}

class AppendMetadataProcessor implements ArticleProcessor
{
    public function process(array $data, ?ArticleModel $article): ?Model
    {
        if ($article) {
            $metadata = $article->metadata ?? [];
            $metadata['enriched'] = true;
            $article->update(['metadata' => $metadata]);
        }

        return $article;
    }

    public function shouldProcess(array $data): bool
    {
        return true;
    }
}
