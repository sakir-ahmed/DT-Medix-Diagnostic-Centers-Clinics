<script setup>
import { computed } from 'vue';
import { formatDate, relativeTime } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    value: { type: [String, Date, null], default: null },
    time: { type: Boolean, default: false },
    relative: { type: Boolean, default: false },
});

const { t } = useI18n();
const exact = computed(() => formatDate(props.value, { time: true }));
const text = computed(() => (props.relative ? relativeTime(props.value, t) : formatDate(props.value, { time: props.time })));
</script>

<template>
    <span v-if="!value" class="text-muted">&ndash;</span>
    <time v-else :datetime="String(value)" :title="exact" class="whitespace-nowrap tabular-nums">{{ text }}</time>
</template>
