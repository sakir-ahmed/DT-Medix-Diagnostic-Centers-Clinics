<script setup>
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';
import SelectInput from './SelectInput.vue';

/**
 * Date range with presets. v-model is { from: 'YYYY-MM-DD', to: 'YYYY-MM-DD', preset }.
 */
const model = defineModel({ type: Object, default: () => ({ from: null, to: null, preset: null }) });

defineProps({ id: { type: String, default: 'date-range' } });

const { t } = useI18n();

function iso(date) {
    const local = new Date(date.toLocaleString('en-US', { timeZone: 'Asia/Dhaka' }));
    return `${local.getFullYear()}-${String(local.getMonth() + 1).padStart(2, '0')}-${String(local.getDate()).padStart(2, '0')}`;
}

function range(preset) {
    const today = new Date();
    const day = 86400000;
    const now = new Date(today.toLocaleString('en-US', { timeZone: 'Asia/Dhaka' }));
    switch (preset) {
        case 'today':
            return [iso(today), iso(today)];
        case 'yesterday':
            return [iso(new Date(today - day)), iso(new Date(today - day))];
        case '7d':
            return [iso(new Date(today - 6 * day)), iso(today)];
        case 'month':
            return [`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`, iso(today)];
        case 'last_month': {
            const first = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const last = new Date(now.getFullYear(), now.getMonth(), 0);
            const f = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            return [f(first), f(last)];
        }
        default:
            return [model.value.from, model.value.to];
    }
}

const presets = computed(() => ['today', 'yesterday', '7d', 'month', 'last_month', 'custom'].map((value) => ({ value, label: t(`periods.${value}`) })));

const preset = computed({
    get: () => model.value.preset ?? 'custom',
    set: (value) => {
        const [from, to] = range(value);
        model.value = { from, to, preset: value };
    },
});

function setDate(key, value) {
    model.value = { ...model.value, [key]: value, preset: 'custom' };
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <SelectInput :id="`${id}-preset`" v-model="preset" class="!w-36" :options="presets" :aria-label="t('periods.label')" />
        <input :id="`${id}-from`" type="date" class="control !w-36" :value="model.from" :aria-label="t('periods.from')" @change="setDate('from', $event.target.value)" />
        <span class="text-muted">&ndash;</span>
        <input :id="`${id}-to`" type="date" class="control !w-36" :value="model.to" :min="model.from ?? undefined" :aria-label="t('periods.to')" @change="setDate('to', $event.target.value)" />
    </div>
</template>
