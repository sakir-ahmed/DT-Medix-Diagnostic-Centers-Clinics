<script setup>
import { Link } from '@inertiajs/vue3';
import AppIcon from './AppIcon.vue';

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
    breadcrumbs: { type: Array, default: () => [] },
});
</script>

<template>
    <header class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <nav v-if="breadcrumbs.length" :aria-label="$t('common.breadcrumbs')" class="mb-0.5 flex items-center gap-1 text-xs text-muted">
                <template v-for="(crumb, index) in breadcrumbs" :key="index">
                    <Link v-if="crumb.href" :href="crumb.href" class="hover:text-foreground">{{ crumb.label }}</Link>
                    <span v-else>{{ crumb.label }}</span>
                    <AppIcon v-if="index < breadcrumbs.length - 1" name="chevron-right" size="xs" />
                </template>
            </nav>
            <h1 class="truncate text-lg font-semibold">{{ title }}</h1>
            <p v-if="description" class="text-[13px] text-muted">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </header>
</template>
