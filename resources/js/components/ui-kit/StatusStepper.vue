<script setup>
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';

/**
 * Ordered, Collected, Received, Result entered, Verified, Released.
 */
const props = defineProps({
    steps: { type: Array, default: () => ['ordered', 'collected', 'received', 'entered', 'verified', 'released'] },
    current: { type: String, required: true },
    failed: { type: String, default: null },
    compact: { type: Boolean, default: false },
});

const { t } = useI18n();
const index = computed(() => props.steps.indexOf(props.current));
</script>

<template>
    <ol class="flex items-center gap-1" :aria-label="t('steps.label')">
        <li v-for="(step, i) in steps" :key="step" class="flex items-center gap-1">
            <span
                class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-medium whitespace-nowrap"
                :class="failed && i === index ? 'bg-danger/10 text-danger' : i < index ? 'text-success' : i === index ? 'bg-accent/10 text-accent' : 'text-muted'"
                :aria-current="i === index ? 'step' : undefined"
            >
                <AppIcon :name="failed && i === index ? 'circle-xmark' : i < index ? 'circle-check' : i === index ? 'circle' : 'circle'" :variant="i > index ? 'regular' : 'solid'" size="xs" />
                <span v-if="!compact || i === index">{{ t(`steps.${step}`) }}</span>
            </span>
            <span v-if="i < steps.length - 1" class="h-px w-3 bg-border" aria-hidden="true" />
        </li>
    </ol>
</template>
