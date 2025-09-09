<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\User;
use App\Models\JobFamily;
use Illuminate\Validation\ValidationException;

/**
 * Update a job family for an organization.
 */
final class UpdateJobFamily
{
    public function __construct(
        public Organization $organization,
        public User $user,
        public JobFamily $jobFamily,
        public string $jobFamilyName,
        public ?string $description,
    ) {}

    public function execute(): JobFamily
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->jobFamily;
    }

    private function validate(): void
    {
        if ($this->user->isPartOfOrganization($this->organization) === false) {
            throw ValidationException::withMessages([
                'organization' => 'User is not part of the organization.',
            ]);
        }
    }

    private function update(): void
    {
        $this->jobFamily->update([
            'name' => $this->jobFamilyName,
            'description' => $this->description,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'job_family_update',
            description: sprintf('Updated the job family called %s', $this->jobFamilyName),
        )->onQueue('low');
    }
}
