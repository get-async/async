<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Delete a role for an organization.
 */
final class DestroyRole
{
    private string $formerName;

    public function __construct(
        public Organization $organization,
        public User $user,
        public Role $role,
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

        if ($this->role->organization_id !== $this->organization->id) {
            throw ValidationException::withMessages([
                'role' => 'Role does not belong to the organization.',
            ]);
        }
    }

    private function delete(): void
    {
        $this->formerName = $this->role->name;
        $this->role->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'role_deletion',
            description: sprintf('Deleted the role called %s', $this->formerName),
        )->onQueue('low');
    }
}
