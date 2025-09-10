<?php

declare(strict_types=1);

use App\Models\JobDiscipline;
use App\Models\JobFamily;
use App\Models\JobLevel;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

$collectionJsonStructure = [
    'data' => [
        '*' => [
            'type',
            'id',
            'attributes' => [
                'organization_id',
                'job_discipline_id',
                'name',
                'description',
                'slug',
                'created_at',
                'updated_at',
            ],
            'links' => [
                'self',
            ],
        ],
    ],
];

$singleJsonStructure = [
    'data' => [
        'type',
        'id',
        'attributes' => [
            'organization_id',
            'job_discipline_id',
            'name',
            'description',
            'slug',
            'created_at',
            'updated_at',
        ],
        'links' => [
            'self',
        ],
    ],
];

it('can list job levels for a job discipline', function () use ($collectionJsonStructure): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
    ]);

    $jobLevel1 = JobLevel::factory()->create([
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
        'name' => 'Junior Developer',
        'description' => 'Entry level position',
    ]);

    $jobLevel2 = JobLevel::factory()->create([
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
        'name' => 'Senior Developer',
        'description' => 'Experienced developer',
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('GET', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}/job-disciplines/{$jobDiscipline->id}/job-levels");

    $response->assertSuccessful();
    $response->assertJsonStructure($collectionJsonStructure);
    $response->assertJsonCount(2, 'data');

    $response->assertJson([
        'data' => [
            [
                'type' => 'job_level',
                'id' => (string) $jobLevel1->id,
                'attributes' => [
                    'name' => 'Junior Developer',
                    'description' => 'Entry level position',
                    'slug' => $jobLevel1->slug,
                ],
            ],
            [
                'type' => 'job_level',
                'id' => (string) $jobLevel2->id,
                'attributes' => [
                    'name' => 'Senior Developer',
                    'description' => 'Experienced developer',
                    'slug' => $jobLevel2->slug,
                ],
            ],
        ],
    ]);
});

it('can create a new job level', function () use ($singleJsonStructure): void {
    Carbon::setTestNow('2025-01-01 00:00:00');

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('POST', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}/job-disciplines/{$jobDiscipline->id}/job-levels", [
        'name' => 'Lead Developer',
        'description' => 'Technical leadership role',
    ]);

    $response->assertCreated();
    $response->assertJsonStructure($singleJsonStructure);

    $this->assertDatabaseHas('job_levels', [
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
        'name' => 'Lead Developer',
        'description' => 'Technical leadership role',
    ]);

    $jobLevel = JobLevel::where('name', 'Lead Developer')->first();

    $response->assertJson([
        'data' => [
            'type' => 'job_level',
            'id' => (string) $jobLevel->id,
            'attributes' => [
                'name' => 'Lead Developer',
                'description' => 'Technical leadership role',
                'slug' => $jobLevel->slug,
                'created_at' => Carbon::now()->timestamp,
                'updated_at' => Carbon::now()->timestamp,
            ],
        ],
    ]);
});

it('can show a specific job level', function () use ($singleJsonStructure): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
    ]);

    $jobLevel = JobLevel::factory()->create([
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
        'name' => 'Staff Engineer',
        'description' => 'High-level technical contributor',
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('GET', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}/job-disciplines/{$jobDiscipline->id}/job-levels/{$jobLevel->id}");

    $response->assertSuccessful();
    $response->assertJsonStructure($singleJsonStructure);

    $response->assertJson([
        'data' => [
            'type' => 'job_level',
            'id' => (string) $jobLevel->id,
            'attributes' => [
                'name' => 'Staff Engineer',
                'description' => 'High-level technical contributor',
                'slug' => $jobLevel->slug,
            ],
        ],
    ]);
});

it('can update a specific job level', function () use ($singleJsonStructure): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
    ]);

    $jobLevel = JobLevel::factory()->create([
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
        'name' => 'Staff Engineer',
        'description' => 'High-level technical contributor',
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('PUT', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}/job-disciplines/{$jobDiscipline->id}/job-levels/{$jobLevel->id}", [
        'name' => 'Principal Engineer',
        'description' => 'Strategic technical leadership',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure($singleJsonStructure);

    $this->assertDatabaseHas('job_levels', [
        'id' => $jobLevel->id,
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
        'name' => 'Principal Engineer',
        'description' => 'Strategic technical leadership',
    ]);
});

it('can delete a specific job level', function () use ($singleJsonStructure): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
    ]);

    $jobLevel = JobLevel::factory()->create([
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
        'name' => 'Staff Engineer',
        'description' => 'High-level technical contributor',
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('DELETE', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}/job-disciplines/{$jobDiscipline->id}/job-levels/{$jobLevel->id}");

    $response->assertSuccessful();
    $response->assertNoContent();

    $this->assertDatabaseMissing('job_levels', [
        'id' => $jobLevel->id,
    ]);
});
