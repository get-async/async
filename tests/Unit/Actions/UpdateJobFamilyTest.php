<?php

declare(strict_types=1);

use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\JobFamily;
use App\Actions\UpdateJobFamily;
use App\Models\Organization;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('updates a job family', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Engineering',
        'description' => 'Technical roles',
    ]);

    $updatedJobFamily = (new UpdateJobFamily(
        organization: $organization,
        user: $user,
        jobFamily: $jobFamily,
        jobFamilyName: 'Software Engineering',
        description: 'Software development roles',
    ))->execute();

    expect($updatedJobFamily)
        ->toBeInstanceOf(JobFamily::class);

    $this->assertDatabaseHas('job_families', [
        'id' => $jobFamily->id,
        'organization_id' => $organization->id,
        'name' => 'Software Engineering',
        'description' => 'Software development roles',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'job_family_update'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'Software Engineering');
        },
    );
});

it('throws an exception if user is not part of organization', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new UpdateJobFamily(
        organization: $organization,
        user: $user,
        jobFamily: $jobFamily,
        jobFamilyName: 'Software Engineering',
        description: 'Software development roles',
    ))->execute();
});
