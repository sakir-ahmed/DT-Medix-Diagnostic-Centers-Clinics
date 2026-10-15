<script setup>
import ConfirmDialog from './ConfirmDialog.vue';

/**
 * Declarative ConfirmDialog with level "approval". Prefer useConfirm() in pages.
 */
const open = defineModel('open', { type: Boolean, default: false });

defineEmits(['confirm', 'cancel']);
</script>

<template>
    <ConfirmDialog v-model:open="open" v-bind="$attrs" level="approval" @confirm="(result) => $emit('confirm', result)" @cancel="$emit('cancel')" />
</template>
