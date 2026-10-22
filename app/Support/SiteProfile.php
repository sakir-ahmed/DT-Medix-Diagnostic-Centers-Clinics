<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Branch;

/**
 * The center's public contact profile, shared with every public page
 * (header, footer, contact page). Contains no patient data.
 */
final class SiteProfile
{
    /**
     * @return array{name: string, name_bn: string|null, address: string|null, phone: string|null, email: string|null, branches: list<array{name: string, address: string|null, phone: string|null}>}|null
     */
    public static function current(): ?array
    {
        if (! settings()->has('center_name')) {
            return null;
        }

        $branches = Branch::query()
            ->withoutGlobalScope('accessible')
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['name', 'name_bn', 'address', 'phone'])
            ->map(fn (Branch $branch): array => [
                'name' => LocalName::of($branch->name, $branch->name_bn),
                'address' => $branch->address,
                'phone' => $branch->phone,
            ])
            ->all();

        return [
            'name' => LocalName::of((string) setting('center_name'), setting('center_name_bn')),
            'name_bn' => setting('center_name_bn'),
            'address' => setting('center_address'),
            'phone' => setting('center_contact_mobile'),
            'email' => setting('center_contact_email'),
            'branches' => $branches,
        ];
    }
}
