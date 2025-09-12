<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Delete a permission for an organization.
 */
final class DestroyPermission
{
    private string $formerKey;

    public function __construct(
        public Organization $organization,
        public User $user,
        public Permission $permission,
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

        if ($this->permission->organization_id !== $this->organization->id) {
            throw ValidationException::withMessages([
                'permission' => 'Permission does not belong to the organization.',
            ]);
        }
    }

    private function delete(): void
    {
        $this->formerKey = $this->permission->key;
        $this->permission->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'permission_deletion',
            description: sprintf('Deleted the permission with key %s', $this->formerKey),
        )->onQueue('low');
    }
}
