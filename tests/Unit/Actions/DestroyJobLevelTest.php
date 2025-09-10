<?php

declare(strict_types=1);

use App\Actions\DestroyJobLevel;
use App\Jobs\LogUserAction;
use App\Models\JobDiscipline;
use App\Models\JobLevel;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('deletes a job level', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobLevel = JobLevel::factory()->create([
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
        'name' => 'Junior',
    ]);

    (new DestroyJobLevel(
        organization: $organization,
        jobLevel: $jobLevel,
        user: $user,
    ))->execute();

    $this->assertDatabaseMissing('job_levels', [
        'id' => $jobLevel->id,
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user, $jobDiscipline): bool {
            return $job->action === 'job_level_deletion'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'Junior')
                && str_contains($job->description, $jobDiscipline->name);
        },
    );
});

it('throws an exception if user is not part of organization', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $jobLevel = JobLevel::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new DestroyJobLevel(
        organization: $organization,
        jobLevel: $jobLevel,
        user: $user,
    ))->execute();
});

it('throws an exception if job level does not belong to organization', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $jobLevel = JobLevel::factory()->create();

    (new DestroyJobLevel(
        organization: $organization,
        jobLevel: $jobLevel,
        user: $user,
    ))->execute();
});
