<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Toaster } from 'vue-sonner';
import DemoBanner from '@/components/layout/DemoBanner.vue';
import FlashToasts from '@/components/layout/FlashToasts.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import CheckboxField from '@/components/ui-kit/CheckboxField.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import LanguageSwitch from '@/components/ui-kit/LanguageSwitch.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import ThemeToggle from '@/components/ui-kit/ThemeToggle.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const { t } = useI18n();
const form = useForm({ login: '', password: '', remember: false });
const loggingInId = ref(null);

const page = usePage();
const demoUsers = computed(() => page.props.demo_users ?? []);
const points = [
    { icon: 'cash-register', key: 'reception' },
    { icon: 'flask', key: 'lab' },
    { icon: 'chart-line', key: 'books' },
];

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
    <Head :title="t('auth.sign_in')" />
    <DemoBanner />
    <div class="min-h-dvh bg-background p-3 sm:p-4 lg:p-5">
        <div class="mx-auto grid min-h-[calc(100dvh-1.5rem)] max-w-6xl overflow-hidden rounded-xl border border-border lg:min-h-[calc(100dvh-2.5rem)] lg:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.2fr)]">
            <section class="relative hidden flex-col justify-between overflow-hidden bg-primary px-8 py-10 text-primary-foreground lg:flex">
                <div class="pointer-events-none absolute -top-24 -right-16 h-64 w-64 rounded-full bg-accent/30" aria-hidden="true" />
                <div class="pointer-events-none absolute bottom-24 -left-12 h-40 w-40 rounded-full border border-primary-foreground/20" aria-hidden="true" />
                <div class="pointer-events-none absolute right-10 bottom-8 h-16 w-16 rounded-full bg-primary-foreground/10" aria-hidden="true" />

                <Link :href="route('home')" class="relative flex items-center gap-2">
                    <img src="/images/brand/logo-mark.svg" alt="" class="h-8 w-8" />
                    <span class="text-sm font-semibold">DT Medix</span>
                </Link>

                <p class="relative max-w-sm text-2xl font-semibold tracking-tight">{{ t('auth.brand_line') }}</p>

                <ul class="relative space-y-2.5">
                    <li v-for="point in points" :key="point.key" class="flex items-center gap-2.5 text-[13px]">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-primary-foreground/10"><AppIcon :name="point.icon" size="sm" /></span>
                        {{ t(`auth.brand_points.${point.key}`) }}
                    </li>
                </ul>

                <p class="relative text-xs text-primary-foreground/70">{{ t('auth.brand_credit') }}</p>
            </section>

            <section class="relative flex flex-col bg-surface px-5 py-6 sm:px-8 lg:py-10">
                <div class="mb-6 flex items-center justify-between gap-2">
                    <Link :href="route('home')" class="inline-flex items-center gap-1.5 text-[13px] font-medium text-muted hover:text-foreground">
                        <AppIcon name="chevron-left" size="sm" /> {{ t('auth.back_to_site') }}
                    </Link>
                    <div class="flex gap-1.5">
                        <LanguageSwitch />
                        <ThemeToggle />
                    </div>
                </div>

                <div class="mx-auto w-full max-w-sm">
                    <h1 class="text-lg font-semibold">{{ t('auth.sign_in') }}</h1>
                    <p class="mt-0.5 mb-4 text-[13px] text-muted">{{ t('auth.sign_in_hint') }}</p>
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
                </div>

                <div v-if="demoUsers.length > 0" class="mx-auto mt-6 w-full max-w-xl border-t border-border pt-4" role="region" :aria-label="t('auth.demo_accounts')">
                    <h2 class="flex items-center gap-1.5 text-sm font-semibold">
                        <AppIcon name="bolt" size="sm" class="text-accent" />
                        {{ t('auth.demo_accounts') }}
                    </h2>
                    <p class="mt-0.5 text-xs text-muted">{{ t('auth.demo_accounts_hint') }}</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <button
                            v-for="user in demoUsers"
                            :key="user.id"
                            type="button"
                            class="flex cursor-pointer items-center gap-2.5 rounded-md border border-border bg-background px-2.5 py-2 text-left transition-colors duration-100 hover:border-accent hover:bg-subtle focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="loggingInId !== null"
                            :aria-label="t('auth.demo_login_as', { name: user.name, role: user.role })"
                            @click="demoLogin(user)"
                        >
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[13px] font-semibold text-primary">{{ initials(user.name) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] font-semibold">{{ user.name }}</span>
                                <span class="block truncate text-xs text-muted">{{ user.role }} <span aria-hidden="true">/</span> <span class="tabular-nums">{{ user.username }}</span></span>
                            </span>
                            <AppIcon v-if="loggingInId === user.id" name="spinner" spin class="shrink-0 text-muted" />
                            <AppIcon v-else name="chevron-right" size="sm" class="shrink-0 text-muted" />
                        </button>
                    </div>
                </div>
            </section>
        </div>
        <Toaster position="top-center" rich-colors :visible-toasts="3" />
        <FlashToasts />
    </div>
</template>
