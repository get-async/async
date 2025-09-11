<?php

declare(strict_types=1);

use App\Jobs\ExportOrganizationAsSql;
use App\Models\EmailSent;
use App\Models\JobDiscipline;
use App\Models\JobFamily;
use App\Models\JobLevel;
use App\Models\Log;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

it('exports the organization as a sql file', function (): void {
    $fs = new Filesystem();
    $exportDir = storage_path('app/exports');
    if ($fs->exists($exportDir)) {
        $fs->deleteDirectory($exportDir);
    }

    $org = Organization::factory()->create();
    $other = Organization::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user->id, [
        'joined_at' => now(),
    ]);

    // Seed data for target org
    $family = JobFamily::factory()->create(['organization_id' => $org->id]);
    $discipline = JobDiscipline::factory()->create([
        'organization_id' => $org->id,
        'job_family_id' => $family->id,
    ]);
    JobLevel::factory(3)->create([
        'organization_id' => $org->id,
        'job_discipline_id' => $discipline->id,
    ]);
    Log::factory()->create(['organization_id' => $org->id]);
    EmailSent::factory()->create(['organization_id' => $org->id]);

    // Seed some data for other org to ensure it is excluded
    JobFamily::factory()->create(['organization_id' => $other->id]);
    Log::factory()->create(['organization_id' => $other->id]);

    // Run the job directly
    (new ExportOrganizationAsSql($org, $user))->handle();

    expect($fs->exists($exportDir))->toBeTrue();

    // Find the newest SQL file
    $files = collect($fs->files($exportDir))
        ->filter(fn($f) => Str::endsWith((string) $f, '.sql'))
        ->sortByDesc(fn($f) => $fs->lastModified($f))
        ->values();

    expect($files->count())->toBeGreaterThan(0);

    $path = (string) $files->first();
    $contents = $fs->get($path);

    // Assert expected inserts exist for org-scoped tables
    expect($contents)
        ->toContain('INSERT INTO `job_families`')
        ->toContain('INSERT INTO `job_disciplines`')
        ->toContain('INSERT INTO `job_levels`')
        ->toContain('INSERT INTO `logs`')
        ->toContain('INSERT INTO `emails_sent`');

    // Ensure no unrelated table like users is present
    expect($contents)->not->toContain('INSERT INTO `users`');
});
