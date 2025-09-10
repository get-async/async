<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\JobLevel;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Update a job level for a job discipline within an organization.
 */
final class UpdateJobLevel
{
    public function __construct(
        public Organization $organization,
        public JobLevel $jobLevel,
        public User $user,
        public string $jobLevelName,
        public ?string $description,
    ) {}

    public function execute(): JobLevel
    {
        $this->validate();
        $this->update();
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

        if ($this->jobLevel->jobDiscipline->organization_id !== $this->organization->id) {
            throw ValidationException::withMessages([
                'job_discipline' => 'Job discipline does not belong to the organization.',
            ]);
        }
    }

    private function update(): void
    {
        $this->jobLevel->update([
            'name' => $this->jobLevelName,
            'description' => $this->description,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'job_level_update',
            description: sprintf('Updated a job level called %s in %s', $this->jobLevelName, $this->jobLevel->jobDiscipline->name),
        )->onQueue('low');
    }
}
