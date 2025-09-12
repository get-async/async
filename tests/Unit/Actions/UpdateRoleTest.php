<?php

declare(strict_types=1);

use App\Actions\UpdateRole;
use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('updates a role', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $role = Role::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Manager',
        'description' => 'Manages stuff',
    ]);

    $updatedRole = (new UpdateRole(
        organization: $organization,
        user: $user,
        role: $role,
        roleName: 'Senior Manager',
        description: 'Manages more things',
    ))->execute();

    expect($updatedRole)->toBeInstanceOf(Role::class);

    $this->assertDatabaseHas('roles', [
        'id' => $role->id,
        'organization_id' => $organization->id,
        'name' => 'Senior Manager',
        'description' => 'Manages more things',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'role_update'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'Senior Manager');
        },
    );
});

it('throws an exception if user is not part of organization when updating a role', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $role = Role::factory()->create([
        'organization_id' => $organization->id,
    ]);

    (new UpdateRole(
        organization: $organization,
        user: $user,
        role: $role,
        roleName: 'Senior Manager',
        description: 'Manages more things',
    ))->execute();
});

it('throws an exception if role does not belong to organization', function (): void {
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

    (new UpdateRole(
        organization: $organization,
        user: $user,
        role: $role,
        roleName: 'Senior Manager',
        description: 'Manages more things',
    ))->execute();
});
