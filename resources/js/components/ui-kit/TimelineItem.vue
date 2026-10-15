<script setup>
import AppIcon from './AppIcon.vue';
import DateText from './DateText.vue';
import StatusBadge from './StatusBadge.vue';

defineProps({
    date: { type: String, required: true },
    icon: { type: String, default: 'circle' },
    title: { type: String, required: true },
    summary: { type: String, default: null },
    status: { type: String, default: null },
    href: { type: String, default: null },
    last: { type: Boolean, default: false },
});
</script>

<template>
    <li class="relative flex gap-3 pb-4">
        <span v-if="!last" class="absolute top-7 left-3.5 h-[calc(100%-1.5rem)] w-px bg-border" aria-hidden="true" />
        <span class="z-0 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-subtle text-muted">
            <AppIcon :name="icon" size="sm" />
        </span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <component :is="href ? 'a' : 'span'" :href="href ?? undefined" class="text-[13px] font-medium" :class="href ? 'hover:text-accent hover:underline' : ''">{{ title }}</component>
                <StatusBadge v-if="status" :status="status" />
            </div>
            <p v-if="summary" class="truncate text-xs text-muted">{{ summary }}</p>
            <DateText :value="date" time class="text-xs text-muted" />
        </div>
    </li>
</template>
