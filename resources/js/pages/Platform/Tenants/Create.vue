<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import FormField from '@/components/ui-kit/FormField.vue';
import MobileInput from '@/components/ui-kit/MobileInput.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    plans: { type: Array, required: true },
    defaultTrialEndsAt: { type: String, required: true },
});

const { t } = useI18n();

const form = useForm({
    name: '',
    name_bn: '',
    slug: '',
    domain: '',
    plan: 'starter',
    trial_ends_at: props.defaultTrialEndsAt,
    contact_name: '',
    contact_mobile: '',
    contact_email: '',
    address: '',
    message_credits: 100,
    owner: { name: '', email: '', mobile: '', username: '', password: '' },
});

function slugify() {
    if (!form.slug) {
        form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 50);
    }
}

const submit = () => form.post(route('platform.tenants.store'), { onError: focusFirstError });
</script>

<template>
    <div>
        <Head :title="t('platform.new_tenant')" />
        <PageHeader :title="t('platform.new_tenant')" :breadcrumbs="[{ label: t('platform.tenants'), href: route('platform.tenants.index') }, { label: t('platform.new_tenant') }]" />
        <form class="max-w-2xl space-y-4" novalidate @submit.prevent="submit">
            <section class="card space-y-3">
                <h2 class="text-sm font-semibold">{{ t('platform.center') }}</h2>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                    <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required>
                        <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" @blur="slugify" />
                    </FormField>
                    <FormField id="name_bn" :label="t('fields.name_bn')" :error="form.errors.name_bn" optional>
                        <TextInput id="name_bn" v-model="form.name_bn" lang="bn" />
                    </FormField>
                    <FormField id="slug" :label="t('platform.slug')" :error="form.errors.slug" :helper="t('platform.slug_helper')" required>
                        <TextInput id="slug" v-model="form.slug" :invalid="!!form.errors.slug" />
                    </FormField>
                    <FormField id="domain" :label="t('platform.domain')" :error="form.errors.domain" optional>
                        <TextInput id="domain" v-model="form.domain" :invalid="!!form.errors.domain" />
                    </FormField>
                    <FormField id="plan" :label="t('platform.plan')" :error="form.errors.plan" required>
                        <SelectInput id="plan" v-model="form.plan" :options="plans.map((p) => ({ value: p.value, label: `${p.label} (৳${p.price})` }))" />
                    </FormField>
                    <FormField id="trial_ends_at" :label="t('platform.trial_ends_at')" :error="form.errors.trial_ends_at" required>
                        <input id="trial_ends_at" v-model="form.trial_ends_at" type="date" class="control" />
                    </FormField>
                    <FormField id="contact_mobile" :label="t('platform.contact_mobile')" :error="form.errors.contact_mobile" optional>
                        <MobileInput id="contact_mobile" v-model="form.contact_mobile" :invalid="!!form.errors.contact_mobile" />
                    </FormField>
                    <FormField id="contact_email" :label="t('fields.email')" :error="form.errors.contact_email" optional>
                        <TextInput id="contact_email" v-model="form.contact_email" type="email" :invalid="!!form.errors.contact_email" />
                    </FormField>
                    <FormField id="address" :label="t('fields.address')" :error="form.errors.address" optional span>
                        <TextArea id="address" v-model="form.address" :rows="2" />
                    </FormField>
                </div>
            </section>
            <section class="card space-y-3">
                <h2 class="text-sm font-semibold">{{ t('platform.owner_account') }}</h2>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                    <FormField id="owner.name" :label="t('platform.owner_name')" :error="form.errors['owner.name']" required>
                        <TextInput id="owner.name" v-model="form.owner.name" :invalid="!!form.errors['owner.name']" />
                    </FormField>
                    <FormField id="owner.username" :label="t('fields.username')" :error="form.errors['owner.username']" optional>
                        <TextInput id="owner.username" v-model="form.owner.username" autocomplete="off" />
                    </FormField>
                    <FormField id="owner.email" :label="t('fields.email')" :error="form.errors['owner.email']">
                        <TextInput id="owner.email" v-model="form.owner.email" type="email" :invalid="!!form.errors['owner.email']" />
                    </FormField>
                    <FormField id="owner.mobile" :label="t('fields.mobile')" :error="form.errors['owner.mobile']">
                        <MobileInput id="owner.mobile" v-model="form.owner.mobile" :invalid="!!form.errors['owner.mobile']" />
                    </FormField>
                    <FormField id="owner.password" :label="t('fields.password')" :error="form.errors['owner.password']" required>
                        <TextInput id="owner.password" v-model="form.owner.password" type="password" autocomplete="new-password" :invalid="!!form.errors['owner.password']" />
                    </FormField>
                </div>
            </section>
            <div class="flex justify-end gap-2">
                <UiButton :href="route('platform.tenants.index')">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('platform.create_tenant') }}</UiButton>
            </div>
        </form>
    </div>
</template>
