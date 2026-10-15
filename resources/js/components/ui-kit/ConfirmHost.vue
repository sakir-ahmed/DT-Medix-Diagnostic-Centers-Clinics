<script setup>
import { confirmState } from '@/composables/useConfirm';
import ConfirmDialog from './ConfirmDialog.vue';

function settle(result) {
    const resolve = confirmState.resolve;
    confirmState.resolve = null;
    confirmState.open = false;
    confirmState.processing = false;
    resolve?.(result);
}

async function onConfirm(result) {
    const action = confirmState.options.action;
    if (!action) {
        settle(result);
        return;
    }
    confirmState.processing = true;
    confirmState.error = null;
    try {
        await action(result);
        settle(result);
    } catch (error) {
        confirmState.processing = false;
        confirmState.error = error?.message ?? String(error);
    }
}
</script>

<template>
    <ConfirmDialog
        v-if="confirmState.options.title"
        v-model:open="confirmState.open"
        v-bind="{ ...confirmState.options, action: undefined }"
        :processing="confirmState.processing"
        :error="confirmState.error"
        @confirm="onConfirm"
        @cancel="settle({ confirmed: false })"
    />
</template>
