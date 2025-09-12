<?php

declare(strict_types=1);

use App\Actions\CreatePermission;
use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('creates a permission', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $permission = (new CreatePermission(
        organization: $organization,
        user: $user,
        permissionKey: 'organization.create',
        name: 'Create Organization',
        description: 'Allows creating organizations',
    ))->execute();

    $this->assertDatabaseHas('permissions', [
        'id' => $permission->id,
        'organization_id' => $organization->id,
        'key' => 'organization.create',
        'name' => 'Create Organization',
        'description' => 'Allows creating organizations',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'permission_creation'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'organization.create');
        },
    );
});

it('throws an exception if user not part of organization when creating permission', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    (new CreatePermission(
        organization: $organization,
        user: $user,
        permissionKey: 'organization.create',
        name: 'Create Organization',
        description: 'Allows creating organizations',
    ))->execute();
});

it('throws an exception if duplicate key in same organization', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    Permission::factory()->create([
        'organization_id' => $organization->id,
        'key' => 'organization.create',
    ]);

    (new CreatePermission(
        organization: $organization,
        user: $user,
        permissionKey: 'organization.create',
        name: 'Create Organization',
        description: 'Allows creating organizations',
    ))->execute();
});
