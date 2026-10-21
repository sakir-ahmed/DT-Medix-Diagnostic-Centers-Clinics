<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\UpdateSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Settings/Edit', [
            'center' => [
                'name' => setting('center_name'),
                'name_bn' => setting('center_name_bn'),
                'contact_mobile' => setting('center_contact_mobile') === null ? null : substr((string) setting('center_contact_mobile'), 2),
                'contact_email' => setting('center_contact_email'),
                'address' => setting('center_address'),
            ],
            'settings' => settings()->rules(),
        ]);
    }

    public function update(SettingsRequest $request, UpdateSettingsAction $update): RedirectResponse
    {
        $update->execute($request->validated());

        return back()->with('success', __('settings.saved'));
    }
}
