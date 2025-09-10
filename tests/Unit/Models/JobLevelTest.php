<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\JobDiscipline;
use App\Models\JobLevel;

it('belongs to an organization', function (): void {
    $organization = Organization::factory()->create();
    $jobLevel = JobLevel::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($jobLevel->organization()->exists())->toBeTrue();
});

it('belongs to a job discipline', function (): void {
    $jobDiscipline = JobDiscipline::factory()->create();
    $jobLevel = JobLevel::factory()->create([
        'job_discipline_id' => $jobDiscipline->id,
    ]);

    expect($jobLevel->jobDiscipline()->exists())->toBeTrue();
});
