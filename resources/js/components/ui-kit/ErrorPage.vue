<script setup>
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';
import UiButton from './UiButton.vue';

const props = defineProps({
    status: { type: [Number, String], default: 500 },
    message: { type: String, default: null },
    homeHref: { type: String, default: '/admin' },
});

const { t, has } = useI18n();

const key = computed(() => (['403', '404', '500', '503', 'offline'].includes(String(props.status)) ? String(props.status) : '500'));
const icons = { 403: 'lock', 404: 'magnifying-glass', 500: 'circle-xmark', 503: 'gear', offline: 'wifi' };
const fallback = {
    403: ['Access denied', 'You do not have permission to open this page.'],
    404: ['Page not found', 'The page does not exist or has moved.'],
    500: ['Something went wrong', 'Please try again in a moment.'],
    503: ['Under maintenance', 'We will be back shortly.'],
    offline: ['You are offline', 'Check the internet connection and try again.'],
};

const reload = () => window.location.reload();

const title = computed(() => (has(`errors.pages.${key.value}.title`) ? t(`errors.pages.${key.value}.title`) : fallback[key.value][0]));
const text = computed(() => props.message ?? (has(`errors.pages.${key.value}.message`) ? t(`errors.pages.${key.value}.message`) : fallback[key.value][1]));
</script>

<template>
    <div class="flex min-h-[60vh] flex-col items-center justify-center gap-3 px-4 text-center">
        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-subtle text-muted">
            <AppIcon :name="icons[key]" size="2xl" />
        </span>
        <p class="text-xs font-semibold tracking-wider text-muted tabular-nums">{{ key === 'offline' ? '' : key }}</p>
        <h1 class="text-lg font-semibold">{{ title }}</h1>
        <p class="max-w-md text-[13px] text-muted">{{ text }}</p>
        <div class="flex gap-2">
            <UiButton icon="arrows-rotate" @click="reload">{{ has('common.retry') ? t('common.retry') : 'Retry' }}</UiButton>
            <UiButton variant="primary" :href="homeHref">{{ has('common.go_dashboard') ? t('common.go_dashboard') : 'Go to dashboard' }}</UiButton>
        </div>
    </div>
</template>
