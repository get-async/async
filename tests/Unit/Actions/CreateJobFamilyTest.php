<?php

declare(strict_types=1);

use App\Models\User;
use App\Actions\CreateJobFamily;
use App\Models\Organization;
use Illuminate\Support\Facades\Queue;

it('creates a job family', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $jobFamily = (new CreateJobFamily(
        user: $user,
        organization: $organization,
        jobFamilyName: 'Sales',
    ))->execute();

    $this->assertDatabaseHas('job_families', [
        'id' => $jobFamily->id,
        'organization_id' => $organization->id,
        'name' => 'Sales',
    ]);
});
