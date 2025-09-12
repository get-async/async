<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Attach a permission to a role inside an organization.
 */
final class AddPermissionToRole
{
    public function __construct(
        public Organization $organization,
        public User $user,
        public Role $role,
        public Permission $permission,
    ) {}

    public function execute(): Role
    {
        $this->validate();
        $this->attach();
        $this->log();

        return $this->role->load('permissions');
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

        if ($this->permission->organization_id !== $this->organization->id) {
            throw ValidationException::withMessages([
                'permission' => 'Permission does not belong to the organization.',
            ]);
        }

        if ($this->role->permissions()->where('permissions.id', $this->permission->id)->exists()) {
            throw ValidationException::withMessages([
                'permission' => 'Permission already attached to role.',
            ]);
        }
    }

    private function attach(): void
    {
        $this->role->permissions()->attach($this->permission->id);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'role_permission_attach',
            description: sprintf('Attached permission %s to role %s', $this->permission->key, $this->role->name),
        )->onQueue('low');
    }
}
