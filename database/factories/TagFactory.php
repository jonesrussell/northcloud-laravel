<?php

namespace JonesRussell\NorthCloud\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JonesRussell\NorthCloud\Models\Tag;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(4),
            'type' => 'topic',
            'color' => fake()->optional()->hexColor(),
            'description' => fake()->optional()->sentence(),
            'article_count' => 0,
        ];
    }

    public function type(string $type): static
    {
        return $this->state(['type' => $type]);
    }
}
