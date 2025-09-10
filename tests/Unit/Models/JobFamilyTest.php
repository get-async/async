<?php

declare(strict_types=1);

use App\Models\JobDiscipline;
use App\Models\JobFamily;
use App\Models\Organization;

it('belongs to an organization', function (): void {
    $organization = Organization::factory()->create();
    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($jobFamily->organization()->exists())->toBeTrue();
});

it('has many job disciplines', function (): void {
    $jobFamily = JobFamily::factory()->create();
    JobDiscipline::factory()->create([
        'job_family_id' => $jobFamily->id,
    ]);

    expect($jobFamily->jobDisciplines()->exists())->toBeTrue();
});
