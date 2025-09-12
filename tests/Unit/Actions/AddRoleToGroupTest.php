<?php

declare(strict_types=1);

use App\Actions\AddRoleToGroup;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('attaches a role to a group', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $group = Group::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create(['organization_id' => $organization->id]);

    $result = (new AddRoleToGroup(
        organization: $organization,
        user: $user,
        group: $group,
        role: $role,
    ))->execute();

    expect($result->roles->pluck('id')->all())
        ->toContain($role->id);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'group_role_attach'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id;
        },
    );
});

it('throws an exception if user not part of organization when attaching role to group', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $group = Group::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create(['organization_id' => $organization->id]);

    (new AddRoleToGroup(
        organization: $organization,
        user: $user,
        group: $group,
        role: $role,
    ))->execute();
});

it('throws an exception if group not in organization when attaching role', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $otherOrg = Organization::factory()->create();
    $group = Group::factory()->create(['organization_id' => $otherOrg->id]);
    $role = Role::factory()->create(['organization_id' => $organization->id]);

    (new AddRoleToGroup(
        organization: $organization,
        user: $user,
        group: $group,
        role: $role,
    ))->execute();
});

it('throws an exception if role not in organization when attaching to group', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $otherOrg = Organization::factory()->create();
    $group = Group::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create(['organization_id' => $otherOrg->id]);

    (new AddRoleToGroup(
        organization: $organization,
        user: $user,
        group: $group,
        role: $role,
    ))->execute();
});

it('throws an exception if role already attached to group', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $group = Group::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create(['organization_id' => $organization->id]);
    $group->roles()->attach($role->id);

    (new AddRoleToGroup(
        organization: $organization,
        user: $user,
        group: $group,
        role: $role,
    ))->execute();
});
