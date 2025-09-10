<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\JobDiscipline;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Update a job discipline for a job family within an organization.
 */
final class UpdateJobDiscipline
{
    public function __construct(
        public Organization $organization,
        public JobDiscipline $jobDiscipline,
        public User $user,
        public string $jobDisciplineName,
        public ?string $description,
    ) {}

    public function execute(): JobDiscipline
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->jobDiscipline;
    }

    private function validate(): void
    {
        if ($this->user->isPartOfOrganization($this->organization) === false) {
            throw ValidationException::withMessages([
                'organization' => 'User is not part of the organization.',
            ]);
        }

        if ($this->jobDiscipline->jobFamily->organization_id !== $this->organization->id) {
            throw ValidationException::withMessages([
                'job_family' => 'Job family does not belong to the organization.',
            ]);
        }
    }

    private function update(): void
    {
        $this->jobDiscipline->update([
            'name' => $this->jobDisciplineName,
            'description' => $this->description,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'job_discipline_update',
            description: sprintf('Updated a job discipline called %s in %s', $this->jobDisciplineName, $this->jobDiscipline->jobFamily->name),
        )->onQueue('low');
    }
}
