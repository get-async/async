<?php

declare(strict_types=1);

use App\Actions\RemovePermissionFromRole;
use App\Actions\AddPermissionToRole;
use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('detaches a permission from a role', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $permission = Permission::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);

    // Attach first
    (new AddPermissionToRole(
        organization: $organization,
        user: $user,
        role: $role,
        permission: $permission,
    ))->execute();

    $result = (new RemovePermissionFromRole(
        organization: $organization,
        user: $user,
        role: $role,
        permission: $permission,
    ))->execute();

    expect($result->permissions->pluck('id')->all())
        ->not->toContain($permission->id);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'role_permission_detach'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id;
        },
    );
});

it('throws an exception if user not part of organization when detaching permission', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $permission = Permission::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new RemovePermissionFromRole(
        organization: $organization,
        user: $user,
        role: $role,
        permission: $permission,
    ))->execute();
});

it('throws an exception if role not in organization when detaching permission', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $otherOrg = Organization::factory()->create();
    $permission = Permission::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $role = Role::factory()->create([
        'organization_id' => $otherOrg->id,
    ]);

    (new RemovePermissionFromRole(
        organization: $organization,
        user: $user,
        role: $role,
        permission: $permission,
    ))->execute();
});

it('throws an exception if permission not in organization when detaching', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $otherOrg = Organization::factory()->create();
    $permission = Permission::factory()->create([
        'organization_id' => $otherOrg->id,
    ]);
    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new RemovePermissionFromRole(
        organization: $organization,
        user: $user,
        role: $role,
        permission: $permission,
    ))->execute();
});

it('throws an exception if permission not attached when detaching', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $permission = Permission::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new RemovePermissionFromRole(
        organization: $organization,
        user: $user,
        role: $role,
        permission: $permission,
    ))->execute();
});
