<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\UpdateTenantSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        $tenant = tenant();
        abort_if($tenant === null, 404);

        return Inertia::render('Admin/Settings/Edit', [
            'center' => [
                'name' => $tenant->name,
                'name_bn' => $tenant->name_bn,
                'contact_mobile' => $tenant->contact_mobile === null ? null : substr($tenant->contact_mobile, 2),
                'contact_email' => $tenant->contact_email,
                'address' => $tenant->address,
            ],
            'settings' => $tenant->allSettings(),
        ]);
    }

    public function update(SettingsRequest $request, UpdateTenantSettingsAction $update): RedirectResponse
    {
        $tenant = tenant();
        abort_if($tenant === null, 404);

        $update->execute($tenant, $request->validated());

        return back()->with('success', __('settings.saved'));
    }
}
