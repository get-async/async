<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\JobDiscipline;
use App\Models\JobLevel;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Create a job level for a job discipline within an organization.
 */
final class CreateJobLevel
{
    private JobLevel $jobLevel;

    public function __construct(
        public Organization $organization,
        public JobDiscipline $jobDiscipline,
        public User $user,
        public string $jobLevelName,
        public ?string $description,
    ) {}

    public function execute(): JobLevel
    {
        $this->validate();
        $this->create();
        $this->generateSlug();
        $this->log();

        return $this->jobLevel;
    }

    private function validate(): void
    {
        if ($this->user->isPartOfOrganization($this->organization) === false) {
            throw ValidationException::withMessages([
                'organization' => 'User is not part of the organization.',
            ]);
        }

        if ($this->jobDiscipline->organization_id !== $this->organization->id) {
            throw ValidationException::withMessages([
                'job_discipline' => 'Job discipline does not belong to the organization.',
            ]);
        }
    }

    private function create(): void
    {
        $this->jobLevel = JobLevel::create([
            'organization_id' => $this->organization->id,
            'job_discipline_id' => $this->jobDiscipline->id,
            'name' => $this->jobLevelName,
            'description' => $this->description,
        ]);
    }

    private function generateSlug(): void
    {
        $slug = $this->jobLevel->id . '-' . Str::of($this->jobLevelName)->slug('-');

        $this->jobLevel->slug = $slug;
        $this->jobLevel->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'job_level_creation',
            description: sprintf('Created a job level called %s in %s', $this->jobLevelName, $this->jobDiscipline->name),
        )->onQueue('low');
    }
}
