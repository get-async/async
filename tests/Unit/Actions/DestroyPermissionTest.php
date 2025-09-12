<?php

declare(strict_types=1);

use App\Actions\DestroyPermission;
use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('deletes a permission', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $permission = Permission::factory()->create([
        'organization_id' => $organization->id,
        'key' => 'organization.create',
    ]);

    (new DestroyPermission(
        organization: $organization,
        user: $user,
        permission: $permission,
    ))->execute();

    $this->assertDatabaseMissing('permissions', [
        'id' => $permission->id,
        'organization_id' => $organization->id,
        'key' => 'organization.create',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'permission_deletion'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'organization.create');
        },
    );
});

it('throws an exception if user not part of organization when deleting permission', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $permission = Permission::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new DestroyPermission(
        organization: $organization,
        user: $user,
        permission: $permission,
    ))->execute();
});

it('throws an exception if permission not in organization on delete', function (): void {
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

    (new DestroyPermission(
        organization: $organization,
        user: $user,
        permission: $permission,
    ))->execute();
});
