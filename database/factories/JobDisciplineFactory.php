<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\JobDiscipline;
use App\Models\Organization;
use App\Models\JobFamily;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobDiscipline>
 */
final class JobDisciplineFactory extends Factory
{
    protected $model = JobDiscipline::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'job_family_id' => JobFamily::factory(),
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'slug' => fake()->slug(),
        ];
    }
}
