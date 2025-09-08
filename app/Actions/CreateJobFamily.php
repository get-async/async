<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\User;
use App\Models\JobFamily;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Create a job family for an organization.
 */
final class CreateJobFamily
{
    private JobFamily $jobFamily;

    public function __construct(
        public Organization $organization,
        public User $user,
        public string $jobFamilyName,
    ) {}

    public function execute(): JobFamily
    {
        $this->validate();
        $this->create();
        $this->generateSlug();
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

    private function create(): void
    {
        $this->jobFamily = JobFamily::create([
            'organization_id' => $this->organization->id,
            'name' => $this->jobFamilyName,
        ]);
    }

    private function generateSlug(): void
    {
        $slug = $this->jobFamily->id . '-' . Str::of($this->jobFamilyName)->slug('-');

        $this->jobFamily->slug = $slug;
        $this->jobFamily->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'job_family_creation',
            description: sprintf('Created a job family called %s', $this->jobFamilyName),
        )->onQueue('low');
    }
}
