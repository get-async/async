<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Organizations\Settings;

use App\Actions\CreateJobDiscipline;
use App\Http\Controllers\Controller;
use App\Http\Resources\JobDisciplineResource;
use App\Actions\DestroyJobDiscipline;
use App\Actions\UpdateJobDiscipline;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class JobDisciplineController extends Controller
{
    use ApiResponses;

    public function index(Request $request): AnonymousResourceCollection
    {
        $organization = $request->attributes->get('organization');
        $jobFamilyId = $request->route()->parameter('job_family_id');

        $jobFamily = $organization
            ->jobFamilies()
            ->with('organization')
            ->where('id', $jobFamilyId)
            ->firstOrFail();

        $jobDisciplines = $jobFamily
            ->jobDisciplines()
            ->with('organization')
            ->paginate(10);

        return JobDisciplineResource::collection($jobDisciplines);
    }

    public function create(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        $jobFamilyId = $request->route()->parameter('job_family_id');

        $jobFamily = $organization
            ->jobFamilies()
            ->where('id', $jobFamilyId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $jobDiscipline = new CreateJobDiscipline(
            organization: $organization,
            jobFamily: $jobFamily,
            user: Auth::user(),
            jobDisciplineName: $validated['name'],
            description: $validated['description'] ?? null,
        )->execute();

        return new JobDisciplineResource($jobDiscipline)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        $jobFamilyId = $request->route()->parameter('job_family_id');
        $jobDisciplineId = $request->route()->parameter('job_discipline_id');

        $jobFamily = $organization
            ->jobFamilies()
            ->with('organization')
            ->where('id', $jobFamilyId)
            ->firstOrFail();

        $jobDiscipline = $jobFamily
            ->jobDisciplines()
            ->with('organization')
            ->where('id', $jobDisciplineId)
            ->firstOrFail();

        return new JobDisciplineResource($jobDiscipline)
            ->response()
            ->setStatusCode(200);
    }

    public function update(Request $request): JsonResponse
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

        $jobDiscipline = new UpdateJobDiscipline(
            organization: $organization,
            jobDiscipline: $jobDiscipline,
            user: Auth::user(),
            jobDisciplineName: $validated['name'],
            description: $validated['description'] ?? null,
        )->execute();

        return new JobDisciplineResource($jobDiscipline)
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Request $request): Response
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

        new DestroyJobDiscipline(
            organization: $organization,
            jobDiscipline: $jobDiscipline,
            user: Auth::user(),
        )->execute();

        return response()->noContent();
    }
}
