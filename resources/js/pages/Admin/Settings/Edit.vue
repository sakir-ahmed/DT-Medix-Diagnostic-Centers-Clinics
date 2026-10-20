<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import FormField from '@/components/ui-kit/FormField.vue';
import MobileInput from '@/components/ui-kit/MobileInput.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    center: { type: Object, required: true },
    settings: { type: Object, required: true },
});

const { t } = useI18n();
const form = useForm({ ...props.center, contact_mobile: props.center.contact_mobile ?? '', settings: { ...props.settings } });

const submit = () => form.put(route('admin.settings.update'), { preserveScroll: true, onError: focusFirstError });

const releaseRules = ['hold_until_paid', 'manager_override', 'always'].map((value) => ({ value, label: t(`settings.release_rules.${value}`) }));
const numberFields = [
    ['discount_limit_percent', '%'],
    ['vat_percent', '%'],
    ['service_charge_percent', '%'],
    ['report_link_days', t('settings.days')],
    ['follow_up_days', t('settings.days')],
    ['follow_up_fee_percent', '%'],
    ['delta_check_percent', '%'],
    ['commission_default_percent', '%'],
    ['idle_timeout_minutes', t('settings.minutes')],
    ['tat_target_hours', t('settings.hours')],
];
const switches = ['vat_enabled', 'service_charge_enabled', 'allow_self_verification', 'commission_enabled', 'collected_samples_refundable'];
</script>

<template>
    <div>
        <Head :title="t('nav.settings')" />
        <PageHeader :title="t('nav.settings')" :description="t('settings.description')" />
        <form class="max-w-2xl space-y-4 pb-16" novalidate @submit.prevent="submit">
            <section class="card space-y-3">
                <h2 class="text-sm font-semibold">{{ t('settings.center') }}</h2>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                    <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required>
                        <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
                    </FormField>
                    <FormField id="name_bn" :label="t('fields.name_bn')" :error="form.errors.name_bn" optional>
                        <TextInput id="name_bn" v-model="form.name_bn" lang="bn" />
                    </FormField>
                    <FormField id="contact_mobile" :label="t('settings.contact_mobile')" :error="form.errors.contact_mobile" optional>
                        <MobileInput id="contact_mobile" v-model="form.contact_mobile" :invalid="!!form.errors.contact_mobile" />
                    </FormField>
                    <FormField id="contact_email" :label="t('fields.email')" :error="form.errors.contact_email" optional>
                        <TextInput id="contact_email" v-model="form.contact_email" type="email" />
                    </FormField>
                    <FormField id="address" :label="t('fields.address')" :error="form.errors.address" optional span>
                        <TextArea id="address" v-model="form.address" :rows="2" />
                    </FormField>
                </div>
            </section>
            <section class="card space-y-3">
                <h2 class="text-sm font-semibold">{{ t('settings.rules') }}</h2>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                    <FormField id="settings.release_rule" :label="t('settings.fields.release_rule')" :error="form.errors['settings.release_rule']" :helper="t('settings.helpers.release_rule')" required span>
                        <SelectInput id="settings.release_rule" v-model="form.settings.release_rule" :options="releaseRules" />
                    </FormField>
                    <FormField v-for="[key, suffix] in numberFields" :id="`settings.${key}`" :key="key" :label="t(`settings.fields.${key}`)" :error="form.errors[`settings.${key}`]" required>
                        <TextInput :id="`settings.${key}`" v-model="form.settings[key]" inputmode="decimal" align="right" :suffix="suffix" :invalid="!!form.errors[`settings.${key}`]" />
                    </FormField>
                    <FormField id="settings.commission_basis" :label="t('settings.fields.commission_basis')" :error="form.errors['settings.commission_basis']" required>
                        <SelectInput id="settings.commission_basis" v-model="form.settings.commission_basis" :options="[{ value: 'paid', label: t('settings.basis.paid') }, { value: 'billed', label: t('settings.basis.billed') }]" />
                    </FormField>
                    <FormField id="settings.default_locale" :label="t('settings.fields.default_locale')" required>
                        <SelectInput id="settings.default_locale" v-model="form.settings.default_locale" :options="[{ value: 'en', label: 'English' }, { value: 'bn', label: 'বাংলা' }]" />
                    </FormField>
                </div>
                <div class="grid grid-cols-1 gap-2 pt-2 sm:grid-cols-2">
                    <SwitchField v-for="key in switches" :key="key" v-model="form.settings[key]" :label="t(`settings.fields.${key}`)" />
                </div>
            </section>
            <div class="sticky bottom-0 -mx-1 flex justify-end gap-2 border-t border-border bg-background px-1 py-2">
                <UiButton @click="form.reset()">{{ t('common.reset') }}</UiButton>
                <UiButton type="submit" variant="primary" icon="floppy-disk" :loading="form.processing" :disabled="!form.isDirty">{{ t('common.save_changes') }}</UiButton>
            </div>
        </form>
    </div>
</template>
