<?php

declare(strict_types=1);

use App\Actions\RemoveRoleFromGroup;
use App\Actions\AddRoleToGroup;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('detaches a role from a group', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $group = Group::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create(['organization_id' => $organization->id]);

    (new AddRoleToGroup(
        organization: $organization,
        user: $user,
        group: $group,
        role: $role,
    ))->execute();

    $result = (new RemoveRoleFromGroup(
        organization: $organization,
        user: $user,
        group: $group,
        role: $role,
    ))->execute();

    expect($result->roles->pluck('id')->all())
        ->not->toContain($role->id);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'group_role_detach'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id;
        },
    );
});

it('throws an exception if user not part of organization when detaching role from group', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $group = Group::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create(['organization_id' => $organization->id]);

    (new RemoveRoleFromGroup(
        organization: $organization,
        user: $user,
        group: $group,
        role: $role,
    ))->execute();
});

it('throws an exception if group not in organization when detaching role', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $otherOrg = Organization::factory()->create();
    $group = Group::factory()->create(['organization_id' => $otherOrg->id]);
    $role = Role::factory()->create(['organization_id' => $organization->id]);

    (new RemoveRoleFromGroup(
        organization: $organization,
        user: $user,
        group: $group,
        role: $role,
    ))->execute();
});

it('throws an exception if role not in organization when detaching from group', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $otherOrg = Organization::factory()->create();
    $group = Group::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create(['organization_id' => $otherOrg->id]);

    (new RemoveRoleFromGroup(
        organization: $organization,
        user: $user,
        group: $group,
        role: $role,
    ))->execute();
});

it('throws an exception if role not attached when detaching', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $group = Group::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create(['organization_id' => $organization->id]);

    (new RemoveRoleFromGroup(
        organization: $organization,
        user: $user,
        group: $group,
        role: $role,
    ))->execute();
});
