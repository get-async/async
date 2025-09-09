<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Organizations\Settings;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobFamilyResource;
use App\Actions\CreateJobFamily;
use App\Actions\DestroyJobFamily;
use App\Actions\UpdateJobFamily;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class JobFamilyController extends Controller
{
    use ApiResponses;

    public function index(Request $request): AnonymousResourceCollection
    {
        $organization = $request->attributes->get('organization');
        $jobFamilies = $organization
            ->jobFamilies()
            ->with('organization')
            ->paginate(10);

        return JobFamilyResource::collection($jobFamilies);
    }

    public function create(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $jobFamily = new CreateJobFamily(
            organization: $organization,
            user: Auth::user(),
            jobFamilyName: $validated['name'],
            description: $validated['description'] ?? null,
        )->execute();

        return new JobFamilyResource($jobFamily)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        $jobFamilyId = $request->route()->parameter('job_family_id');

        $jobFamily = $organization
            ->jobFamilies()
            ->with('organization')
            ->where('id', $jobFamilyId)
            ->firstOrFail();

        return new JobFamilyResource($jobFamily)
            ->response()
            ->setStatusCode(200);
    }

    public function update(Request $request): JsonResponse
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

        $jobFamily = new UpdateJobFamily(
            organization: $organization,
            user: Auth::user(),
            jobFamily: $jobFamily,
            jobFamilyName: $validated['name'],
            description: $validated['description'] ?? null,
        )->execute();

        return new JobFamilyResource($jobFamily)
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Request $request): Response
    {
        $organization = $request->attributes->get('organization');
        $jobFamilyId = $request->route()->parameter('job_family_id');

        $jobFamily = $organization
            ->jobFamilies()
            ->where('id', $jobFamilyId)
            ->firstOrFail();

        new DestroyJobFamily(
            organization: $organization,
            user: Auth::user(),
            jobFamily: $jobFamily,
        )->execute();

        return response()->noContent();
    }
}
