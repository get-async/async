<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\CreateAccount;
use App\Actions\CreateJobFamily;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use App\Actions\CreateOrganization;

final class DatabaseSeeder extends Seeder
{
    private User $michael;

    private Organization $organization;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createDunderMifflin();
        $this->validateEmail();
        $this->addOrganization();
        $this->createJobFamilies();
    }

    private function createDunderMifflin(): void
    {
        $this->michael = new CreateAccount(
            email: 'michael.scott@dundermifflin.com',
            password: 'password',
            firstName: 'Michael',
            lastName: 'Scott',
        )->execute();
    }

    private function validateEmail(): void
    {
        $this->michael->email_verified_at = now();
        $this->michael->save();
    }

    private function addOrganization(): void
    {
        $this->organization = new CreateOrganization(
            user: $this->michael,
            organizationName: 'Dunder Mifflin',
        )->execute();
    }

    private function createJobFamilies(): void
    {
        $jobFamilies = [
            'Engineering' => 'Engineering job family',
            'Design' => 'Design job family',
            'Product' => 'Product job family',
            'Marketing' => 'Marketing job family',
            'Sales' => 'Sales job family',
            'HR' => 'HR job family',
            'Finance' => 'Finance job family',
        ];
        foreach ($jobFamilies as $name => $description) {
            (new CreateJobFamily(
                organization: $this->organization,
                user: $this->michael,
                jobFamilyName: $name,
                description: $description,
            ))->execute();
        }
    }
}
