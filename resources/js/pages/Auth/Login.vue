<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import CheckboxField from '@/components/ui-kit/CheckboxField.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    demoUsers: { type: Array, default: () => [] },
});

const { t } = useI18n();
const form = useForm({ login: '', password: '', remember: false });
const loggingInId = ref(null);

function submit() {
    form.post(route('login.store'), {
        onError: (errors) => focusFirstError(errors),
        onFinish: () => form.reset('password'),
    });
}

function demoLogin(user) {
    loggingInId.value = user.id;
    router.post(route('demo.login', user.id), {}, { onFinish: () => (loggingInId.value = null) });
}

function initials(name) {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}
</script>

<template>
    <div>
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

        <section v-if="demoUsers.length > 0" class="mt-6 rounded-lg border border-border bg-surface p-3" :aria-label="t('auth.demo_accounts')">
            <h2 class="flex items-center gap-1.5 text-sm font-semibold">
                <AppIcon name="bolt" size="sm" class="text-accent" />
                {{ t('auth.demo_accounts') }}
            </h2>
            <p class="mt-0.5 text-xs text-muted">{{ t('auth.demo_accounts_hint') }}</p>
            <ul class="mt-2 space-y-1.5">
                <li v-for="user in demoUsers" :key="user.id">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-md border border-border bg-surface px-2.5 py-2 text-left transition-colors duration-100 hover:border-accent hover:bg-subtle focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none"
                        :aria-label="t('auth.demo_login_as', { name: user.name, role: user.role })"
                        @click="demoLogin(user)"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">{{ initials(user.name) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-medium">{{ user.role }}</span>
                            <span class="block truncate text-xs text-muted tabular-nums">{{ user.username }}</span>
                        </span>
                        <AppIcon v-if="loggingInId === user.id" name="spinner" spin class="text-muted" />
                        <AppIcon v-else name="arrow-right" class="text-muted" />
                    </button>
                </li>
            </ul>
        </section>
    </div>
</template>
