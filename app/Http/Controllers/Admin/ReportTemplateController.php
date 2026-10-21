<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\SaveCatalogItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportTemplateRequest;
use App\Models\Department;
use App\Models\QuickPhrase;
use App\Models\ReportTemplate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/ReportTemplates/Index', [
            'templates' => ReportTemplate::query()->with('department')->withCount('phrases')->orderBy('name')->get()->map(fn (ReportTemplate $t): array => [
                'id' => $t->id,
                'name' => $t->name,
                'type' => $t->type->value,
                'department' => $t->department?->name,
                'phrases_count' => $t->phrases_count,
                'is_active' => $t->is_active,
            ]),
        ]);
    }

    public function create(): Response
    {
        return $this->form(null);
    }

    public function edit(ReportTemplate $reportTemplate): Response
    {
        return $this->form($reportTemplate);
    }

    public function store(ReportTemplateRequest $request, SaveCatalogItemAction $save): RedirectResponse
    {
        $template = $save->reportTemplate($request->validated());

        return redirect()->route('admin.report-templates.edit', $template)->with('success', __('catalog.template_saved'));
    }

    public function update(ReportTemplateRequest $request, ReportTemplate $reportTemplate, SaveCatalogItemAction $save): RedirectResponse
    {
        $save->reportTemplate($request->validated(), $reportTemplate);

        return back()->with('success', __('catalog.template_saved'));
    }

    private function form(?ReportTemplate $template): Response
    {
        $template?->load('phrases');

        return Inertia::render('Admin/ReportTemplates/Form', [
            'template' => $template === null ? null : [
                'id' => $template->id,
                'name' => $template->name,
                'type' => $template->type->value,
                'department_id' => $template->department_id,
                'options' => $template->resolvedOptions(),
                'default_findings' => $template->default_findings,
                'default_impression' => $template->default_impression,
                'footer_note' => $template->footer_note,
                'is_active' => $template->is_active,
                'phrases' => $template->phrases->map(fn (QuickPhrase $p): array => ['shortcut' => $p->shortcut, 'section' => $p->section, 'text' => $p->text])->values(),
            ],
            'defaultOptions' => ReportTemplate::DEFAULT_OPTIONS,
            'departments' => Department::query()->orderBy('sort_order')->get(['id', 'name'])->map(fn (Department $d): array => ['value' => $d->id, 'label' => $d->name]),
            'center' => ['name' => setting('center_name'), 'address' => setting('center_address'), 'phone' => setting('center_contact_mobile') === null ? null : substr((string) setting('center_contact_mobile'), 2)],
        ]);
    }
}
