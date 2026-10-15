<script setup>
import { computed, ref, watch } from 'vue';
import { computeFlag, deltaExceeded, evaluateFormula } from '@/lib/flags';
import { formatDate } from '@/lib/format';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';

/**
 * Keyboard-first result entry grid (design.md section 9). Flags appear
 * instantly with text and icon; calculated parameters are read-only.
 * v-model: { [rowId]: { value, remarks } }
 */
const model = defineModel({ type: Object, default: () => ({}) });

const props = defineProps({
    rows: { type: Array, required: true },
    readonly: { type: Boolean, default: false },
    deltaPercent: { type: [Number, String], default: 50 },
});

const emit = defineEmits(['flags', 'save-next']);
const { t } = useI18n();
const inputs = ref({});

function entry(row) {
    if (!model.value[row.id]) {
        model.value[row.id] = { value: row.value ?? '', remarks: row.remarks ?? '' };
    }
    return model.value[row.id];
}

const valuesByCode = computed(() => Object.fromEntries(props.rows.map((row) => [row.code, model.value[row.id]?.value ?? row.value ?? ''])));

watch(
    valuesByCode,
    () => {
        props.rows
            .filter((row) => row.result_type === 'calculated')
            .forEach((row) => {
                const result = evaluateFormula(row.formula, valuesByCode.value);
                const current = entry(row);
                const next = result === null ? '' : String(result);
                if (current.value !== next) {
                    current.value = next;
                }
            });
    },
    { immediate: true, deep: true },
);

const flags = computed(() => Object.fromEntries(props.rows.map((row) => [row.id, computeFlag(model.value[row.id]?.value ?? row.value, { ...(row.range ?? {}), result_type: row.result_type })])));

watch(flags, (value) => emit('flags', value), { immediate: true, deep: true });

const flagStyle = {
    C: { icon: 'triangle-exclamation', class: 'text-danger font-bold', label: 'flags.critical' },
    H: { icon: 'arrow-up', class: 'text-danger font-semibold', label: 'flags.high' },
    L: { icon: 'arrow-down', class: 'text-danger font-semibold', label: 'flags.low' },
    A: { icon: 'circle-exclamation', class: 'text-danger font-semibold', label: 'flags.abnormal' },
    N: { icon: 'circle-check', class: 'text-success', label: 'flags.normal' },
};

const editable = computed(() => props.rows.filter((row) => row.result_type !== 'calculated').map((row) => row.id));

function focusNext(rowId) {
    const index = editable.value.indexOf(rowId);
    const next = editable.value[index + 1];
    if (next !== undefined) {
        inputs.value[next]?.focus();
        inputs.value[next]?.select?.();
    }
}

function onKey(event, row) {
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        emit('save-next');
    } else if (event.key === 'Enter') {
        event.preventDefault();
        focusNext(row.id);
    }
}

function onChoiceKey(event, row) {
    if (event.key.length === 1 && /[a-z]/i.test(event.key) && row.choices?.length) {
        const match = row.choices.find((choice) => choice.toLowerCase().startsWith(event.key.toLowerCase()));
        if (match) {
            event.preventDefault();
            entry(row).value = match;
        }
    }
    onKey(event, row);
}

const delta = (row) => row.previous && deltaExceeded(model.value[row.id]?.value, row.previous.value, props.deltaPercent);

defineExpose({ focusFirst: () => inputs.value[editable.value[0]]?.focus(), flags });
</script>

<template>
    <div class="overflow-x-auto rounded-lg border border-border bg-surface">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="h-8 border-b border-border text-xs text-muted">
                    <th class="px-3 text-left font-semibold">{{ t('results.parameter') }}</th>
                    <th class="min-w-[120px] px-3 text-right font-semibold">{{ t('results.result') }}</th>
                    <th class="px-3 text-left font-semibold">{{ t('results.unit') }}</th>
                    <th class="px-3 text-left font-semibold">{{ t('results.reference') }}</th>
                    <th class="min-w-[90px] px-3 text-left font-semibold">{{ t('results.flag') }}</th>
                    <th class="px-3 text-right font-semibold">{{ t('results.previous') }}</th>
                    <th class="min-w-[140px] px-3 text-left font-semibold">{{ t('results.remarks') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id" class="border-b border-border last:border-0" :class="flags[row.id] === 'C' ? 'bg-danger/10' : ''">
                    <td class="px-3 py-1">
                        <label :for="`result-${row.id}`" class="font-medium">{{ row.name }}</label>
                        <span v-if="row.result_type === 'calculated'" class="ml-1 text-xs text-muted">({{ t('results.calculated') }})</span>
                    </td>
                    <td class="px-3 py-1 text-right">
                        <template v-if="readonly || row.result_type === 'calculated'">
                            <span class="tabular-nums" :class="flagStyle[flags[row.id]]?.class">{{ entry(row).value || '–' }}</span>
                        </template>
                        <select
                            v-else-if="row.result_type === 'choice'"
                            :id="`result-${row.id}`"
                            :ref="(el) => (inputs[row.id] = el)"
                            v-model="entry(row).value"
                            class="control text-right"
                            @keydown="onChoiceKey($event, row)"
                        >
                            <option value="" />
                            <option v-for="choice in row.choices" :key="choice" :value="choice">{{ choice }}</option>
                        </select>
                        <input
                            v-else
                            :id="`result-${row.id}`"
                            :ref="(el) => (inputs[row.id] = el)"
                            v-model="entry(row).value"
                            :inputmode="row.result_type === 'number' ? 'decimal' : 'text'"
                            autocomplete="off"
                            class="control"
                            :class="row.result_type === 'number' ? 'text-right tabular-nums' : ''"
                            @keydown="onKey($event, row)"
                        />
                    </td>
                    <td class="px-3 py-1 text-muted">{{ row.unit }}</td>
                    <td class="px-3 py-1 whitespace-nowrap text-muted tabular-nums">{{ row.range?.text ?? '' }}</td>
                    <td class="px-3 py-1 whitespace-nowrap">
                        <span v-if="flags[row.id] && flags[row.id] !== 'N'" class="inline-flex items-center gap-1" :class="flagStyle[flags[row.id]].class" role="status">
                            <AppIcon :name="flagStyle[flags[row.id]].icon" size="sm" />
                            {{ t(flagStyle[flags[row.id]].label) }}
                        </span>
                        <span v-else-if="flags[row.id] === 'N'" class="inline-flex items-center gap-1 text-success"><AppIcon name="circle-check" size="sm" />{{ t('flags.normal') }}</span>
                        <span v-if="delta(row)" class="ml-1 inline-flex items-center gap-1 text-xs text-warning" :title="t('results.delta_warning')">
                            <AppIcon name="triangle-exclamation" size="xs" />{{ t('results.delta') }}
                        </span>
                    </td>
                    <td class="px-3 py-1 text-right whitespace-nowrap tabular-nums">
                        <template v-if="row.previous">
                            <span>{{ row.previous.value }}</span>
                            <span class="ml-1 text-xs text-muted">{{ formatDate(row.previous.date) }}</span>
                        </template>
                        <span v-else class="text-muted">&ndash;</span>
                    </td>
                    <td class="px-3 py-1">
                        <span v-if="readonly" class="text-muted">{{ entry(row).remarks }}</span>
                        <input v-else v-model="entry(row).remarks" class="control" tabindex="-1" :aria-label="t('results.remarks')" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
