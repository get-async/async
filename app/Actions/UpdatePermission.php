<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Update a permission for an organization.
 */
final class UpdatePermission
{
    public function __construct(
        public Organization $organization,
        public User $user,
        public Permission $permission,
        public string $permissionKey,
        public ?string $name,
        public ?string $description,
    ) {}

    public function execute(): Permission
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->permission;
    }

    private function validate(): void
    {
        if ($this->user->isPartOfOrganization($this->organization) === false) {
            throw ValidationException::withMessages([
                'organization' => 'User is not part of the organization.',
            ]);
        }

        if ($this->permission->organization_id !== $this->organization->id) {
            throw ValidationException::withMessages([
                'permission' => 'Permission does not belong to the organization.',
            ]);
        }

        if (in_array(preg_match('/^[a-z0-9]+([._-][a-z0-9]+)*([.][a-z0-9]+([._-][a-z0-9]+)*)*$/', $this->permissionKey), [0, false], true)) {
            throw ValidationException::withMessages([
                'key' => 'Permission key format is invalid.',
            ]);
        }

        if (Permission::where('organization_id', $this->organization->id)
            ->where('key', $this->permissionKey)
            ->where('id', '!=', $this->permission->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'key' => 'Permission key already exists for this organization.',
            ]);
        }
    }

    private function update(): void
    {
        $this->permission->update([
            'key' => $this->permissionKey,
            'name' => $this->name,
            'description' => $this->description,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'permission_update',
            description: sprintf('Updated the permission with key %s', $this->permissionKey),
        )->onQueue('low');
    }
}
