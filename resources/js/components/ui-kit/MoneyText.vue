<script setup>
import { computed } from 'vue';
import { formatMoney } from '@/lib/format';

const props = defineProps({
    value: { type: [String, Number, null], default: null },
    symbol: { type: Boolean, default: false },
    decimals: { type: Number, default: 2 },
    signed: { type: Boolean, default: true },
});

const number = computed(() => Number(props.value ?? 0));
const isZero = computed(() => props.value === null || props.value === '' || number.value === 0);
</script>

<template>
    <span v-if="isZero" class="text-muted tabular-nums">&ndash;</span>
    <span v-else class="tabular-nums whitespace-nowrap" :class="signed && number < 0 ? 'text-danger' : ''">
        {{ formatMoney(value, { symbol, decimals }) }}
    </span>
</template>
