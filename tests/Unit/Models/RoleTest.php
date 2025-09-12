<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Role;
use App\Models\Group;

it('belongs to an organization', function (): void {
    $organization = Organization::factory()->create();
    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($role->organization()->exists())->toBeTrue();
});

it('belongs to many groups', function (): void {
    $role = Role::factory()->create();
    $group = Group::factory()->create([
        'organization_id' => $role->organization_id,
    ]);

    $group->roles()->attach($role->id);

    expect($role->groups()->exists())->toBeTrue();
});
