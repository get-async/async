<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\NotEnoughPermissionException;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

abstract class BaseAction
{
    /**
     * The user who calls the action.
     */
    public User $user;

    /**
     * The organization object.
     */
    public Organization $organization;

    /**
     * Get the permissions that users need to execute the service.
     */
    public function permissions(): array
    {
        return [];
    }

    /**
     * Validate an array against a set of rules.
     */
    public function validatePermissions(array $permissions): bool
    {

    }
}
