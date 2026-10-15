<script setup>
const model = defineModel({ type: Boolean, default: false });

defineProps({
    id: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    disabled: { type: Boolean, default: false },
});
</script>

<template>
    <label class="flex cursor-pointer items-start gap-2" :class="disabled ? 'cursor-not-allowed opacity-60' : ''">
        <button
            :id="id ?? undefined"
            type="button"
            role="switch"
            :aria-checked="model ? 'true' : 'false'"
            :disabled="disabled"
            class="relative mt-0.5 inline-flex h-[18px] w-8 shrink-0 items-center rounded-full transition-colors focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none"
            :class="model ? 'bg-accent' : 'bg-border'"
            @click="model = !model"
        >
            <span class="inline-block h-3.5 w-3.5 rounded-full bg-white shadow transition-transform" :class="model ? 'translate-x-4' : 'translate-x-0.5'" />
        </button>
        <span v-if="label || description" class="leading-tight">
            <span class="block text-[13px] font-medium">{{ label }}</span>
            <span v-if="description" class="block text-xs text-muted">{{ description }}</span>
        </span>
    </label>
</template>
