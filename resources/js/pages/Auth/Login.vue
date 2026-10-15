<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import FormField from '@/components/ui-kit/FormField.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import CheckboxField from '@/components/ui-kit/CheckboxField.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

const { t } = useI18n();
const form = useForm({ login: '', password: '', remember: false });

function submit() {
    form.post(route('login.store'), {
        onError: (errors) => focusFirstError(errors),
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head :title="t('auth.sign_in')" />
    <h1 class="mb-1 text-base font-semibold">{{ t('auth.sign_in') }}</h1>
    <p class="mb-4 text-[13px] text-muted">{{ t('auth.sign_in_hint') }}</p>
    <form class="space-y-3" novalidate @submit.prevent="submit">
        <FormField id="login" :label="t('auth.login_field')" :error="form.errors.login" required>
            <TextInput id="login" v-model="form.login" name="login" autocomplete="username" autofocus :invalid="!!form.errors.login" aria-describedby="login-error" />
        </FormField>
        <FormField id="password" :label="t('fields.password')" :error="form.errors.password" required>
            <TextInput id="password" v-model="form.password" name="password" type="password" autocomplete="current-password" :invalid="!!form.errors.password" />
        </FormField>
        <CheckboxField v-model="form.remember" :label="t('auth.remember_me')" />
        <UiButton type="submit" variant="primary" size="lg" class="w-full" :loading="form.processing">{{ t('auth.sign_in') }}</UiButton>
    </form>
</template>
