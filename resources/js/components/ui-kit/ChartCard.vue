<script setup>
import {
    ArcElement, BarElement, CategoryScale, Chart as ChartJS, Filler, Legend, LinearScale, LineElement, PointElement, Tooltip,
} from 'chart.js';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { formatMoney, formatNumber } from '@/lib/format';
import { useI18n } from '@/lib/i18n';
import EmptyState from './EmptyState.vue';
import ErrorState from './ErrorState.vue';
import UiButton from './UiButton.vue';

ChartJS.register(ArcElement, BarElement, CategoryScale, Filler, Legend, LinearScale, LineElement, PointElement, Tooltip);

/**
 * The only chart wrapper (design.md section 16). Colors come from CSS tokens
 * so dark mode works. Includes table view, PNG/CSV export and all states.
 *
 * datasets: [{ label, data: [...], dashed?, band?: { min: [...], max: [...] } }]
 */
const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    type: { type: String, default: 'line' },
    labels: { type: Array, default: () => [] },
    datasets: { type: Array, default: () => [] },
    horizontal: { type: Boolean, default: false },
    money: { type: Boolean, default: false },
    unit: { type: String, default: null },
    loading: { type: Boolean, default: false },
    error: { type: [String, Boolean], default: null },
    height: { type: Number, default: 240 },
    centerLabel: { type: String, default: null },
});

defineEmits(['retry']);

const { t } = useI18n();
const showTable = ref(false);
const chartRef = ref(null);
const themeTick = ref(0);
let observer = null;

onMounted(() => {
    observer = new MutationObserver(() => themeTick.value++);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onBeforeUnmount(() => observer?.disconnect());

function token(name, fallback) {
    if (typeof window === 'undefined') {
        return fallback;
    }
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
}

// Color-blind-safe series palette (Okabe-Ito based), max 6 series.
const PALETTE = ['#0F766E', '#E69F00', '#56B4E9', '#CC79A7', '#0072B2', '#D55E00'];

const isEmpty = computed(() => props.datasets.length === 0 || props.datasets.every((d) => (d.data ?? []).every((v) => v === null || Number(v) === 0)));

const formatValue = (value) => (props.money ? formatMoney(value) : `${formatNumber(value, Number.isInteger(Number(value)) ? 0 : 1)}${props.unit ? ` ${props.unit}` : ''}`);

const chartData = computed(() => {
    themeTick.value;
    const sets = [];
    props.datasets.slice(0, 6).forEach((dataset, index) => {
        const color = dataset.color ?? PALETTE[index % PALETTE.length];
        if (dataset.band) {
            sets.push(
                { label: `${dataset.label} min`, data: dataset.band.min, borderWidth: 0, pointRadius: 0, fill: false, backgroundColor: 'transparent', band: true },
                { label: `${dataset.label} max`, data: dataset.band.max, borderWidth: 0, pointRadius: 0, fill: '-1', backgroundColor: `${PALETTE[0]}22`, band: true },
            );
        }
        sets.push({
            label: dataset.label,
            data: dataset.data,
            borderColor: props.type === 'doughnut' ? token('--color-surface', '#fff') : color,
            backgroundColor: props.type === 'doughnut' ? dataset.data.map((_, i) => PALETTE[i % PALETTE.length]) : props.type === 'bar' ? color : `${color}33`,
            borderDash: dataset.dashed ? [5, 4] : undefined,
            fill: props.type === 'line' && dataset.fill ? 'origin' : false,
            tension: 0.3,
            pointRadius: dataset.pointRadius ?? 2,
            pointStyle: dataset.pointStyles ?? 'circle',
            pointBackgroundColor: dataset.pointColors ?? color,
        });
    });
    return { labels: props.labels, datasets: sets };
});

const reducedMotion = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

const options = computed(() => {
    themeTick.value;
    const muted = token('--color-muted', '#64748b');
    const border = token('--color-border', '#e2e8f0');
    const base = {
        responsive: true,
        maintainAspectRatio: false,
        animation: reducedMotion ? false : { duration: 400 },
        indexAxis: props.horizontal ? 'y' : 'x',
        plugins: {
            legend: {
                display: props.type === 'doughnut' || props.datasets.length > 1,
                labels: { color: muted, boxWidth: 10, font: { size: 11 }, filter: (item, data) => !data.datasets[item.datasetIndex]?.band },
            },
            tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label ?? ''}: ${formatValue(ctx.parsed?.y ?? ctx.parsed?.x ?? ctx.parsed)}` } },
        },
    };
    if (props.type === 'doughnut') {
        return { ...base, cutout: '65%' };
    }
    const valueAxis = { beginAtZero: props.type === 'bar', ticks: { color: muted, font: { size: 11 }, callback: (v) => (props.money ? formatNumber(v) : formatNumber(v)) }, grid: { color: border } };
    const categoryAxis = { ticks: { color: muted, font: { size: 11 }, autoSkip: true, maxRotation: 45 }, grid: { display: false } };
    return { ...base, scales: props.horizontal ? { x: valueAxis, y: categoryAxis } : { x: categoryAxis, y: valueAxis } };
});

const component = computed(() => ({ line: Line, bar: Bar, doughnut: Doughnut })[props.type] ?? Line);

const summary = computed(() => {
    const parts = props.datasets.map((d) => `${d.label}: ${(d.data ?? []).map((v, i) => `${props.labels[i]} ${formatValue(v)}`).join(', ')}`);
    return `${props.title}. ${parts.join('. ')}`;
});

function exportPng() {
    const chart = chartRef.value?.chart;
    if (!chart) {
        return;
    }
    const link = document.createElement('a');
    link.href = chart.toBase64Image();
    link.download = `${props.title}.png`;
    link.click();
}

function exportCsv() {
    const header = ['', ...props.datasets.map((d) => d.label)];
    const rows = props.labels.map((label, i) => [label, ...props.datasets.map((d) => d.data[i] ?? '')]);
    const csv = [header, ...rows].map((r) => r.map((c) => `"${String(c).replaceAll('"', '""')}"`).join(',')).join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([`﻿${csv}`], { type: 'text/csv;charset=utf-8' }));
    link.download = `${props.title}.csv`;
    link.click();
}
</script>

<template>
    <section class="card">
        <header class="mb-2 flex items-start justify-between gap-2">
            <div class="min-w-0">
                <h3 class="truncate text-sm font-semibold">{{ title }}</h3>
                <p v-if="subtitle" class="truncate text-xs text-muted">{{ subtitle }}</p>
            </div>
            <div class="flex shrink-0 gap-1">
                <UiButton size="xs" variant="ghost" icon-only :icon="showTable ? 'chart-line' : 'table-list'" :label="showTable ? t('charts.view_chart') : t('charts.view_table')" @click="showTable = !showTable" />
                <UiButton size="xs" variant="ghost" icon-only icon="image" :label="t('charts.export_png')" :disabled="isEmpty || showTable" @click="exportPng" />
                <UiButton size="xs" variant="ghost" icon-only icon="file-export" :label="t('charts.export_csv')" :disabled="isEmpty" @click="exportCsv" />
            </div>
        </header>
        <div v-if="loading" class="animate-pulse rounded bg-subtle" :style="{ height: `${height}px` }" />
        <ErrorState v-else-if="error" :message="typeof error === 'string' ? error : null" @retry="$emit('retry')" />
        <EmptyState v-else-if="isEmpty" icon="chart-column" :title="t('charts.no_data')" />
        <div v-else-if="showTable" class="overflow-auto" :style="{ maxHeight: `${height}px` }">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="border-b border-border text-xs text-muted">
                        <th class="px-2 py-1 text-left font-semibold" />
                        <th v-for="d in datasets" :key="d.label" class="px-2 py-1 text-right font-semibold">{{ d.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(label, i) in labels" :key="i" class="border-b border-border last:border-0">
                        <td class="px-2 py-1">{{ label }}</td>
                        <td v-for="d in datasets" :key="d.label" class="px-2 py-1 text-right tabular-nums">{{ formatValue(d.data[i]) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="relative" :style="{ height: `${height}px` }" role="img" :aria-label="summary">
            <component :is="component" ref="chartRef" :data="chartData" :options="options" />
            <div v-if="type === 'doughnut' && centerLabel" class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm font-semibold">
                {{ centerLabel }}
            </div>
        </div>
    </section>
</template>
