<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\SaveCatalogItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageTemplateRequest;
use App\Models\MessageTemplate;
use App\Services\MessageTemplateRenderer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MessageTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/MessageTemplates/Index', [
            'templates' => MessageTemplate::query()->orderBy('id')->get()->map(fn (MessageTemplate $t): array => [
                'id' => $t->id,
                'key' => $t->key->value,
                'channel' => $t->channel,
                'body_en' => $t->body_en,
                'body_bn' => $t->body_bn,
                'whatsapp_template' => $t->whatsapp_template,
                'is_active' => $t->is_active,
                'placeholders' => $t->key->placeholders(),
            ]),
            'samples' => MessageTemplateRenderer::sampleValues((string) center()?->name),
            'costPerSegment' => (string) config('services.bdbulksms.cost_per_segment'),
        ]);
    }

    public function update(MessageTemplateRequest $request, MessageTemplate $messageTemplate, SaveCatalogItemAction $save): RedirectResponse
    {
        $save->messageTemplate($messageTemplate, $request->validated());

        return back()->with('success', __('catalog.message_saved'));
    }

    public function reset(MessageTemplate $messageTemplate, SaveCatalogItemAction $save): RedirectResponse
    {
        $save->resetMessageTemplate($messageTemplate);

        return back()->with('success', __('catalog.message_reset'));
    }
}
