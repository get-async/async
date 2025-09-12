<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Detach a role from a group inside an organization.
 */
final class RemoveRoleFromGroup
{
    public function __construct(
        public Organization $organization,
        public User $user,
        public Group $group,
        public Role $role,
    ) {}

    public function execute(): Group
    {
        $this->validate();
        $this->detach();
        $this->log();

        return $this->group->load('roles');
    }

    private function validate(): void
    {
        if ($this->user->isPartOfOrganization($this->organization) === false) {
            throw ValidationException::withMessages([
                'organization' => 'User is not part of the organization.',
            ]);
        }

        if ($this->group->organization_id !== $this->organization->id) {
            throw ValidationException::withMessages([
                'group' => 'Group does not belong to the organization.',
            ]);
        }

        if ($this->role->organization_id !== $this->organization->id) {
            throw ValidationException::withMessages([
                'role' => 'Role does not belong to the organization.',
            ]);
        }

        if ($this->group->roles()->where('roles.id', $this->role->id)->doesntExist()) {
            throw ValidationException::withMessages([
                'role' => 'Role is not attached to group.',
            ]);
        }
    }

    private function detach(): void
    {
        $this->group->roles()->detach($this->role->id);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'group_role_detach',
            description: sprintf('Detached role %s from group %s', $this->role->name, $this->group->name),
        )->onQueue('low');
    }
}
