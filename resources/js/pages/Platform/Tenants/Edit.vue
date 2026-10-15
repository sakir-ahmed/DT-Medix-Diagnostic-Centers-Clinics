<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import CheckboxField from '@/components/ui-kit/CheckboxField.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useConfirm } from '@/composables/useConfirm';
import { post } from '@/lib/http';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    tenant: { type: Object, required: true },
    plans: { type: Array, required: true },
    modules: { type: Array, required: true },
});

const { t } = useI18n();
const confirm = useConfirm();

const form = useForm({
    name: props.tenant.name,
    plan: props.tenant.plan,
    trial_ends_at: props.tenant.trial_ends_at,
    subscription_ends_at: props.tenant.subscription_ends_at,
    limits: { branches: props.tenant.limits.branches, users: props.tenant.limits.users },
    modules: [...props.tenant.modules],
    message_credits: props.tenant.message_credits,
});

const submit = () => form.put(route('platform.tenants.update', props.tenant.id), { preserveScroll: true, onError: focusFirstError });

function suspend() {
    confirm({
        variant: 'danger',
        level: 'typed',
        typedWord: props.tenant.slug,
        title: t('platform.suspend_title', { tenant: props.tenant.name }),
        message: t('platform.suspend_message'),
        confirmText: t('platform.suspend'),
        action: () => post(route('platform.tenants.suspend', props.tenant.id)),
    });
}
</script>

<template>
    <Head :title="tenant.name" />
    <PageHeader :title="tenant.name" :breadcrumbs="[{ label: t('platform.tenants'), href: route('platform.tenants.index') }, { label: tenant.name }]">
        <template #actions>
            <StatusBadge :status="tenant.status" />
            <UiButton icon="file-export" @click="router.post(route('platform.tenants.export', tenant.id))">{{ t('platform.export') }}</UiButton>
            <UiButton v-if="tenant.status === 'suspended'" icon="play" @click="router.post(route('platform.tenants.resume', tenant.id))">{{ t('platform.resume') }}</UiButton>
            <UiButton v-else variant="ghost-danger" icon="ban" @click="suspend">{{ t('platform.suspend') }}</UiButton>
        </template>
    </PageHeader>
    <form class="max-w-2xl space-y-4" novalidate @submit.prevent="submit">
        <section class="card space-y-3">
            <h2 class="text-sm font-semibold">{{ t('platform.plan_and_trial') }}</h2>
            <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required>
                    <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
                </FormField>
                <FormField id="plan" :label="t('platform.plan')" :error="form.errors.plan" required>
                    <SelectInput id="plan" v-model="form.plan" :options="plans.map((p) => ({ value: p.value, label: p.label }))" />
                </FormField>
                <FormField id="trial_ends_at" :label="t('platform.trial_ends_at')" :error="form.errors.trial_ends_at" optional>
                    <input id="trial_ends_at" v-model="form.trial_ends_at" type="date" class="control" />
                </FormField>
                <FormField id="subscription_ends_at" :label="t('platform.subscription_ends_at')" :error="form.errors.subscription_ends_at" optional>
                    <input id="subscription_ends_at" v-model="form.subscription_ends_at" type="date" class="control" />
                </FormField>
                <FormField id="limits.branches" :label="t('platform.limit_branches')" :error="form.errors['limits.branches']" :helper="t('platform.unlimited_hint')">
                    <TextInput id="limits.branches" v-model="form.limits.branches" type="number" min="1" align="right" />
                </FormField>
                <FormField id="limits.users" :label="t('platform.limit_users')" :error="form.errors['limits.users']" :helper="t('platform.unlimited_hint')">
                    <TextInput id="limits.users" v-model="form.limits.users" type="number" min="1" align="right" />
                </FormField>
                <FormField id="message_credits" :label="t('platform.message_credits')" :error="form.errors.message_credits">
                    <TextInput id="message_credits" v-model="form.message_credits" type="number" min="0" align="right" />
                </FormField>
            </div>
        </section>
        <section class="card space-y-3">
            <h2 class="text-sm font-semibold">{{ t('platform.modules') }}</h2>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                <CheckboxField v-for="module in modules" :key="module" v-model="form.modules" :value="module" :label="t(`modules.${module}`)" />
            </div>
        </section>
        <div class="flex justify-end gap-2">
            <UiButton :href="route('platform.tenants.index')">{{ t('common.cancel') }}</UiButton>
            <UiButton type="submit" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('common.save_changes') }}</UiButton>
        </div>
    </form>
</template>
