<?php

declare(strict_types=1);

use App\Actions\DestroyRole;
use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('deletes a role', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $role = Role::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Manager',
    ]);

    (new DestroyRole(
        organization: $organization,
        user: $user,
        role: $role,
    ))->execute();

    $this->assertDatabaseMissing('roles', [
        'id' => $role->id,
        'organization_id' => $organization->id,
        'name' => 'Manager',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'role_deletion'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'Manager');
        },
    );
});

it('throws an exception if user is not part of organization when deleting a role', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new DestroyRole(
        organization: $organization,
        user: $user,
        role: $role,
    ))->execute();
});

it('throws an exception if role does not belong to organization on delete', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $otherOrganization = Organization::factory()->create();
    $role = Role::factory()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    (new DestroyRole(
        organization: $organization,
        user: $user,
        role: $role,
    ))->execute();
});
