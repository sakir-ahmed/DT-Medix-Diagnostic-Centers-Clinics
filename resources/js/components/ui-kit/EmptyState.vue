<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
    icon: { type: String, default: 'folder-open' },
    title: { type: String, required: true },
    description: { type: String, default: null },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-2 px-4 py-10 text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-subtle text-muted">
            <AppIcon :name="icon" :variant="icon === 'folder-open' ? 'regular' : 'solid'" size="2xl" />
        </span>
        <p class="text-sm font-semibold">{{ title }}</p>
        <p v-if="description" class="max-w-sm text-[13px] text-muted">{{ description }}</p>
        <div v-if="$slots.default" class="mt-1 flex gap-2"><slot /></div>
    </div>
</template>
