<?php

declare(strict_types=1);

use App\Actions\UpdateJobLevel;
use App\Jobs\LogUserAction;
use App\Models\JobDiscipline;
use App\Models\JobLevel;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('updates a job level', function (): void {
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
        'description' => 'Building user interfaces',
    ]);

    $updatedJobLevel = (new UpdateJobLevel(
        organization: $organization,
        jobLevel: $jobLevel,
        user: $user,
        jobLevelName: 'Senior',
        description: 'Building complete web applications',
    ))->execute();

    expect($updatedJobLevel)
        ->toBeInstanceOf(JobLevel::class);

    $this->assertDatabaseHas('job_levels', [
        'id' => $jobLevel->id,
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
        'name' => 'Senior',
        'description' => 'Building complete web applications',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user, $jobLevel): bool {
            return $job->action === 'job_level_update'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'Senior')
                && str_contains($job->description, $jobLevel->name);
        },
    );
});

it('throws an exception if user is not part of organization', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $jobLevel = JobLevel::factory()->create([
        'organization_id' => $organization->id,
        'job_discipline_id' => $jobDiscipline->id,
    ]);

    (new UpdateJobLevel(
        organization: $organization,
        jobLevel: $jobLevel,
        user: $user,
        jobLevelName: 'Senior',
        description: 'Building complete web applications',
    ))->execute();
});

it('throws an exception if job level does not belong to organization', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $jobLevel = JobLevel::factory()->create();

    (new UpdateJobLevel(
        organization: $organization,
        jobLevel: $jobLevel,
        user: $user,
        jobLevelName: 'Senior',
        description: 'Building complete web applications',
    ))->execute();
});
