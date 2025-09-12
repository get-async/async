<?php

declare(strict_types=1);

use App\Actions\PopulateDefaultPermissionsInOrganization;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('populates default permissions in an organization', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    (new PopulateDefaultPermissionsInOrganization(
        organization: $organization,
        user: $user,
    ))->execute();

    $ownerRole = Role::where('organization_id', $organization->id)
        ->where('name', 'Owner')
        ->first();

    expect($ownerRole)->not->toBeNull();
    expect($ownerRole->description)->toBe('Has all permissions');

    $adminRole = Role::where('organization_id', $organization->id)
        ->where('name', 'Administrator')
        ->first();

    expect($adminRole)->not->toBeNull();
    expect($adminRole->description)->toBe('Has most permissions');

    // Assert Owner has all permissions from config
    $configPermissions = config('async.permissions');
    $expectedOwnerPermissions = [];
    foreach ($configPermissions as $permission) {
        $expectedOwnerPermissions[] = $permission['permissions'][0]['key'];
    }

    expect($ownerRole->permissions)
        ->toBeArray()
        ->toEqual($expectedOwnerPermissions);

    // Assert Administrator has specific permissions
    expect($adminRole->permissions)
        ->toBeArray()
        ->toContain('organization.permission.manage');
});

it('creates correct number of roles', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $initialRoleCount = Role::where('organization_id', $organization->id)->count();

    (new PopulateDefaultPermissionsInOrganization(
        organization: $organization,
        user: $user,
    ))->execute();

    $finalRoleCount = Role::where('organization_id', $organization->id)->count();

    expect($finalRoleCount - $initialRoleCount)->toBe(2);
});

it('assigns all config permissions to owner role', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    (new PopulateDefaultPermissionsInOrganization(
        organization: $organization,
        user: $user,
    ))->execute();

    $ownerRole = Role::where('organization_id', $organization->id)
        ->where('name', 'Owner')
        ->first();

    // Get expected permissions from config
    $configPermissions = config('async.permissions');
    $expectedPermissionCount = count($configPermissions);

    expect($ownerRole->permissions)
        ->toBeArray()
        ->toHaveCount($expectedPermissionCount);

    // Verify each config permission is assigned
    foreach ($configPermissions as $permission) {
        expect($ownerRole->permissions)
            ->toContain($permission['permissions'][0]['key']);
    }
});
