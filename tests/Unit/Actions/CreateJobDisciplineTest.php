<?php

declare(strict_types=1);

use App\Actions\CreateJobDiscipline;
use App\Jobs\LogUserAction;
use App\Models\JobDiscipline;
use App\Models\JobFamily;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('creates a job discipline', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $jobDiscipline = (new CreateJobDiscipline(
        organization: $organization,
        jobFamily: $jobFamily,
        user: $user,
        jobDisciplineName: 'Frontend Development',
        description: 'Building user interfaces and experiences',
    ))->execute();

    $this->assertDatabaseHas('job_disciplines', [
        'id' => $jobDiscipline->id,
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
        'name' => 'Frontend Development',
        'description' => 'Building user interfaces and experiences',
        'slug' => $jobDiscipline->id . '-frontend-development',
    ]);

    expect($jobDiscipline)->toBeInstanceOf(JobDiscipline::class);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($user, $jobFamily): bool {
            return $job->action === 'job_discipline_creation'
                && $job->user->id === $user->id
                && str_contains($job->description, 'Frontend Development')
                && str_contains($job->description, $jobFamily->name);
        },
    );
});

it('throws an exception if user is not part of organization', function (): void {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('User is not part of the organization.');

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new CreateJobDiscipline(
        organization: $organization,
        jobFamily: $jobFamily,
        user: $user,
        jobDisciplineName: 'Software Development',
        description: 'Building software solutions',
    ))->execute();
});

it('throws an exception if job family does not belong to organization', function (): void {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Job family does not belong to the organization.');

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $otherOrganization = Organization::factory()->create();
    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    (new CreateJobDiscipline(
        organization: $organization,
        jobFamily: $jobFamily,
        user: $user,
        jobDisciplineName: 'Software Development',
        description: 'Building software solutions',
    ))->execute();
});
