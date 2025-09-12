<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Detach a permission from a role inside an organization.
 */
final class RemovePermissionFromRole
{
    public function __construct(
        public Organization $organization,
        public User $user,
        public Role $role,
        public string $permissionKey,
    ) {}

    public function execute(): Role
    {
        $this->validate();
        $this->detach();
        $this->log();

        return $this->role->refresh();
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

        $key = mb_strtolower($this->permissionKey);

        if (preg_match('/^[a-z0-9]+(\.[a-z0-9]+)*$/', $key) !== 1) {
            throw ValidationException::withMessages([
                'permission' => 'Permission key format is invalid.',
            ]);
        }

        $current = $this->role->permissions ?? [];
        if (in_array($key, $current, true) === false) {
            throw ValidationException::withMessages([
                'permission' => 'Permission is not attached to role.',
            ]);
        }
    }

    private function detach(): void
    {
        $updated = array_values(array_filter(
            $this->role->permissions ?? [],
            fn(string $permission): bool => $permission !== mb_strtolower($this->permissionKey),
        ));

        $this->role->permissions = $updated;
        $this->role->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'role_permission_detach',
            description: sprintf('Detached permission %s from role %s', mb_strtolower($this->permissionKey), $this->role->name),
        )->onQueue('low');
    }
}
