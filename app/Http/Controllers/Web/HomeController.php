<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public landing page: the tenant's page on a tenant host, otherwise the
 * product page. Rendered with SSR.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenant = ResolveTenant::tenantFromHost($request->getHost());
        $title = $tenant === null ? (string) config('app.name') : $tenant->name;

        return Inertia::render('Public/Home', [
            'center' => $tenant === null ? null : [
                'name' => $tenant->name,
                'name_bn' => $tenant->name_bn,
                'address' => $tenant->address,
                'phone' => $tenant->contact_mobile,
            ],
        ])->withViewData(['meta_title' => $title]);
    }
}
