<script setup>
import AppIcon from './AppIcon.vue';

const model = defineModel({ type: [String, Number, null], default: null });

defineProps({
    options: { type: Array, required: true },
    name: { type: String, required: true },
    touch: { type: Boolean, default: false },
    columns: { type: Number, default: 3 },
});
</script>

<template>
    <div role="radiogroup" class="grid gap-2" :style="{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }">
        <label
            v-for="option in options"
            :key="option.value"
            class="flex cursor-pointer items-center gap-2 rounded-md border px-2 transition-colors"
            :class="[
                model === option.value ? 'border-accent bg-accent/10 text-foreground' : 'border-border bg-surface hover:bg-subtle',
                touch ? 'h-11 text-base' : 'h-7 text-[13px]',
                option.disabled ? 'cursor-not-allowed opacity-50' : '',
            ]"
        >
            <input v-model="model" type="radio" class="sr-only" :name="name" :value="option.value" :disabled="option.disabled" />
            <AppIcon v-if="option.icon" :name="option.icon" size="sm" />
            <span class="truncate">{{ option.label }}</span>
        </label>
    </div>
</template>
