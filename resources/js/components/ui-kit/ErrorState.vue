<script setup>
import AppIcon from './AppIcon.vue';
import UiButton from './UiButton.vue';

defineProps({
    title: { type: String, default: null },
    message: { type: String, default: null },
    reference: { type: String, default: null },
});

defineEmits(['retry']);
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-2 px-4 py-10 text-center" role="alert">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-danger/10 text-danger">
            <AppIcon name="circle-xmark" size="2xl" />
        </span>
        <p class="text-sm font-semibold">{{ title ?? $t('states.error_title') }}</p>
        <p class="max-w-sm text-[13px] text-muted">{{ message ?? $t('states.error_message') }}</p>
        <p v-if="reference" class="text-xs text-muted">{{ $t('states.reference', { id: reference }) }}</p>
        <UiButton icon="arrows-rotate" @click="$emit('retry')">{{ $t('common.retry') }}</UiButton>
    </div>
</template>
