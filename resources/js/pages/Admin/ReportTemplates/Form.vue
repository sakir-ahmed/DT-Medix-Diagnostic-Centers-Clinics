<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormField from '@/components/ui-kit/FormField.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import ReportPreview from '@/components/ui-kit/ReportPreview.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useDraft } from '@/composables/useDraft';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    template: { type: Object, default: null },
    defaultOptions: { type: Object, required: true },
    departments: { type: Array, default: () => [] },
    center: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const form = useForm({
    name: props.template?.name ?? '',
    type: props.template?.type ?? 'table',
    department_id: props.template?.department_id ?? null,
    options: { ...props.defaultOptions, ...(props.template?.options ?? {}) },
    default_findings: props.template?.default_findings ?? '',
    default_impression: props.template?.default_impression ?? '',
    footer_note: props.template?.footer_note ?? '',
    is_active: props.template?.is_active ?? true,
    phrases: props.template?.phrases ? props.template.phrases.map((p) => ({ ...p })) : [],
});
const draft = useDraft(`report-template-${props.template?.id ?? 'new'}`, form, { t });

const preview = computed(() => ({
    center: props.center,
    patient: { name: 'Sample Patient', patient_code: 'P-26-000001', age_sex: '35 Y 0 M / M' },
    sample_code: 'BR01-261015-0001',
    reported_at: new Date().toISOString(),
    groups:
        form.type === 'table'
            ? [
                  {
                      title: 'Complete Blood Count',
                      rows: [
                          { name: 'Hemoglobin', value: '11.2', unit: form.options.show_unit ? 'g/dL' : '', reference: '13 - 17', flag: 'L' },
                          { name: 'WBC count', value: '7.4', unit: form.options.show_unit ? '10^3/uL' : '', reference: '4 - 11', flag: 'N' },
                      ],
                      notes: [form.options.show_method ? `${t('catalog.method')}: Automated analyzer` : null, form.options.show_interpretation ? t('catalog.sample_interpretation') : null, form.footer_note || null].filter(Boolean).join(' | '),
                  },
              ]
            : [{ title: form.name || t('catalog.new_template'), findings: form.default_findings, impression: form.default_impression, notes: form.footer_note }],
    signatures: [{ name: 'Dr. Sample Pathologist', qualification: 'MBBS, M.Phil', bmdc: 'A-00000' }],
}));

function submit() {
    const options = { preserveScroll: true, onError: focusFirstError, onSuccess: () => draft.clear() };
    props.template ? form.put(route('admin.report-templates.update', props.template.id), options) : form.post(route('admin.report-templates.store'), options);
}
</script>

<template>
    <div>
        <Head :title="template?.name ?? t('catalog.new_template')" />
        <PageHeader :title="template?.name ?? t('catalog.new_template')" :breadcrumbs="[{ label: t('nav.report_templates'), href: route('admin.report-templates.index') }, { label: template?.name ?? t('catalog.new_template') }]" />
        <div class="grid gap-4 xl:grid-cols-2">
            <form class="space-y-4 pb-20" novalidate @submit.prevent="submit">
                <section class="card space-y-3">
                    <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                        <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required span>
                            <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
                        </FormField>
                        <FormField id="type" :label="t('catalog.report_style')" required>
                            <SelectInput id="type" v-model="form.type" :options="[{ value: 'table', label: t('catalog.styles.table') }, { value: 'narrative', label: t('catalog.styles.narrative') }]" />
                        </FormField>
                        <FormField id="department_id" :label="t('catalog.department')" optional>
                            <SelectInput id="department_id" v-model="form.department_id" :placeholder="t('common.all')" :options="departments" />
                        </FormField>
                    </div>
                    <div v-if="form.type === 'table'" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <SwitchField v-for="(value, key) in defaultOptions" :key="key" v-model="form.options[key]" :label="t(`catalog.options.${key}`)" />
                    </div>
                    <template v-else>
                        <FormField id="default_findings" :label="t('reports.findings')" :helper="t('catalog.default_text_helper')" optional>
                            <TextArea id="default_findings" v-model="form.default_findings" :rows="6" />
                        </FormField>
                        <FormField id="default_impression" :label="t('reports.impression')" optional>
                            <TextArea id="default_impression" v-model="form.default_impression" :rows="3" />
                        </FormField>
                    </template>
                    <FormField id="footer_note" :label="t('catalog.footer_note')" optional>
                        <TextArea id="footer_note" v-model="form.footer_note" :rows="2" />
                    </FormField>
                    <SwitchField v-model="form.is_active" :label="t('common.active')" />
                </section>

                <section class="card space-y-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold">{{ t('catalog.quick_phrases') }}</h2>
                            <p class="text-xs text-muted">{{ t('catalog.quick_phrases_hint') }}</p>
                        </div>
                        <UiButton icon="plus" @click="form.phrases.push({ shortcut: '', section: 'findings', text: '' })">{{ t('catalog.add_phrase') }}</UiButton>
                    </div>
                    <div v-for="(phrase, index) in form.phrases" :key="index" class="grid grid-cols-[8rem_7rem_1fr_auto] items-start gap-2">
                        <TextInput :id="`phrases.${index}.shortcut`" v-model="phrase.shortcut" :placeholder="t('catalog.shortcut')" :invalid="!!form.errors[`phrases.${index}.shortcut`]" :aria-label="t('catalog.shortcut')" />
                        <SelectInput v-model="phrase.section" :options="[{ value: 'findings', label: t('reports.findings') }, { value: 'impression', label: t('reports.impression') }]" :aria-label="t('catalog.section')" />
                        <TextArea :id="`phrases.${index}.text`" v-model="phrase.text" :rows="2" :invalid="!!form.errors[`phrases.${index}.text`]" :aria-label="t('catalog.phrase_text')" />
                        <UiButton size="default" variant="ghost-danger" icon-only icon="trash" :label="t('common.remove')" @click="form.phrases.splice(index, 1)" />
                    </div>
                </section>

                <div class="sticky bottom-0 z-10 flex justify-end gap-2 border-t border-border bg-background py-2">
                    <UiButton :href="route('admin.report-templates.index')">{{ t('common.cancel') }}</UiButton>
                    <UiButton type="submit" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('catalog.save_template') }}</UiButton>
                </div>
            </form>
            <div>
                <h2 class="mb-2 text-sm font-semibold">{{ t('catalog.preview') }}</h2>
                <ReportPreview :report="preview" />
            </div>
        </div>
    </div>
</template>
