<?php

declare(strict_types=1);

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

it('can list job families for an organization', function () use ($collectionJsonStructure): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily1 = JobFamily::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Engineering',
        'description' => 'Technical roles',
    ]);

    $jobFamily2 = JobFamily::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Marketing',
        'description' => 'Marketing roles',
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('GET', "/api/organizations/{$organization->id}/settings/job-families");

    $response->assertSuccessful();
    $response->assertJsonStructure($collectionJsonStructure);
    $response->assertJsonCount(2, 'data');

    $response->assertJson([
        'data' => [
            [
                'type' => 'job_family',
                'id' => (string) $jobFamily1->id,
                'attributes' => [
                    'name' => 'Engineering',
                    'description' => 'Technical roles',
                    'slug' => $jobFamily1->slug,
                ],
            ],
            [
                'type' => 'job_family',
                'id' => (string) $jobFamily2->id,
                'attributes' => [
                    'name' => 'Marketing',
                    'description' => 'Marketing roles',
                    'slug' => $jobFamily2->slug,
                ],
            ],
        ],
    ]);
});

it('can create a new job family', function () use ($singleJsonStructure): void {
    Carbon::setTestNow('2025-01-01 00:00:00');

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    Sanctum::actingAs($user);

    $response = $this->json('POST', "/api/organizations/{$organization->id}/settings/job-families", [
        'name' => 'Product Management',
        'description' => 'Product strategy and management roles',
    ]);

    $response->assertCreated();
    $response->assertJsonStructure($singleJsonStructure);

    $this->assertDatabaseHas('job_families', [
        'organization_id' => $organization->id,
        'name' => 'Product Management',
        'description' => 'Product strategy and management roles',
    ]);

    $jobFamily = JobFamily::where('name', 'Product Management')->first();

    $response->assertJson([
        'data' => [
            'type' => 'job_family',
            'id' => (string) $jobFamily->id,
            'attributes' => [
                'name' => 'Product Management',
                'description' => 'Product strategy and management roles',
                'slug' => $jobFamily->slug,
                'created_at' => Carbon::now()->timestamp,
                'updated_at' => Carbon::now()->timestamp,
            ],
        ],
    ]);
});

it('can show a specific job family', function () use ($singleJsonStructure): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Data Science',
        'description' => 'Data analysis and machine learning roles',
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('GET', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}");

    $response->assertSuccessful();
    $response->assertJsonStructure($singleJsonStructure);

    $response->assertJson([
        'data' => [
            'type' => 'job_family',
            'id' => (string) $jobFamily->id,
            'attributes' => [
                'name' => 'Data Science',
                'description' => 'Data analysis and machine learning roles',
                'slug' => $jobFamily->slug,
            ],
        ],
    ]);
});

it('can update a specific job family', function () use ($singleJsonStructure): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['joined_at' => now()]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Data Science',
        'description' => 'Data analysis and machine learning roles',
    ]);

    Sanctum::actingAs($user);

    $response = $this->json('PUT', "/api/organizations/{$organization->id}/settings/job-families/{$jobFamily->id}", [
        'name' => 'Data Analytics',
        'description' => 'Data analysis and machine roles',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure($singleJsonStructure);

    $this->assertDatabaseHas('job_families', [
        'id' => $jobFamily->id,
        'organization_id' => $organization->id,
        'name' => 'Data Analytics',
        'description' => 'Data analysis and machine roles',
    ]);
});
