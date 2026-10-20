<script setup>
import LanguageSwitch from '@/components/ui-kit/LanguageSwitch.vue';
import ThemeToggle from '@/components/ui-kit/ThemeToggle.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import { useI18n } from '@/lib/i18n';

defineProps({ centerName: { type: String, default: null } });
const { t } = useI18n();
</script>

<template>
    <div class="min-h-screen bg-background text-base">
        <header class="border-b border-border bg-surface">
            <div class="mx-auto flex h-16 max-w-5xl items-center gap-3 px-4">
                <img src="/images/brand/logo-primary.png" alt="" class="h-9 w-auto" />
                <span class="font-display font-semibold">{{ centerName ?? 'DT Medix' }}</span>
                <div class="ml-auto flex gap-1.5">
                    <LanguageSwitch />
                    <ThemeToggle />
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-5xl px-4 py-6">
            <slot />
        </main>
        <footer class="mt-8 border-t border-border bg-surface">
            <div class="mx-auto flex max-w-5xl flex-col gap-2 px-4 py-5 text-[13px] text-muted sm:flex-row sm:items-center sm:justify-between">
                <p class="flex items-center gap-2">
                    <img src="/images/brand/logo-mark.svg" alt="" class="h-5 w-5" />
                    {{ t('public.footer_credit') }}
                </p>
                <p class="flex items-center gap-1.5">
                    <AppIcon name="globe" size="sm" />
                    datatechbd.com
                </p>
            </div>
        </footer>
    </div>
</template>
