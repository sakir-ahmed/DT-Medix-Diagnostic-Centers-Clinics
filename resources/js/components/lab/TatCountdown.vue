<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from '@/lib/i18n';
import AppIcon from '@/components/ui-kit/AppIcon.vue';

/**
 * Live countdown to the target turnaround time (design.md section 9):
 * overdue shows a danger triangle, under an hour a warning clock.
 */
const props = defineProps({
    dueAt: { type: String, default: null },
});

const { t } = useI18n();
const now = ref(Date.now());
let timer = null;

onMounted(() => {
    timer = setInterval(() => {
        now.value = Date.now();
    }, 30000);
});

onBeforeUnmount(() => {
    clearInterval(timer);
});

const minutesLeft = computed(() => {
    if (!props.dueAt) {
        return null;
    }
    return Math.round((new Date(props.dueAt).getTime() - now.value) / 60000);
});

const text = computed(() => {
    const minutes = minutesLeft.value;

    if (minutes === null) {
        return '';
    }

    const absolute = Math.abs(minutes);

    const value =
        absolute >= 60 * 24
            ? t('time.days_hours', { days: Math.floor(absolute / 1440), hours: Math.round((absolute % 1440) / 60) })
            : absolute >= 60
              ? t('time.hours_minutes', { hours: Math.floor(absolute / 60), minutes: absolute % 60 })
              : t('time.minutes', { count: absolute });

    return minutes < 0 ? t('lab.overdue', { time: value }) : t('lab.due_in', { time: value });
});

const tone = computed(() => {
    const minutes = minutesLeft.value;

    if (minutes === null) {
        return 'text-muted';
    }

    if (minutes < 0) {
        return 'text-danger';
    }

    return minutes < 60 ? 'text-warning' : 'text-muted';
});

const icon = computed(() => {
    const minutes = minutesLeft.value;

    if (minutes === null) {
        return 'clock';
    }

    return minutes < 0 ? 'triangle-exclamation' : 'clock';
});
</script>

<template>
    <span class="inline-flex items-center gap-1 whitespace-nowrap tabular-nums" :class="tone" role="status">
        <AppIcon :name="icon" size="sm" />
        {{ text }}
    </span>
</template>
