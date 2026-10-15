<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import FormField from '@/components/ui-kit/FormField.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

const { t } = useI18n();
const form = useForm({ code: '' });

function submit() {
    form.post(route('two-factor.store'), { onError: focusFirstError, onFinish: () => form.reset('code') });
}
</script>

<template>
    <div>
        <Head :title="t('auth.two_factor')" />
        <h1 class="mb-1 text-base font-semibold">{{ t('auth.two_factor') }}</h1>
        <p class="mb-4 text-[13px] text-muted">{{ t('auth.two_factor_hint') }}</p>
        <form class="space-y-3" @submit.prevent="submit">
            <FormField id="code" :label="t('auth.code')" :error="form.errors.code" required>
                <TextInput id="code" v-model="form.code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus :invalid="!!form.errors.code" />
            </FormField>
            <UiButton type="submit" variant="primary" size="lg" class="w-full" :loading="form.processing">{{ t('auth.verify') }}</UiButton>
        </form>
    </div>
</template>
