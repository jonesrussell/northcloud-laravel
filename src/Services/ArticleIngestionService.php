<?php

namespace JonesRussell\NorthCloud\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ArticleIngestionService
{
    public function __construct(
        protected NewsSourceResolver $sourceResolver,
    ) {}

    public function ingest(array $data): ?Model
    {
        if (! $this->validate($data)) {
            return null;
        }

        if ($this->exists($data['id'])) {
            return null;
        }

        $articleModel = config('northcloud.models.article');
        $source = $this->sourceResolver->resolveFromData($data);

        $article = $articleModel::create([
            'news_source_id' => $source->id,
            'title' => $data['title'] ?? $data['og_title'] ?? 'Untitled Article',
            'slug' => $this->generateSlug($data['title'] ?? $data['og_title'] ?? 'untitled'),
            'excerpt' => $data['intro'] ?? $data['og_description'] ?? null,
            'content' => $this->sanitizeContent($data['body'] ?? null),
            'url' => $this->getArticleUrl($data),
            'external_id' => $data['id'],
            'image_url' => $data['og_image'] ?? $data['image_url'] ?? null,
            'author' => $data['author'] ?? null,
            'status' => 'published',
            'published_at' => $this->getPublishedDate($data),
            'crawled_at' => now(),
            'metadata' => $this->buildMetadata($data),
            'view_count' => 0,
            'is_featured' => false,
        ]);

        $this->attachTags($article, $data['topics'] ?? []);

        return $article;
    }

    public function validate(array $data): bool
    {
        if (! isset($data['id'])) {
            return false;
        }

        if (! isset($data['title']) && ! isset($data['og_title'])) {
            return false;
        }

        return true;
    }

    public function exists(string $externalId): bool
    {
        $articleModel = config('northcloud.models.article');

        return $articleModel::where('external_id', $externalId)->exists();
    }

    protected function generateSlug(string $title): string
    {
        $articleModel = config('northcloud.models.article');
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;

        while ($articleModel::where('slug', $slug)->exists()) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    protected function getArticleUrl(array $data): string
    {
        return $data['canonical_url']
            ?? $data['og_url']
            ?? $data['source'] ?? 'https://unknown/' . ($data['id'] ?? Str::uuid());
    }

    protected function getPublishedDate(array $data): Carbon
    {
        $dateString = $data['published_date']
            ?? $data['publisher']['published_at']
            ?? null;

        if ($dateString) {
            try {
                $date = Carbon::parse($dateString);
                if ($date->year >= 1970) {
                    return $date;
                }
            } catch (\Exception $e) {
                // Fall through
            }
        }

        return now();
    }

    protected function buildMetadata(array $data): array
    {
        $metadata = [];

        if (isset($data['quality_score'])) {
            $metadata['quality_score'] = $data['quality_score'];
        }

        if (isset($data['source_reputation'])) {
            $metadata['source_reputation'] = $data['source_reputation'];
        }

        if (isset($data['publisher'])) {
            $metadata['publisher'] = $data['publisher'];
        }

        if (isset($data['crime_relevance'])) {
            $metadata['crime_relevance'] = $data['crime_relevance'];
        }

        if (isset($data['mining'])) {
            $metadata['mining'] = $data['mining'];
        }

        return $metadata;
    }

    protected function sanitizeContent(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }

        $allowedTags = config('northcloud.content.allowed_tags', []);
        $tagString = implode('', array_map(fn ($tag) => "<{$tag}>", $allowedTags));

        return strip_tags($content, $tagString);
    }

    protected function attachTags(Model $article, array $topics): void
    {
        if (empty($topics)) {
            return;
        }

        $tagModel = config('northcloud.models.tag');
        $defaultType = config('northcloud.tags.default_type', 'topic');
        $autoCreate = config('northcloud.tags.auto_create', true);

        $tagIds = [];
        foreach ($topics as $topic) {
            $slug = Str::slug($topic);

            if ($autoCreate) {
                $tag = $tagModel::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => Str::title(str_replace('-', ' ', $slug)),
                        'type' => $defaultType,
                    ]
                );
                $tagIds[$tag->id] = ['confidence' => null];
            } else {
                $tag = $tagModel::where('slug', $slug)->first();
                if ($tag) {
                    $tagIds[$tag->id] = ['confidence' => null];
                }
            }
        }

        if (! empty($tagIds)) {
            $article->tags()->sync($tagIds);
        }
    }
}
