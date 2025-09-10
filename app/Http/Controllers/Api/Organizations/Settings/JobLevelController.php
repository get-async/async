<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Organizations\Settings;

use App\Actions\CreateJobLevel;
use App\Http\Controllers\Controller;
use App\Http\Resources\JobLevelResource;
use App\Actions\DestroyJobLevel;
use App\Actions\UpdateJobLevel;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class JobLevelController extends Controller
{
    use ApiResponses;

    public function index(Request $request): AnonymousResourceCollection
    {
        $organization = $request->attributes->get('organization');
        $jobFamilyId = $request->route()->parameter('job_family_id');
        $jobDisciplineId = $request->route()->parameter('job_discipline_id');

        $jobFamily = $organization
            ->jobFamilies()
            ->where('id', $jobFamilyId)
            ->firstOrFail();

        $jobDiscipline = $jobFamily
            ->jobDisciplines()
            ->with('organization')
            ->where('id', $jobDisciplineId)
            ->firstOrFail();

        $jobLevels = $jobDiscipline
            ->jobLevels()
            ->with(['organization', 'jobDiscipline'])
            ->paginate(10);

        return JobLevelResource::collection($jobLevels);
    }

    public function create(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        $jobFamilyId = $request->route()->parameter('job_family_id');
        $jobDisciplineId = $request->route()->parameter('job_discipline_id');

        $jobFamily = $organization
            ->jobFamilies()
            ->where('id', $jobFamilyId)
            ->firstOrFail();

        $jobDiscipline = $jobFamily
            ->jobDisciplines()
            ->where('id', $jobDisciplineId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $jobLevel = new CreateJobLevel(
            organization: $organization,
            jobDiscipline: $jobDiscipline,
            user: Auth::user(),
            jobLevelName: $validated['name'],
            description: $validated['description'] ?? null,
        )->execute();

        return new JobLevelResource($jobLevel)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        $jobFamilyId = $request->route()->parameter('job_family_id');
        $jobDisciplineId = $request->route()->parameter('job_discipline_id');
        $jobLevelId = $request->route()->parameter('job_level_id');

        $jobFamily = $organization
            ->jobFamilies()
            ->where('id', $jobFamilyId)
            ->firstOrFail();

        $jobDiscipline = $jobFamily
            ->jobDisciplines()
            ->with('organization')
            ->where('id', $jobDisciplineId)
            ->firstOrFail();

        $jobLevel = $jobDiscipline
            ->jobLevels()
            ->with(['organization', 'jobDiscipline'])
            ->where('id', $jobLevelId)
            ->firstOrFail();

        return new JobLevelResource($jobLevel)
            ->response()
            ->setStatusCode(200);
    }

    public function update(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        $jobFamilyId = $request->route()->parameter('job_family_id');
        $jobDisciplineId = $request->route()->parameter('job_discipline_id');
        $jobLevelId = $request->route()->parameter('job_level_id');

        $jobFamily = $organization
            ->jobFamilies()
            ->where('id', $jobFamilyId)
            ->firstOrFail();

        $jobDiscipline = $jobFamily
            ->jobDisciplines()
            ->where('id', $jobDisciplineId)
            ->firstOrFail();

        $jobLevel = $jobDiscipline
            ->jobLevels()
            ->where('id', $jobLevelId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $jobLevel = new UpdateJobLevel(
            organization: $organization,
            jobLevel: $jobLevel,
            user: Auth::user(),
            jobLevelName: $validated['name'],
            description: $validated['description'] ?? null,
        )->execute();

        return new JobLevelResource($jobLevel)
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Request $request): Response
    {
        $organization = $request->attributes->get('organization');
        $jobFamilyId = $request->route()->parameter('job_family_id');
        $jobDisciplineId = $request->route()->parameter('job_discipline_id');
        $jobLevelId = $request->route()->parameter('job_level_id');

        $jobFamily = $organization
            ->jobFamilies()
            ->where('id', $jobFamilyId)
            ->firstOrFail();

        $jobDiscipline = $jobFamily
            ->jobDisciplines()
            ->where('id', $jobDisciplineId)
            ->firstOrFail();

        $jobLevel = $jobDiscipline
            ->jobLevels()
            ->where('id', $jobLevelId)
            ->firstOrFail();

        new DestroyJobLevel(
            organization: $organization,
            user: Auth::user(),
            jobLevel: $jobLevel,
        )->execute();

        return response()->noContent();
    }
}
