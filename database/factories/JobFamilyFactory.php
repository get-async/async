<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\JobFamily;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobFamily>
 */
final class JobFamilyFactory extends Factory
{
    protected $model = JobFamily::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'slug' => fake()->slug(),
        ];
    }
}
