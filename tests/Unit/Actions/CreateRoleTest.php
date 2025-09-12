<?php

declare(strict_types=1);

use App\Actions\CreateRole;
use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('creates a role', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    $role = (new CreateRole(
        organization: $organization,
        user: $user,
        roleName: 'Admin',
        description: 'Administrative role',
    ))->execute();

    $this->assertDatabaseHas('roles', [
        'id' => $role->id,
        'organization_id' => $organization->id,
        'name' => 'Admin',
        'description' => 'Administrative role',
        'slug' => $role->id . '-admin',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: function (LogUserAction $job) use ($organization, $user): bool {
            return $job->action === 'role_creation'
                && $job->user->id === $user->id
                && $job->organization->id === $organization->id
                && str_contains($job->description, 'Admin');
        },
    );
});

it('throws an exception if user is not part of organization when creating a role', function (): void {
    $this->expectException(ValidationException::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    (new CreateRole(
        organization: $organization,
        user: $user,
        roleName: 'Admin',
        description: 'Administrative role',
    ))->execute();
});
