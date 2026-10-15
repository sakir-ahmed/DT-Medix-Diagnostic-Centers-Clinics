<script setup>
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { formatNumber, localizeDigits } from '@/lib/format';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';
import DateText from './DateText.vue';
import EmptyState from './EmptyState.vue';
import ErrorState from './ErrorState.vue';
import MoneyText from './MoneyText.vue';
import SelectInput from './SelectInput.vue';
import SkeletonRows from './SkeletonRows.vue';
import StatusBadge from './StatusBadge.vue';
import UiButton from './UiButton.vue';

/**
 * Data table following design.md sections 19 and 25: alignment by data
 * type, sticky header, skeleton/empty/error states, server pagination,
 * sorting synced to the URL, bulk selection and a pinned totals row.
 */
const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: [Array, Object], default: () => [] },
    rowKey: { type: String, default: 'id' },
    loading: { type: Boolean, default: false },
    error: { type: [String, Boolean], default: null },
    emptyTitle: { type: String, default: null },
    emptyDescription: { type: String, default: null },
    emptyIcon: { type: String, default: 'folder-open' },
    filtered: { type: Boolean, default: false },
    selectable: { type: Boolean, default: false },
    totals: { type: Object, default: null },
    sort: { type: String, default: null },
    clickable: { type: Boolean, default: false },
    perPageOptions: { type: Array, default: () => [25, 50, 100] },
    syncUrl: { type: Boolean, default: true },
    label: { type: String, default: null },
});

const emit = defineEmits(['row-click', 'sort', 'retry', 'clear-filters', 'update:selected']);
const { t } = useI18n();

const items = computed(() => (Array.isArray(props.rows) ? props.rows : (props.rows?.data ?? [])));
// Accepts a Laravel paginator or an API resource collection ({ data, meta, links }).
const paginator = computed(() => {
    if (Array.isArray(props.rows) || !props.rows) {
        return null;
    }
    if (props.rows.meta) {
        return { ...props.rows.meta, prev_page_url: props.rows.links?.prev ?? null, next_page_url: props.rows.links?.next ?? null };
    }
    return props.rows;
});
const selected = ref([]);

watch(items, () => {
    selected.value = [];
});
watch(selected, (value) => emit('update:selected', value), { deep: true });

const RIGHT = ['number', 'money', 'percent', 'actions'];
const CENTER = ['bool', 'icon'];
const MIN_WIDTHS = { money: 'min-w-[96px]', number: 'min-w-[64px]', date: 'min-w-[112px]', datetime: 'min-w-[140px]', status: 'min-w-[104px]', actions: 'w-10' };

function align(column) {
    if (RIGHT.includes(column.type)) {
        return 'text-right';
    }
    if (CENTER.includes(column.type)) {
        return 'text-center';
    }
    return 'text-left';
}

function cellClasses(column) {
    return [
        'px-3 py-1.5 align-middle',
        align(column),
        MIN_WIDTHS[column.type] ?? '',
        ['id', 'number', 'money', 'percent', 'date', 'datetime'].includes(column.type) ? 'whitespace-nowrap tabular-nums' : '',
        column.grow ? 'w-full max-w-0' : '',
        column.class ?? '',
    ];
}

function value(row, key) {
    return key.split('.').reduce((node, part) => (node === null || node === undefined ? node : node[part]), row);
}

const currentSort = computed(() => props.sort ?? new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '').get('sort'));

function sortBy(column) {
    if (!column.sortable) {
        return;
    }
    const next = currentSort.value === column.key ? `-${column.key}` : column.key;
    emit('sort', next);
    if (props.syncUrl) {
        visitWith({ sort: next, page: 1 });
    }
}

function sortIcon(column) {
    if (currentSort.value === column.key) {
        return 'sort-up';
    }
    if (currentSort.value === `-${column.key}`) {
        return 'sort-down';
    }
    return 'sort';
}

function visitWith(params) {
    const url = new URL(window.location.href);
    Object.entries(params).forEach(([key, val]) => url.searchParams.set(key, String(val)));
    router.get(url.pathname + url.search, {}, { preserveState: true, preserveScroll: true, replace: true });
}

const allSelected = computed({
    get: () => items.value.length > 0 && selected.value.length === items.value.length,
    set: (checked) => {
        selected.value = checked ? items.value.map((row) => row[props.rowKey]) : [];
    },
});

function onRowClick(row) {
    if (props.clickable) {
        emit('row-click', row);
    }
}

function onRowKey(event, row) {
    if (props.clickable && (event.key === 'Enter' || event.key === ' ')) {
        event.preventDefault();
        emit('row-click', row);
    }
}

const showing = computed(() => {
    if (!paginator.value) {
        return null;
    }
    return t('table.showing', {
        from: localizeDigits(paginator.value.from ?? 0),
        to: localizeDigits(paginator.value.to ?? 0),
        total: formatNumber(paginator.value.total ?? 0),
    });
});

const perPage = computed({
    get: () => Number(paginator.value?.per_page ?? props.perPageOptions[0]),
    set: (val) => visitWith({ per_page: val, page: 1 }),
});

function goTo(url) {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: false });
    }
}
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-border bg-surface">
        <div v-if="selectable && selected.length" class="flex items-center gap-2 border-b border-border bg-accent/10 px-3 py-1.5 text-[13px]">
            <span class="font-medium">{{ t('table.selected', { count: localizeDigits(selected.length) }) }}</span>
            <slot name="bulk-actions" :selected="selected" />
            <UiButton size="xs" variant="ghost" @click="selected = []">{{ t('common.clear') }}</UiButton>
        </div>

        <SkeletonRows v-if="loading" :columns="Math.min(columns.length, 6)" :rows="6" />
        <ErrorState v-else-if="error" :message="typeof error === 'string' ? error : null" @retry="$emit('retry')" />
        <template v-else-if="items.length === 0">
            <EmptyState
                v-if="filtered"
                icon="filter"
                :title="t('table.no_results')"
                :description="t('table.no_results_hint')"
            >
                <UiButton size="default" @click="$emit('clear-filters')">{{ t('table.clear_filters') }}</UiButton>
            </EmptyState>
            <EmptyState v-else :icon="emptyIcon" :title="emptyTitle ?? t('table.empty')" :description="emptyDescription">
                <slot name="empty-actions" />
            </EmptyState>
        </template>
        <template v-else>
            <!-- Desktop table -->
            <div class="hidden max-h-[70vh] overflow-auto md:block">
                <table class="w-full border-collapse text-[13px]" :aria-label="label ?? undefined">
                    <thead class="sticky top-0 z-10 bg-surface">
                        <tr class="border-b border-border">
                            <th v-if="selectable" class="w-10 px-3 py-1.5 text-center">
                                <input v-model="allSelected" type="checkbox" class="h-4 w-4 accent-[var(--color-accent)]" :aria-label="t('table.select_all')" />
                            </th>
                            <th
                                v-for="column in columns"
                                :key="column.key"
                                scope="col"
                                class="h-8 px-3 text-xs font-semibold whitespace-nowrap text-muted"
                                :class="[align(column), MIN_WIDTHS[column.type] ?? '']"
                                :aria-sort="currentSort === column.key ? 'ascending' : currentSort === `-${column.key}` ? 'descending' : undefined"
                            >
                                <button
                                    v-if="column.sortable"
                                    type="button"
                                    class="inline-flex items-center gap-1 hover:text-foreground"
                                    :class="RIGHT.includes(column.type) ? 'flex-row-reverse' : ''"
                                    @click="sortBy(column)"
                                >
                                    <span>{{ column.label }}</span>
                                    <AppIcon :name="sortIcon(column)" size="xs" />
                                </button>
                                <span v-else-if="column.type !== 'actions'">{{ column.label }}</span>
                                <span v-else class="sr-only">{{ t('table.actions') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in items"
                            :key="row[rowKey]"
                            class="border-b border-border last:border-b-0 hover:bg-subtle/60"
                            :class="[clickable ? 'cursor-pointer' : '', selected.includes(row[rowKey]) ? 'bg-accent/5' : '', row._rowClass ?? '']"
                            :tabindex="clickable ? 0 : undefined"
                            @click="onRowClick(row)"
                            @keydown="onRowKey($event, row)"
                        >
                            <td v-if="selectable" class="w-10 px-3 py-1.5 text-center" @click.stop>
                                <input v-model="selected" type="checkbox" :value="row[rowKey]" class="h-4 w-4 accent-[var(--color-accent)]" :aria-label="t('table.select_row')" />
                            </td>
                            <td v-for="column in columns" :key="column.key" :class="cellClasses(column)" @click="column.type === 'actions' ? $event.stopPropagation() : null">
                                <slot :name="`cell-${column.key}`" :row="row" :value="value(row, column.key)">
                                    <MoneyText v-if="column.type === 'money'" :value="value(row, column.key)" />
                                    <span v-else-if="column.type === 'number'">{{ value(row, column.key) === null || value(row, column.key) === undefined ? '' : formatNumber(value(row, column.key), column.decimals ?? 0) }}</span>
                                    <span v-else-if="column.type === 'percent'">{{ formatNumber(value(row, column.key), 1) }}%</span>
                                    <DateText v-else-if="column.type === 'date'" :value="value(row, column.key)" />
                                    <DateText v-else-if="column.type === 'datetime'" :value="value(row, column.key)" time />
                                    <StatusBadge v-else-if="column.type === 'status' && value(row, column.key)" :status="String(value(row, column.key))" />
                                    <AppIcon v-else-if="column.type === 'bool'" :name="value(row, column.key) ? 'circle-check' : 'circle-xmark'" :class="value(row, column.key) ? 'text-success' : 'text-muted'" :label="value(row, column.key) ? t('common.yes') : t('common.no')" />
                                    <slot v-else-if="column.type === 'actions'" name="row-actions" :row="row" />
                                    <span v-else class="block truncate" :title="String(value(row, column.key) ?? '')">{{ value(row, column.key) ?? '' }}</span>
                                </slot>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="totals" class="sticky bottom-0 bg-surface">
                        <tr class="border-t-2 border-border font-semibold">
                            <td v-if="selectable" />
                            <td v-for="(column, index) in columns" :key="column.key" :class="cellClasses(column)">
                                <template v-if="index === 0">{{ t('table.total') }}</template>
                                <MoneyText v-else-if="column.type === 'money' && totals[column.key] !== undefined" :value="totals[column.key]" />
                                <span v-else-if="column.type === 'number' && totals[column.key] !== undefined">{{ formatNumber(totals[column.key]) }}</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Card list on small screens -->
            <ul class="divide-y divide-border md:hidden">
                <li v-for="row in items" :key="row[rowKey]" class="space-y-1 px-3 py-2" :class="clickable ? 'cursor-pointer' : ''" @click="onRowClick(row)">
                    <div v-for="column in columns.filter((c) => c.type !== 'actions')" :key="column.key" class="flex items-center justify-between gap-3">
                        <span class="text-xs text-muted">{{ column.label }}</span>
                        <span class="min-w-0 truncate text-right">
                            <slot :name="`cell-${column.key}`" :row="row" :value="value(row, column.key)">
                                <MoneyText v-if="column.type === 'money'" :value="value(row, column.key)" />
                                <DateText v-else-if="column.type === 'date' || column.type === 'datetime'" :value="value(row, column.key)" :time="column.type === 'datetime'" />
                                <StatusBadge v-else-if="column.type === 'status' && value(row, column.key)" :status="String(value(row, column.key))" />
                                <template v-else>{{ value(row, column.key) ?? '' }}</template>
                            </slot>
                        </span>
                    </div>
                    <div v-if="$slots['row-actions']" class="flex justify-end" @click.stop>
                        <slot name="row-actions" :row="row" />
                    </div>
                </li>
            </ul>
        </template>

        <div v-if="paginator && items.length" class="flex flex-wrap items-center justify-between gap-2 border-t border-border px-3 py-1.5 text-xs text-muted">
            <span>{{ showing }}</span>
            <div class="flex items-center gap-2">
                <label class="sr-only" for="per-page">{{ t('table.per_page') }}</label>
                <SelectInput id="per-page" v-model="perPage" class="!w-20" :options="perPageOptions.map((n) => ({ value: n, label: String(n) }))" />
                <UiButton size="default" icon-only icon="chevron-left" :label="t('table.previous')" :disabled="!paginator.prev_page_url" @click="goTo(paginator.prev_page_url)" />
                <span class="tabular-nums">{{ localizeDigits(paginator.current_page) }} / {{ localizeDigits(paginator.last_page) }}</span>
                <UiButton size="default" icon-only icon="chevron-right" :label="t('table.next')" :disabled="!paginator.next_page_url" @click="goTo(paginator.next_page_url)" />
            </div>
        </div>
    </div>
</template>
