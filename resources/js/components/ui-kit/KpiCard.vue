<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatCompactMoney, formatMoney, formatNumber } from '@/lib/format';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String, null], default: null },
    format: { type: String, default: 'number' },
    change: { type: [Number, null], default: null },
    invertChange: { type: Boolean, default: false },
    href: { type: String, default: null },
    loading: { type: Boolean, default: false },
    sparkline: { type: Array, default: () => [] },
    icon: { type: String, default: null },
});

const display = computed(() => {
    if (props.value === null || props.value === undefined) {
        return '–';
    }
    if (props.format === 'money') {
        return formatCompactMoney(props.value);
    }
    if (props.format === 'percent') {
        return `${formatNumber(props.value, 1)}%`;
    }
    return formatNumber(props.value);
});

const exact = computed(() => (props.format === 'money' ? formatMoney(props.value) : String(props.value ?? '')));
const good = computed(() => (props.change === null ? null : props.invertChange ? props.change < 0 : props.change >= 0));

const points = computed(() => {
    const values = props.sparkline.map(Number);
    if (values.length < 2) {
        return '';
    }
    const max = Math.max(...values);
    const min = Math.min(...values);
    const range = max - min || 1;

    return values.map((v, i) => `${(i / (values.length - 1)) * 100},${28 - ((v - min) / range) * 26}`).join(' ');
});
</script>

<template>
    <component :is="href ? Link : 'div'" :href="href ?? undefined" class="card block min-w-[160px] transition-colors" :class="href ? 'hover:border-accent' : ''">
        <div class="flex items-center justify-between text-xs text-muted">
            <span class="truncate">{{ label }}</span>
            <AppIcon v-if="icon" :name="icon" size="sm" />
        </div>
        <div v-if="loading" class="mt-2 h-7 w-24 animate-pulse rounded bg-subtle" />
        <div v-else class="mt-1 text-2xl font-bold tracking-tight tabular-nums" :title="exact">{{ display }}</div>
        <div class="mt-1 flex items-center justify-between gap-2">
            <span v-if="change !== null && !loading" class="inline-flex items-center gap-1 text-xs font-medium tabular-nums" :class="good ? 'text-success' : 'text-danger'">
                <AppIcon :name="change >= 0 ? 'arrow-up' : 'arrow-down'" size="xs" />
                {{ formatNumber(Math.abs(change), 1) }}%
            </span>
            <span v-else class="text-xs text-muted">&nbsp;</span>
            <svg v-if="points" viewBox="0 0 100 30" class="h-6 w-20 text-accent" preserveAspectRatio="none" aria-hidden="true">
                <polyline :points="points" fill="none" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke" />
            </svg>
        </div>
    </component>
</template>
