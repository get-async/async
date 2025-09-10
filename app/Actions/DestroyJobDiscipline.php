<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\JobDiscipline;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Destroy a job discipline for a job family within an organization.
 */
final class DestroyJobDiscipline
{
    private string $formerName;
    private string $formerJobFamilyName;

    public function __construct(
        public Organization $organization,
        public JobDiscipline $jobDiscipline,
        public User $user,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
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

    private function destroy(): void
    {
        $this->formerName = $this->jobDiscipline->name;
        $this->formerJobFamilyName = $this->jobDiscipline->jobFamily->name;
        $this->jobDiscipline->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'job_discipline_deletion',
            description: sprintf('Deleted a job discipline called %s in %s', $this->formerName, $this->formerJobFamilyName),
        )->onQueue('low');
    }
}
