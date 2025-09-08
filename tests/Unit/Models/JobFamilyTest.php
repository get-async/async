<?php

declare(strict_types=1);

use App\Models\JobFamily;
use App\Models\Organization;

it('belongs to an organization', function (): void {
    $organization = Organization::factory()->create();
    $jobFamily = JobFamily::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($jobFamily->organization()->exists())->toBeTrue();
});
