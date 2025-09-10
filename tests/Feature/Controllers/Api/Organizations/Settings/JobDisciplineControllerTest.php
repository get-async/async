<?php

declare(strict_types=1);

use App\Models\JobDiscipline;
use App\Models\JobFamily;
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

it('can list job disciplines for a job family', function () use ($collectionJsonStructure): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobDiscipline1 = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
        'name' => 'Frontend Development',
        'description' => 'Building user interfaces',
    ]);

    $jobDiscipline2 = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
        'name' => 'Backend Development',
        'description' => 'Server-side development',
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('GET', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}/job-disciplines");

    $response->assertSuccessful();
    $response->assertJsonStructure($collectionJsonStructure);
    $response->assertJsonCount(2, 'data');

    $response->assertJson([
        'data' => [
            [
                'type' => 'job_discipline',
                'id' => (string) $jobDiscipline1->id,
                'attributes' => [
                    'name' => 'Frontend Development',
                    'description' => 'Building user interfaces',
                    'slug' => $jobDiscipline1->slug,
                ],
            ],
            [
                'type' => 'job_discipline',
                'id' => (string) $jobDiscipline2->id,
                'attributes' => [
                    'name' => 'Backend Development',
                    'description' => 'Server-side development',
                    'slug' => $jobDiscipline2->slug,
                ],
            ],
        ],
    ]);
});

it('can create a new job discipline', function () use ($singleJsonStructure): void {
    Carbon::setTestNow('2025-01-01 00:00:00');

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('POST', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}/job-disciplines", [
        'name' => 'Full Stack Development',
        'description' => 'End-to-end web development',
    ]);

    $response->assertCreated();
    $response->assertJsonStructure($singleJsonStructure);

    $this->assertDatabaseHas('job_disciplines', [
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
        'name' => 'Full Stack Development',
        'description' => 'End-to-end web development',
    ]);

    $jobDiscipline = JobDiscipline::where('name', 'Full Stack Development')->first();

    $response->assertJson([
        'data' => [
            'type' => 'job_discipline',
            'id' => (string) $jobDiscipline->id,
            'attributes' => [
                'name' => 'Full Stack Development',
                'description' => 'End-to-end web development',
                'slug' => $jobDiscipline->slug,
                'created_at' => Carbon::now()->timestamp,
                'updated_at' => Carbon::now()->timestamp,
            ],
        ],
    ]);
});

it('can show a specific job discipline', function () use ($singleJsonStructure): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
        'name' => 'DevOps Engineering',
        'description' => 'Infrastructure and deployment automation',
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('GET', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}/job-disciplines/{$jobDiscipline->id}");

    $response->assertSuccessful();
    $response->assertJsonStructure($singleJsonStructure);

    $response->assertJson([
        'data' => [
            'type' => 'job_discipline',
            'id' => (string) $jobDiscipline->id,
            'attributes' => [
                'name' => 'DevOps Engineering',
                'description' => 'Infrastructure and deployment automation',
                'slug' => $jobDiscipline->slug,
            ],
        ],
    ]);
});

it('can update a specific job discipline', function () use ($singleJsonStructure): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
        'name' => 'DevOps Engineering',
        'description' => 'Infrastructure and deployment automation',
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('PUT', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}/job-disciplines/{$jobDiscipline->id}", [
        'name' => 'Platform Engineering',
        'description' => 'Platform and infrastructure development',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure($singleJsonStructure);

    $this->assertDatabaseHas('job_disciplines', [
        'id' => $jobDiscipline->id,
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
        'name' => 'Platform Engineering',
        'description' => 'Platform and infrastructure development',
    ]);
});

it('can delete a specific job discipline', function () use ($singleJsonStructure): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
        'name' => 'DevOps Engineering',
        'description' => 'Infrastructure and deployment automation',
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('DELETE', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}/job-disciplines/{$jobDiscipline->id}");

    $response->assertSuccessful();
    $response->assertNoContent();

    $this->assertDatabaseMissing('job_disciplines', [
        'id' => $jobDiscipline->id,
    ]);
});
