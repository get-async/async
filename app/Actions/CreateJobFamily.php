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
        public string $organizationName,
    ) {}

    public function execute(): Organization
    {
        $this->validate();
        $this->create();
        $this->generateSlug();
        $this->addFirstUser();
        $this->log();

        return $this->organization;
    }

    private function validate(): void
    {
        // make sure the organization name doesn't contain any special characters
        if (in_array(preg_match('/^[a-zA-Z0-9\s\-_]+$/', $this->organizationName), [0, false], true)) {
            throw ValidationException::withMessages([
                'organization_name' => 'Organization name can only contain letters, numbers, spaces, hyphens and underscores',
            ]);
        }
    }

    private function create(): void
    {
        $this->organization = Organization::create([
            'name' => $this->organizationName,
        ]);
    }

    private function generateSlug(): void
    {
        $slug = $this->organization->id . '-' . Str::of($this->organizationName)->slug('-');

        $this->organization->slug = $slug;
        $this->organization->save();
    }

    private function addFirstUser(): void
    {
        $this->user->organizations()->attach($this->organization->id, [
            'joined_at' => now(),
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'organization_creation',
            description: sprintf('Created an organization called %s', $this->organizationName),
        )->onQueue('low');
    }
}
