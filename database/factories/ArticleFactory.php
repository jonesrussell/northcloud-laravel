<?php

namespace JonesRussell\NorthCloud\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'news_source_id' => NewsSource::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->randomNumber(5),
            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(3, true),
            'url' => fake()->unique()->url(),
            'external_id' => fake()->unique()->uuid(),
            'image_url' => fake()->optional(0.7)->imageUrl(),
            'author' => fake()->optional()->name(),
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-30 days'),
            'crawled_at' => now(),
            'metadata' => [],
            'view_count' => fake()->numberBetween(0, 1000),
            'is_featured' => fake()->boolean(10),
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }

    public function published(): static
    {
        return $this->state([
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-30 days'),
        ]);
    }
}
