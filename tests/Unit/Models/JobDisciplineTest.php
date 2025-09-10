<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\JobFamily;
use App\Models\JobDiscipline;
use App\Models\JobLevel;

it('belongs to an organization', function (): void {
    $organization = Organization::factory()->create();
    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($jobDiscipline->organization()->exists())->toBeTrue();
});

it('belongs to a job family', function (): void {
    $jobFamily = JobFamily::factory()->create();
    $jobDiscipline = JobDiscipline::factory()->create([
        'job_family_id' => $jobFamily->id,
    ]);

    expect($jobDiscipline->jobFamily()->exists())->toBeTrue();
});

it('has many job levels', function (): void {
    $jobDiscipline = JobDiscipline::factory()->create();
    JobLevel::factory()->create([
        'job_discipline_id' => $jobDiscipline->id,
    ]);

    expect($jobDiscipline->jobLevels()->exists())->toBeTrue();
});
