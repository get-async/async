<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\LogUserAction;
use App\Models\JobDiscipline;
use App\Models\JobFamily;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Create a job discipline for a job family within an organization.
 */
final class CreateJobDiscipline
{
    private JobDiscipline $jobDiscipline;

    public function __construct(
        public Organization $organization,
        public JobFamily $jobFamily,
        public User $user,
        public string $jobDisciplineName,
        public ?string $description,
    ) {}

    public function execute(): JobDiscipline
    {
        $this->validate();
        $this->create();
        $this->generateSlug();
        $this->log();

        return $this->jobDiscipline;
    }

    private function validate(): void
    {
        if ($this->user->isPartOfOrganization($this->organization) === false) {
            throw ValidationException::withMessages([
                'organization' => 'User is not part of the organization.',
            ]);
        }

        if ($this->jobFamily->organization_id !== $this->organization->id) {
            throw ValidationException::withMessages([
                'job_family' => 'Job family does not belong to the organization.',
            ]);
        }
    }

    private function create(): void
    {
        $this->jobDiscipline = JobDiscipline::create([
            'organization_id' => $this->organization->id,
            'job_family_id' => $this->jobFamily->id,
            'name' => $this->jobDisciplineName,
            'description' => $this->description,
        ]);
    }

    private function generateSlug(): void
    {
        $slug = $this->jobDiscipline->id . '-' . Str::of($this->jobDisciplineName)->slug('-');

        $this->jobDiscipline->slug = $slug;
        $this->jobDiscipline->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            organization: $this->organization,
            user: $this->user,
            action: 'job_discipline_creation',
            description: sprintf('Created a job discipline called %s in %s', $this->jobDisciplineName, $this->jobFamily->name),
        )->onQueue('low');
    }
}
