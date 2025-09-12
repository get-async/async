<?php

declare(strict_types=1);

use App\Actions\UpdatePermission;
use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('updates a permission', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $permission = Permission::factory()->create([
        'organization_id' => $organization->id,
        'key' => 'organization.create',
        'name' => 'Create Org',
    ]);

    $updated = (new UpdatePermission(
        organization: $organization,
        user: $user,
        permission: $permission,
        permissionKey: 'organization.update',
        name: 'Update Org',
        description: 'Allows updating orgs',
    ))->execute();

    expect($updated)->toBeInstanceOf(Permission::class);

    $this->assertDatabaseHas('permissions', [
        'id' => $permission->id,
        'organization_id' => $organization->id,
        'key' => 'organization.update',
        'name' => 'Update Org',
        'description' => 'Allows updating orgs',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'permission_update'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'organization.update');
        },
    );
});

it('throws an exception if user not part of organization when updating permission', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $permission = Permission::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new UpdatePermission(
        organization: $organization,
        user: $user,
        permission: $permission,
        permissionKey: 'organization.update',
        name: 'Update Org',
        description: 'Allows updating orgs',
    ))->execute();
});

it('throws an exception if permission not in organization', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $otherOrg = Organization::factory()->create();
    $permission = Permission::factory()->create([
        'organization_id' => $otherOrg->id,
    ]);

    (new UpdatePermission(
        organization: $organization,
        user: $user,
        permission: $permission,
        permissionKey: 'organization.update',
        name: 'Update Org',
        description: 'Allows updating orgs',
    ))->execute();
});

it('throws an exception if duplicate key on update', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $permissionA = Permission::factory()->create([
        'organization_id' => $organization->id,
        'key' => 'organization.create',
    ]);
    Permission::factory()->create([
        'organization_id' => $organization->id,
        'key' => 'organization.update',
    ]);

    (new UpdatePermission(
        organization: $organization,
        user: $user,
        permission: $permissionA,
        permissionKey: 'organization.update',
        name: 'Update Org',
        description: 'Allows updating orgs',
    ))->execute();
});
