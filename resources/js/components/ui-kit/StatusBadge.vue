<script setup>
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';

/**
 * One badge for every status enum: color + icon + label.
 * Labels come from lang "statuses.{value}" unless a label is given.
 */
const props = defineProps({
    status: { type: String, required: true },
    label: { type: String, default: null },
    tone: { type: String, default: null },
    icon: { type: String, default: null },
});

const { t, has } = useI18n();

const map = {
    success: ['paid', 'completed', 'active', 'released', 'verified', 'normal', 'received', 'approved', 'collected', 'sent', 'delivered', 'present', 'arrived', 'closed', 'done', 'valid', 'yes'],
    warning: ['partial', 'pending', 'trial', 'booked', 'in-consultation', 'entered', 'draft', 'ordered', 'requested', 'in_transit', 'assigned', 'queued', 'open', 'high', 'low', 'processing', 'read_only'],
    danger: ['due', 'overdue', 'cancelled', 'rejected', 'critical', 'suspended', 'failed', 'no-show', 'refunded', 'breached', 'expired', 'returned', 'no'],
    info: ['amended', 'new', 'scheduled', 'info'],
};
const icons = { success: 'circle-check', warning: 'clock', danger: 'circle-xmark', info: 'circle-info', muted: 'circle' };
const tones = {
    success: 'bg-success/10 text-success',
    warning: 'bg-warning/15 text-[oklch(0.5_0.13_70)] dark:text-warning',
    danger: 'bg-danger/10 text-danger',
    info: 'bg-info/10 text-info',
    muted: 'bg-subtle text-muted',
};

const resolvedTone = computed(() => props.tone ?? Object.keys(map).find((tone) => map[tone].includes(props.status)) ?? 'muted');
const resolvedLabel = computed(() => props.label ?? (has(`statuses.${props.status}`) ? t(`statuses.${props.status}`) : props.status));
</script>

<template>
    <span class="inline-flex h-5 min-w-[72px] items-center gap-1 rounded-md px-2 text-[11px] font-medium whitespace-nowrap" :class="tones[resolvedTone]">
        <AppIcon :name="icon ?? icons[resolvedTone]" size="xs" />
        {{ resolvedLabel }}
    </span>
</template>
