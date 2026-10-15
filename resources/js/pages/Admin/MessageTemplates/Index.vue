<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { focusFirstError } from '@/composables/useFormErrors';
import { formatMoney, localizeDigits } from '@/lib/format';
import { post } from '@/lib/http';
import { useI18n } from '@/lib/i18n';
import { renderTemplate, smsSegments } from '@/lib/sms';

const props = defineProps({
    templates: { type: Array, required: true },
    samples: { type: Object, required: true },
    costPerSegment: { type: String, default: '0.35' },
});

const { t } = useI18n();
const confirm = useConfirm();
const open = ref(false);
const editing = ref(null);
const form = useForm({ body_en: '', body_bn: '', whatsapp_template: '', is_active: true });

function edit(template) {
    editing.value = template;
    form.clearErrors();
    Object.assign(form, { body_en: template.body_en, body_bn: template.body_bn, whatsapp_template: template.whatsapp_template ?? '', is_active: template.is_active });
    open.value = true;
}

const braced = (name) => '{' + name + '}';

function insert(field, placeholder) {
    form[field] = `${form[field] ?? ''}{${placeholder}}`;
}

const stats = (body) => {
    const rendered = renderTemplate(body, props.samples);
    const info = smsSegments(rendered);
    return { rendered, ...info, cost: info.segments * Number(props.costPerSegment) };
};
const en = computed(() => stats(form.body_en));
const bn = computed(() => stats(form.body_bn));

function submit() {
    form.put(route('admin.message-templates.update', editing.value.id), { preserveScroll: true, onError: focusFirstError, onSuccess: (page) => !page.props.flash?.error && (open.value = false) });
}

function reset() {
    confirm({
        variant: 'warning',
        title: t('catalog.reset_message_title'),
        message: t('catalog.reset_message_text'),
        confirmText: t('catalog.reset_message'),
        action: () => post(route('admin.message-templates.reset', editing.value.id)).then(() => (open.value = false)),
    });
}
</script>

<template>
    <div>
        <Head :title="t('nav.message_templates')" />
        <PageHeader :title="t('nav.message_templates')" :description="t('catalog.messages_description')" />
        <div class="mb-3 flex items-start gap-2 rounded-md border border-border bg-surface p-3 text-[13px]">
            <AppIcon name="lock" class="mt-0.5 text-accent" />
            <span>{{ t('catalog.messages_privacy') }}</span>
        </div>
        <div class="grid gap-3 lg:grid-cols-2">
            <button v-for="template in templates" :key="template.id" type="button" class="card text-left hover:border-accent" @click="edit(template)">
                <div class="mb-1 flex items-center justify-between gap-2">
                    <span class="text-sm font-semibold">{{ t(`catalog.message_keys.${template.key}`) }}</span>
                    <StatusBadge :status="template.is_active ? 'active' : 'inactive'" />
                </div>
                <p class="truncate text-[13px] text-muted">{{ template.body_en }}</p>
                <p class="truncate text-[13px] text-muted" lang="bn">{{ template.body_bn }}</p>
            </button>
        </div>

        <SlideOver v-model:open="open" :title="editing ? t(`catalog.message_keys.${editing.key}`) : ''" width="lg">
            <form v-if="editing" id="message-form" class="space-y-4" novalidate @submit.prevent="submit">
                <div class="flex flex-wrap items-center gap-1 text-xs">
                    <span class="text-muted">{{ t('catalog.placeholders') }}:</span>
                    <code v-for="p in editing.placeholders" :key="p" class="rounded bg-subtle px-1">{{ braced(p) }}</code>
                </div>
                <div v-for="[field, info, lang] in [['body_en', en, 'en'], ['body_bn', bn, 'bn']]" :key="field" class="space-y-1">
                    <FormField :id="field" :label="field === 'body_en' ? t('catalog.english_text') : t('catalog.bangla_text')" :error="form.errors[field]" required>
                        <TextArea :id="field" v-model="form[field]" :rows="4" :lang="lang" :invalid="!!form.errors[field]" />
                    </FormField>
                    <div class="flex flex-wrap gap-1">
                        <UiButton v-for="p in editing.placeholders" :key="p" size="xs" variant="ghost" @click="insert(field, p)">{{ braced(p) }}</UiButton>
                    </div>
                    <div class="rounded-md bg-subtle p-2 text-[13px]">
                        <div class="mb-1 text-xs text-muted">{{ t('catalog.preview') }}</div>
                        <p :lang="lang">{{ info.rendered }}</p>
                        <p class="mt-1 text-xs text-muted tabular-nums">
                            {{ t('catalog.sms_stats', { chars: localizeDigits(info.length), segments: localizeDigits(info.segments), cost: formatMoney(info.cost) }) }}
                            <span v-if="info.unicode">({{ t('catalog.unicode') }})</span>
                        </p>
                    </div>
                </div>
                <FormField id="whatsapp_template" :label="t('catalog.whatsapp_template')" :helper="t('catalog.whatsapp_template_helper')" :error="form.errors.whatsapp_template" optional>
                    <TextInput id="whatsapp_template" v-model="form.whatsapp_template" />
                </FormField>
                <SwitchField v-model="form.is_active" :label="t('common.active')" />
            </form>
            <template #footer>
                <UiButton class="mr-auto" variant="ghost" icon="rotate-left" @click="reset">{{ t('catalog.reset_message') }}</UiButton>
                <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="message-form" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
