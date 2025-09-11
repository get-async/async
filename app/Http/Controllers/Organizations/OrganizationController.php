<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organizations;

use App\Actions\CreateOrganization;
use App\Http\Controllers\Controller;
use App\Http\ViewModels\Organizations\OrganizationIndexViewModel;
use App\Jobs\ExportOrganizationAsSql;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

final class OrganizationController extends Controller
{
    public function index(): View
    {
        $viewModel = new OrganizationIndexViewModel(
            user: Auth::user(),
        );

        return view('organizations.index', [
            'viewModel' => $viewModel,
        ]);
    }

    public function create(): View
    {
        return view('organizations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
            ],
        ]);

        $organization = new CreateOrganization(
            user: Auth::user(),
            organizationName: $validated['organization_name'],
        )->execute();

        return redirect()->route('organizations.show', $organization->slug)
            ->with('status', __('Organization created successfully'));
    }

    public function show(Request $request): View
    {
        ExportOrganizationAsSql::dispatch(
            $request->attributes->get('organization'),
            Auth::user()
        );

        return view('organizations.show', [
            'organization' => $request->attributes->get('organization'),
        ]);
    }
}
