<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;

it('creates permissions, roles, groups scoped to an organization and links them', function (): void {
    $organization = Organization::factory()->create();

    $permissionA = Permission::factory()->create([
        'organization_id' => $organization->id,
        'key' => 'organization.create',
    ]);
    $permissionB = Permission::factory()->create([
        'organization_id' => $organization->id,
        'key' => 'organization.update',
    ]);

    $role = Role::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Admin',
    ]);

    $group = Group::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Core Team',
    ]);

    // Attach permissions to role
    $role->permissions()->attach([$permissionA->id, $permissionB->id]);

    // Attach role to group
    $group->roles()->attach($role->id);

    expect($organization->permissions)->toHaveCount(2);
    expect($organization->roles)->toHaveCount(1);
    expect($organization->groups)->toHaveCount(1);

    // Reload relations
    $role->load('permissions');
    $group->load('roles');

    expect($role->permissions->pluck('id')->all())
        ->toContain($permissionA->id, $permissionB->id);
    expect($group->roles->pluck('id')->all())
        ->toContain($role->id);
});
