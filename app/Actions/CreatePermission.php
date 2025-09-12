<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Create a permission for an organization.
 */
final class CreatePermission
{
    private Permission $permission;

    public function __construct(
        public Organization $organization,
        public User $user,
        public string $permissionKey,
        public ?string $name,
        public ?string $description,
    ) {}

    public function execute(): Permission
    {
        $this->validate();
        $this->create();
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

        // Ensure key formatting (lowercase, dot segments allowed)
        if (in_array(preg_match('/^[a-z0-9]+([._-][a-z0-9]+)*([.][a-z0-9]+([._-][a-z0-9]+)*)*$/', $this->permissionKey), [0, false], true)) {
            throw ValidationException::withMessages([
                'key' => 'Permission key format is invalid.',
            ]);
        }

        // Ensure uniqueness within organization (DB constraint will also enforce)
        if (Permission::where('organization_id', $this->organization->id)->where('key', $this->permissionKey)->exists()) {
            throw ValidationException::withMessages([
                'key' => 'Permission key already exists for this organization.',
            ]);
        }
    }

    private function create(): void
    {
        $this->permission = Permission::create([
            'organization_id' => $this->organization->id,
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
            action: 'permission_creation',
            description: sprintf('Created a permission with key %s', $this->permissionKey),
        )->onQueue('low');
    }
}
