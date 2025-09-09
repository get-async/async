<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\User;
use App\Models\JobFamily;
use Illuminate\Validation\ValidationException;

/**
 * Delete a job family for an organization.
 */
final class DestroyJobFamily
{
    public function __construct(
        public Organization $organization,
        public User $user,
        public JobFamily $jobFamily,
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
        $this->jobFamily->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'job_family_deletion',
            description: sprintf('Deleted the job family called %s', $this->jobFamily->name),
        )->onQueue('low');
    }
}
