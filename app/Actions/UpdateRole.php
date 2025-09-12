<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Update a role for an organization.
 */
final class UpdateRole
{
    public function __construct(
        public Organization $organization,
        public User $user,
        public Role $role,
        public string $roleName,
        public ?string $description,
    ) {}

    public function execute(): Role
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->role;
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

    private function update(): void
    {
        $this->role->update([
            'name' => $this->roleName,
            'description' => $this->description,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'role_update',
            description: sprintf('Updated the role called %s', $this->roleName),
        )->onQueue('low');
    }
}
