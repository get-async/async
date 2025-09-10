<?php

declare(strict_types=1);

use App\Actions\DestroyJobDiscipline;
use App\Jobs\LogUserAction;
use App\Models\JobDiscipline;
use App\Models\JobFamily;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('deletes a job discipline', function (): void {
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
    ]);

    (new DestroyJobDiscipline(
        organization: $organization,
        jobDiscipline: $jobDiscipline,
        user: $user,
    ))->execute();

    $this->assertDatabaseMissing('job_disciplines', [
        'id' => $jobDiscipline->id,
        'organization_id' => $organization->id,
        'job_family_id' => $jobFamily->id,
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user, $jobFamily): bool {
            return $job->action === 'job_discipline_deletion'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'Frontend Development')
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

    (new DestroyJobDiscipline(
        organization: $organization,
        jobDiscipline: $jobDiscipline,
        user: $user,
    ))->execute();
});

it('throws an exception if job discipline does not belong to organization', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $jobDiscipline = JobDiscipline::factory()->create();

    (new DestroyJobDiscipline(
        organization: $organization,
        jobDiscipline: $jobDiscipline,
        user: $user,
    ))->execute();
});
