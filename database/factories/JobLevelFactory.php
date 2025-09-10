<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\JobDiscipline;
use App\Models\Organization;
use App\Models\JobLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobLevel>
 */
final class JobLevelFactory extends Factory
{
    protected $model = JobLevel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'job_discipline_id' => JobDiscipline::factory(),
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'slug' => fake()->slug(),
        ];
    }
}
