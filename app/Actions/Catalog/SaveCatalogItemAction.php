<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Enums\MessageKey;
use App\Models\Department;
use App\Models\MessageTemplate;
use App\Models\Package;
use App\Models\ReportTemplate;
use App\Services\MessageTemplateRenderer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Departments, packages, report templates and message templates.
 */
final class SaveCatalogItemAction
{
    public function __construct(private readonly MessageTemplateRenderer $renderer) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function department(array $data, ?Department $department = null): Department
    {
        $department ??= new Department;
        $department->fill($data);
        $department->code = strtoupper((string) $data['code']);
        $department->save();

        return $department;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function package(array $data, ?Package $package = null): Package
    {
        return DB::transaction(function () use ($data, $package): Package {
            $package ??= new Package;
            $package->fill(Arr::except($data, ['test_ids']));
            $package->code = strtoupper((string) $data['code']);
            $package->save();
            $package->tests()->sync($data['test_ids'] ?? []);

            return $package;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reportTemplate(array $data, ?ReportTemplate $template = null): ReportTemplate
    {
        return DB::transaction(function () use ($data, $template): ReportTemplate {
            $template ??= new ReportTemplate;
            $template->fill(Arr::except($data, ['phrases']));
            $template->options = array_merge(ReportTemplate::DEFAULT_OPTIONS, Arr::only((array) ($data['options'] ?? []), array_keys(ReportTemplate::DEFAULT_OPTIONS)));
            $template->save();

            if (array_key_exists('phrases', $data)) {
                $template->phrases()->delete();

                foreach ((array) $data['phrases'] as $phrase) {
                    $template->phrases()->create(Arr::only((array) $phrase, ['shortcut', 'section', 'text']));
                }
            }

            return $template;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function messageTemplate(MessageTemplate $template, array $data): MessageTemplate
    {
        $key = $template->key;
        $this->renderer->ensureValid($key, (string) $data['body_en']);
        $this->renderer->ensureValid($key, (string) $data['body_bn']);

        $template->fill(Arr::only($data, ['body_en', 'body_bn', 'whatsapp_template', 'is_active']))->save();

        return $template;
    }

    public function resetMessageTemplate(MessageTemplate $template): MessageTemplate
    {
        [$en, $bn] = MessageKey::from($template->key->value)->defaults();
        $template->fill(['body_en' => $en, 'body_bn' => $bn])->save();

        return $template;
    }
}
