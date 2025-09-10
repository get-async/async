<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\User;
use App\Models\JobLevel;
use Illuminate\Validation\ValidationException;

/**
 * Delete a job level for an organization.
 */
final class DestroyJobLevel
{
    private string $formerName;
    private string $formerJobDisciplineName;

    public function __construct(
        public Organization $organization,
        public User $user,
        public JobLevel $jobLevel,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->delete();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->user->isPartOfOrganization($this->organization) === false) {
            throw ValidationException::withMessages([
                'organization' => 'User is not part of the organization.',
            ]);
        }
    }

    private function delete(): void
    {
        $this->formerName = $this->jobLevel->name;
        $this->formerJobDisciplineName = $this->jobLevel->jobDiscipline->name;
        $this->jobLevel->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'job_level_deletion',
            description: sprintf('Deleted the job level called %s in %s', $this->formerName, $this->formerJobDisciplineName),
        )->onQueue('low');
    }
}
