<?php

namespace JonesRussell\NorthCloud\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JonesRussell\NorthCloud\Models\NewsSource;

class NewsSourceFactory extends Factory
{
    protected $model = NewsSource::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'url' => fake()->unique()->url(),
            'logo_url' => null,
            'description' => fake()->optional()->sentence(),
            'credibility_score' => fake()->numberBetween(40, 95),
            'bias_rating' => fake()->randomElement(['left', 'center-left', 'center', 'center-right', 'right']),
            'factual_reporting_score' => fake()->numberBetween(50, 100),
            'ownership' => fake()->optional()->company(),
            'country' => fake()->countryCode(),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
