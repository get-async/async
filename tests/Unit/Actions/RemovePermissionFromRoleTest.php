<?php

declare(strict_types=1);

use App\Actions\RemovePermissionFromRole;
use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('detaches a permission key from a role', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $role->permissions = ['organization.create'];
    $role->save();

    $result = (new RemovePermissionFromRole(
        organization: $organization,
        user: $user,
        role: $role,
        permissionKey: 'organization.create',
    ))->execute();

    expect($result->permissions)
        ->toBeArray()
        ->not->toContain('organization.create');

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

it('throws an exception if user not part of organization when detaching permission key', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new RemovePermissionFromRole(
        organization: $organization,
        user: $user,
        role: $role,
        permissionKey: 'organization.create',
    ))->execute();
});

it('throws an exception if role not in organization when detaching permission key', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $otherOrg = Organization::factory()->create();
    $role = Role::factory()->create([
        'organization_id' => $otherOrg->id,
    ]);

    (new RemovePermissionFromRole(
        organization: $organization,
        user: $user,
        role: $role,
        permissionKey: 'organization.create',
    ))->execute();
});

it('throws an exception if permission key not attached when detaching', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new RemovePermissionFromRole(
        organization: $organization,
        user: $user,
        role: $role,
        permissionKey: 'organization.create',
    ))->execute();
});
