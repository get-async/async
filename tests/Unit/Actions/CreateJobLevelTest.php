<?php

declare(strict_types=1);

use App\Actions\CreateJobLevel;
use App\Jobs\LogUserAction;
use App\Models\JobDiscipline;
use App\Models\JobLevel;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('creates a job level', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobLevel = (new CreateJobLevel(
        organization: $organization,
        jobDiscipline: $jobDiscipline,
        user: $user,
        jobLevelName: 'Frontend Development',
        description: 'Building user interfaces and experiences',
    ))->execute();

    $this->assertDatabaseHas('job_levels', [
        'id' => $jobLevel->id,
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
        'name' => 'Frontend Development',
        'description' => 'Building user interfaces and experiences',
        'slug' => $jobLevel->id . '-frontend-development',
    ]);

    expect($jobLevel)->toBeInstanceOf(JobLevel::class);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($user, $jobDiscipline): bool {
            return $job->action === 'job_level_creation'
                && $job->user->id === $user->id
                && str_contains($job->description, 'Frontend Development')
                && str_contains($job->description, $jobDiscipline->name);
        },
    );
});

it('throws an exception if user is not part of organization', function (): void {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('User is not part of the organization.');

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new CreateJobLevel(
        organization: $organization,
        jobDiscipline: $jobDiscipline,
        user: $user,
        jobLevelName: 'Software Development',
        description: 'Building software solutions',
    ))->execute();
});

it('throws an exception if job discipline does not belong to organization', function (): void {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Job discipline does not belong to the organization.');

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $otherOrganization = Organization::factory()->create();
    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    (new CreateJobLevel(
        organization: $organization,
        jobDiscipline: $jobDiscipline,
        user: $user,
        jobLevelName: 'Software Development',
        description: 'Building software solutions',
    ))->execute();
});
