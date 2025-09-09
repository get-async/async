<?php

declare(strict_types=1);

use App\Actions\DestroyJobFamily;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\JobFamily;
use App\Models\Organization;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('deletes a job family', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Engineering',
    ]);

    (new DestroyJobFamily(
        organization: $organization,
        user: $user,
        jobFamily: $jobFamily,
    ))->execute();

    $this->assertDatabaseMissing('job_families', [
        'id' => $jobFamily->id,
        'organization_id' => $organization->id,
        'name' => 'Engineering',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'job_family_deletion'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'Engineering');
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

    (new DestroyJobFamily(
        organization: $organization,
        user: $user,
        jobFamily: $jobFamily,
    ))->execute();
});
