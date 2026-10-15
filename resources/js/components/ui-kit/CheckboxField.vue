<script setup>
const model = defineModel({ type: [Boolean, Array], default: false });

defineProps({
    label: { type: String, default: null },
    value: { type: [String, Number], default: null },
    disabled: { type: Boolean, default: false },
});
</script>

<template>
    <label class="inline-flex cursor-pointer items-center gap-2 text-[13px]">
        <input v-model="model" type="checkbox" :value="value" :disabled="disabled" class="h-4 w-4 rounded border-border accent-[var(--color-accent)]" />
        <span v-if="label">{{ label }}</span>
        <slot />
    </label>
</template>
