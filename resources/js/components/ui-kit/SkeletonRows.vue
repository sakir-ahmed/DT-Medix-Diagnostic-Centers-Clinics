<script setup>
defineProps({
    rows: { type: Number, default: 5 },
    columns: { type: Number, default: 4 },
});
</script>

<template>
    <div class="animate-pulse space-y-2 p-3" aria-busy="true" :aria-label="$t('states.loading')">
        <div v-for="row in rows" :key="row" class="flex gap-3">
            <div v-for="column in columns" :key="column" class="h-4 flex-1 rounded bg-subtle" :class="column === 1 ? 'flex-[2]' : ''" />
        </div>
    </div>
</template>
