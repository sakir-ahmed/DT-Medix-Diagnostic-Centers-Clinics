<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\UpdateCenterSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        $center = center();
        abort_if($center === null, 404);

        return Inertia::render('Admin/Settings/Edit', [
            'center' => [
                'name' => $center->name,
                'name_bn' => $center->name_bn,
                'contact_mobile' => $center->contact_mobile === null ? null : substr($center->contact_mobile, 2),
                'contact_email' => $center->contact_email,
                'address' => $center->address,
            ],
            'settings' => $center->allSettings(),
        ]);
    }

    public function update(SettingsRequest $request, UpdateCenterSettingsAction $update): RedirectResponse
    {
        $center = center();
        abort_if($center === null, 404);

        $update->execute($center, $request->validated());

        return back()->with('success', __('settings.saved'));
    }
}
