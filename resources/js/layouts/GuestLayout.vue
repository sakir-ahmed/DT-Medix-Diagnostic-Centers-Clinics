<script setup>
import { Toaster } from 'vue-sonner';
import FlashToasts from '@/components/layout/FlashToasts.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import LanguageSwitch from '@/components/ui-kit/LanguageSwitch.vue';
import ThemeToggle from '@/components/ui-kit/ThemeToggle.vue';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-background px-4 py-8">
        <div class="absolute top-3 right-3 flex gap-1.5">
            <LanguageSwitch />
            <ThemeToggle />
        </div>
        <div class="mb-4 flex items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-md bg-accent text-accent-foreground"><AppIcon name="heart-pulse" size="lg" /></span>
            <span class="text-lg font-semibold">DT Medix</span>
        </div>
        <div class="w-full max-w-sm rounded-xl border border-border bg-surface p-5 shadow-sm">
            <slot />
        </div>
        <Toaster position="top-center" rich-colors :visible-toasts="3" />
        <FlashToasts />
    </div>
</template>
