<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Models\User;

/**
 * Populate the default permissions in an organization.
 */
final class PopulateDefaultPermissionsInOrganization
{
    public function __construct(
        public Organization $organization,
        public User $user,
    ) {}

    public function execute(): void
    {
        $this->addOwner();
        $this->addAdministrator();
    }

    private function addOwner(): void
    {
        $owner = new CreateRole(
            organization: $this->organization,
            user: $this->user,
            roleName: 'Owner',
            description: 'Has all permissions',
        )->execute();

        foreach (config('async.permissions') as $permission) {
            new AddPermissionToRole(
                organization: $this->organization,
                user: $this->user,
                role: $owner,
                permissionKey: $permission['permissions'][0]['key'],
            )->execute();
        }
    }

    private function addAdministrator(): void
    {
        $administrator = new CreateRole(
            organization: $this->organization,
            user: $this->user,
            roleName: 'Administrator',
            description: 'Has most permissions',
        )->execute();

        $permissions = [
            'organization.permission.manage',
        ];

        foreach ($permissions as $permission) {
            new AddPermissionToRole(
                organization: $this->organization,
                user: $this->user,
                role: $administrator,
                permissionKey: $permission,
            )->execute();
        }
    }
}
