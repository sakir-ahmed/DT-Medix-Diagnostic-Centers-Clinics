<script setup>
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import LanguageSwitch from '@/components/ui-kit/LanguageSwitch.vue';
import ThemeToggle from '@/components/ui-kit/ThemeToggle.vue';

defineProps({ centerName: { type: String, default: null } });
</script>

<template>
    <div class="min-h-screen bg-background text-base">
        <header class="border-b border-border bg-surface">
            <div class="mx-auto flex h-14 max-w-5xl items-center gap-2 px-4">
                <span class="flex h-8 w-8 items-center justify-center rounded-md bg-accent text-accent-foreground"><AppIcon name="heart-pulse" /></span>
                <span class="font-semibold">{{ centerName ?? 'DT Medix' }}</span>
                <div class="ml-auto flex gap-1.5">
                    <LanguageSwitch />
                    <ThemeToggle />
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-5xl px-4 py-6">
            <slot />
        </main>
    </div>
</template>
