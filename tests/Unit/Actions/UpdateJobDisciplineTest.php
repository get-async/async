<?php

declare(strict_types=1);

use App\Actions\UpdateJobDiscipline;
use App\Jobs\LogUserAction;
use App\Models\JobDiscipline;
use App\Models\JobFamily;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('updates a job discipline', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
        'name' => 'Frontend Development',
        'description' => 'Building user interfaces',
    ]);

    $updatedJobDiscipline = (new UpdateJobDiscipline(
        organization: $organization,
        jobDiscipline: $jobDiscipline,
        user: $user,
        jobDisciplineName: 'Full Stack Development',
        description: 'Building complete web applications',
    ))->execute();

    expect($updatedJobDiscipline)
        ->toBeInstanceOf(JobDiscipline::class);

    $this->assertDatabaseHas('job_disciplines', [
        'id' => $jobDiscipline->id,
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
        'name' => 'Full Stack Development',
        'description' => 'Building complete web applications',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user, $jobFamily): bool {
            return $job->action === 'job_discipline_update'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'Full Stack Development')
                && str_contains($job->description, $jobFamily->name);
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
    $jobDiscipline = JobDiscipline::factory()->create([
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
    ]);

    (new UpdateJobDiscipline(
        organization: $organization,
        jobDiscipline: $jobDiscipline,
        user: $user,
        jobDisciplineName: 'Full Stack Development',
        description: 'Building complete web applications',
    ))->execute();
});

it('throws an exception if job discipline does not belong to organization', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $jobDiscipline = JobDiscipline::factory()->create();

    (new UpdateJobDiscipline(
        organization: $organization,
        jobDiscipline: $jobDiscipline,
        user: $user,
        jobDisciplineName: 'Full Stack Development',
        description: 'Building complete web applications',
    ))->execute();
});
