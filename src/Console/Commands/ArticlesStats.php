<?php

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArticlesStats extends Command
{
    protected $signature = 'articles:stats
        {--since= : Time period (e.g., 7d, 24h, 30d)}
        {--sources : Show only source breakdown}
        {--tags : Show only tag breakdown}
        {--json : Output as JSON}';

    protected $description = 'Display aggregate article statistics';

    public function handle(): int
    {
        $articleModel = config('northcloud.models.article');
        $newsSourceModel = config('northcloud.models.news_source');
        $tagModel = config('northcloud.models.tag');

        $since = $this->parseSince($this->option('since'));

        $query = $articleModel::query();
        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        $total = $query->count();
        $softDeleted = $articleModel::onlyTrashed()
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->count();

        if ($this->option('json')) {
            return $this->outputJson($articleModel, $total, $softDeleted);
        }

        if ($this->option('sources')) {
            $this->displaySourceBreakdown($articleModel, $since);

            return self::SUCCESS;
        }

        if ($this->option('tags')) {
            $this->displayTagBreakdown($tagModel);

            return self::SUCCESS;
        }

        $this->info('Article Statistics');
        $this->line(str_repeat('─', 30));
        $this->line("Total articles:   {$total}");
        $this->line("Soft-deleted:     {$softDeleted}");

        $this->newLine();
        $this->displaySourceBreakdown($articleModel, $since);
        $this->newLine();
        $this->displayTagBreakdown($tagModel);
        $this->newLine();
        $this->displayIngestionRate($articleModel);

        return self::SUCCESS;
    }

    protected function displaySourceBreakdown(string $articleModel, $since): void
    {
        $this->info('By Source (top 10)');
        $this->line(str_repeat('─', 30));

        $sources = DB::table('articles')
            ->selectRaw('news_source_id, COUNT(*) as article_count')
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->whereNull('deleted_at')
            ->groupBy('news_source_id')
            ->orderByDesc('article_count')
            ->limit(10)
            ->get();

        $newsSourceModel = config('northcloud.models.news_source');
        foreach ($sources as $row) {
            $source = $newsSourceModel::find($row->news_source_id);
            $name = $source ? str_pad($source->name, 25) : str_pad('Unknown', 25);
            $this->line("{$name} {$row->article_count}");
        }
    }

    protected function displayTagBreakdown(string $tagModel): void
    {
        $this->info('By Tag (top 10)');
        $this->line(str_repeat('─', 30));

        $tags = $tagModel::orderByDesc('article_count')->limit(10)->get();
        foreach ($tags as $tag) {
            $name = str_pad($tag->name, 25);
            $this->line("{$name} {$tag->article_count}");
        }
    }

    protected function displayIngestionRate(string $articleModel): void
    {
        $this->info('Ingestion Rate');
        $this->line(str_repeat('─', 30));

        $today = $articleModel::where('created_at', '>=', now()->startOfDay())->count();
        $week = $articleModel::where('created_at', '>=', now()->subWeek())->count();
        $month = $articleModel::where('created_at', '>=', now()->subMonth())->count();

        $this->line("Today:            {$today}");
        $this->line("This week:        {$week}");
        $this->line("This month:       {$month}");
    }

    protected function outputJson(string $articleModel, int $total, int $softDeleted): int
    {
        $data = [
            'total' => $total,
            'soft_deleted' => $softDeleted,
            'today' => $articleModel::where('created_at', '>=', now()->startOfDay())->count(),
            'this_week' => $articleModel::where('created_at', '>=', now()->subWeek())->count(),
            'this_month' => $articleModel::where('created_at', '>=', now()->subMonth())->count(),
        ];

        $this->line(json_encode($data, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    protected function parseSince(?string $since)
    {
        if (! $since) {
            return null;
        }

        if (preg_match('/^(\d+)h$/', $since, $m)) {
            return now()->subHours((int) $m[1]);
        }

        if (preg_match('/^(\d+)d$/', $since, $m)) {
            return now()->subDays((int) $m[1]);
        }

        return null;
    }
}
