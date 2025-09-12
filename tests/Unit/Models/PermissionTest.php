<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;

it('belongs to an organization', function (): void {
    $organization = Organization::factory()->create();
    $permission = Permission::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($permission->organization()->exists())->toBeTrue();
});

it('belongs to many roles', function (): void {
    $permission = Permission::factory()->create();
    $role = Role::factory()->create([
        'organization_id' => $permission->organization_id,
    ]);

    $permission->roles()->attach($role->id);

    expect($permission->roles()->exists())->toBeTrue();
});
