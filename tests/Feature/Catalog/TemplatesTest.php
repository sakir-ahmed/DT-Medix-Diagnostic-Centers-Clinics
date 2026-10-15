<?php

declare(strict_types=1);

use App\Actions\Tenants\SeedTenantDefaultsAction;
use App\Enums\MessageKey;
use App\Enums\RoleName;
use App\Models\MessageTemplate;
use App\Models\ReportTemplate;
use App\Services\MessageTemplateRenderer;
use App\Services\SmsService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->tenant = makeTenant();
    inTenant($this->tenant, fn () => app(SeedTenantDefaultsAction::class)->execute());
    $this->owner = makeUser($this->tenant, RoleName::Owner);
});

it('seeds default departments, templates and messages for new tenants', function (): void {
    expect(inTenant($this->tenant, fn () => MessageTemplate::query()->count()))->toBe(count(MessageKey::cases()))
        ->and(inTenant($this->tenant, fn () => ReportTemplate::query()->count()))->toBe(2);
});

it('creates a narrative template with quick phrases and renders its preview', function (): void {
    $this->actingAs(makeUser($this->tenant, RoleName::Pathologist))->post(route('admin.report-templates.store'), [
        'name' => 'X-ray chest', 'type' => 'narrative', 'default_findings' => 'Lung fields are clear.', 'default_impression' => 'Normal chest X-ray.',
        'phrases' => [['shortcut' => 'clear-lungs', 'section' => 'findings', 'text' => 'Both lung fields are clear.']],
    ])->assertSessionHasNoErrors();

    $template = inTenant($this->tenant, fn () => ReportTemplate::query()->where('name', 'X-ray chest')->firstOrFail());

    $this->actingAs($this->owner)->get(route('admin.report-templates.edit', $template))
        ->assertInertia(fn (Assert $page) => $page->component('Admin/ReportTemplates/Form')
            ->where('template.default_findings', 'Lung fields are clear.')
            ->where('template.phrases.0.shortcut', 'clear-lungs')
            ->where('center.name', $this->tenant->name));
});

it('keeps only known table options', function (): void {
    $this->actingAs($this->owner)->post(route('admin.report-templates.store'), ['name' => 'Table', 'type' => 'table', 'options' => ['show_method' => false, 'unknown' => true]])->assertSessionHasNoErrors();

    $options = inTenant($this->tenant, fn () => ReportTemplate::query()->where('name', 'Table')->firstOrFail()->options);
    expect($options)->toHaveKey('show_method', false)->not->toHaveKey('unknown');
});

it('updates a message template with allowed placeholders', function (): void {
    $template = inTenant($this->tenant, fn () => MessageTemplate::query()->where('key', 'report_ready')->firstOrFail());

    $this->actingAs($this->owner)->put(route('admin.message-templates.update', $template), [
        'body_en' => '{center}: report ready {link}', 'body_bn' => '{center}: রিপোর্ট প্রস্তুত {link}', 'is_active' => true,
    ])->assertSessionHasNoErrors()->assertSessionMissing('error');

    expect($template->fresh()->body_en)->toBe('{center}: report ready {link}');
});

it('rejects placeholders that could leak patient data', function (): void {
    $template = inTenant($this->tenant, fn () => MessageTemplate::query()->where('key', 'report_ready')->firstOrFail());

    $this->actingAs($this->owner)->put(route('admin.message-templates.update', $template), [
        'body_en' => 'Dear {patient_name}, your {result} is ready {link}', 'body_bn' => '{center}', 'is_active' => true,
    ])->assertSessionHas('error_code', 'INVALID_PLACEHOLDER');

    expect($template->fresh()->body_en)->not->toContain('patient_name');
});

it('renders templates and counts SMS segments for English and Bangla', function (): void {
    $renderer = new MessageTemplateRenderer;

    expect($renderer->render('{center}: report {link}', ['center' => 'Lab', 'link' => 'https://x.y/r/1']))->toBe('Lab: report https://x.y/r/1')
        ->and(SmsService::segments(str_repeat('a', 160)))->toBe(1)
        ->and(SmsService::segments(str_repeat('a', 161)))->toBe(2)
        ->and(SmsService::segments(str_repeat('ক', 70)))->toBe(1)
        ->and(SmsService::segments(str_repeat('ক', 71)))->toBe(2)
        ->and(SmsService::segments(str_repeat('ক', 135)))->toBe(3);
});

it('resets a message to its default text', function (): void {
    $template = inTenant($this->tenant, fn () => MessageTemplate::query()->where('key', 'due_reminder')->firstOrFail());
    $template->update(['body_en' => 'changed']);

    $this->actingAs($this->owner)->post(route('admin.message-templates.reset', $template))->assertRedirect();

    expect($template->fresh()->body_en)->toBe(MessageKey::DueReminder->defaults()[0]);
});

it('forbids message templates to receptionists and other tenants', function (): void {
    $foreign = inTenant(makeTenant(), function () {
        app(SeedTenantDefaultsAction::class)->execute();

        return MessageTemplate::query()->firstOrFail();
    });

    $this->actingAs(makeUser($this->tenant, RoleName::Receptionist))->get(route('admin.message-templates.index'))->assertForbidden();
    $this->actingAs($this->owner)->put(route('admin.message-templates.update', $foreign), ['body_en' => 'x', 'body_bn' => 'x'])->assertNotFound();
});
