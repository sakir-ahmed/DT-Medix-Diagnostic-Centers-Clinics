<script setup>
import AppIcon from './AppIcon.vue';

const model = defineModel({ type: Number, default: 1 });

const props = defineProps({
    min: { type: Number, default: 0 },
    max: { type: Number, default: 9999 },
    touch: { type: Boolean, default: false },
});

const change = (delta) => {
    model.value = Math.min(props.max, Math.max(props.min, Number(model.value || 0) + delta));
};
</script>

<template>
    <div class="inline-flex items-stretch overflow-hidden rounded-md border border-border bg-surface" :class="touch ? 'h-11' : 'h-7'">
        <button type="button" class="px-2 text-muted hover:bg-subtle disabled:opacity-40" :class="touch ? 'w-11' : 'w-7'" :disabled="model <= min" :aria-label="$t('common.decrease')" @click="change(-1)">
            <AppIcon name="minus" size="sm" />
        </button>
        <input v-model.number="model" type="number" inputmode="numeric" :min="min" :max="max" class="w-12 border-x border-border bg-transparent text-center tabular-nums focus:outline-none" />
        <button type="button" class="px-2 text-muted hover:bg-subtle disabled:opacity-40" :class="touch ? 'w-11' : 'w-7'" :disabled="model >= max" :aria-label="$t('common.increase')" @click="change(1)">
            <AppIcon name="plus" size="sm" />
        </button>
    </div>
</template>
