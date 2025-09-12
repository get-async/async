<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Group;
use App\Models\Role;

it('belongs to an organization', function (): void {
    $organization = Organization::factory()->create();
    $group = Group::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($group->organization()->exists())->toBeTrue();
});

it('belongs to many roles', function (): void {
    $group = Group::factory()->create();
    $role = Role::factory()->create([
        'organization_id' => $group->organization_id,
    ]);

    $group->roles()->attach($role->id);

    expect($group->roles()->exists())->toBeTrue();
});
