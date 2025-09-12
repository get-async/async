<?php

declare(strict_types=1);

use App\Actions\AddPermissionToRole;
use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('attaches a permission key to a role', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $role = Role::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Admin',
    ]);

    $result = (new AddPermissionToRole(
        organization: $organization,
        user: $user,
        role: $role,
        permissionKey: 'organization.create',
    ))->execute();

    expect($result->permissions)
        ->toBeArray()
        ->toContain('organization.create');

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'role_permission_attach'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id;
        },
    );
});

it('throws an exception if user not part of organization when attaching permission key', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new AddPermissionToRole(
        organization: $organization,
        user: $user,
        role: $role,
        permissionKey: 'organization.create',
    ))->execute();
});

it('throws an exception if role not in organization when attaching permission key', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $otherOrg = Organization::factory()->create();
    $role = Role::factory()->create([
        'organization_id' => $otherOrg->id,
    ]);

    (new AddPermissionToRole(
        organization: $organization,
        user: $user,
        role: $role,
        permissionKey: 'organization.create',
    ))->execute();
});

it('throws an exception if permission key already attached', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $role->permissions = ['organization.create'];
    $role->save();

    (new AddPermissionToRole(
        organization: $organization,
        user: $user,
        role: $role,
        permissionKey: 'organization.create',
    ))->execute();
});
