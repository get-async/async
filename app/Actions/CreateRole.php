<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Create a role for an organization.
 */
final class CreateRole
{
    private Role $role;

    public function __construct(
        public Organization $organization,
        public User $user,
        public string $roleName,
        public ?string $description,
    ) {}

    public function execute(): Role
    {
        $this->validate();
        $this->create();
        $this->generateSlug();
        $this->log();

        return $this->role;
    }

    private function validate(): void
    {
        if ($this->user->isPartOfOrganization($this->organization) === false) {
            throw ValidationException::withMessages([
                'organization' => 'User is not part of the organization.',
            ]);
        }
    }

    private function create(): void
    {
        $this->role = Role::create([
            'organization_id' => $this->organization->id,
            'name' => $this->roleName,
            'description' => $this->description,
        ]);
    }

    private function generateSlug(): void
    {
        $slug = $this->role->id . '-' . Str::of($this->roleName)->slug('-');

        $this->role->slug = $slug;
        $this->role->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'role_creation',
            description: sprintf('Created a role called %s', $this->roleName),
        )->onQueue('low');
    }
}
