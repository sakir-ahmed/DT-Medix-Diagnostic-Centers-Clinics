<script setup>
import { DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import AppIcon from './AppIcon.vue';

const open = defineModel('open', { type: Boolean, default: false });

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
    width: { type: String, default: 'md' },
});

const widths = { sm: 'md:w-[30vw] md:min-w-[380px]', md: 'md:w-[36vw] md:min-w-[440px]', lg: 'md:w-[48vw] md:min-w-[560px]' };
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-40 bg-black/30" />
            <DialogContent
                class="fixed inset-y-0 right-0 z-40 flex w-full flex-col border-l border-border bg-surface shadow-xl focus:outline-none"
                :class="widths[width] ?? widths.md"
            >
                <div class="flex h-12 shrink-0 items-center justify-between gap-3 border-b border-border px-4">
                    <div class="min-w-0">
                        <DialogTitle class="truncate text-base font-semibold">{{ title }}</DialogTitle>
                        <DialogDescription v-if="description" class="truncate text-xs text-muted">{{ description }}</DialogDescription>
                    </div>
                    <DialogClose class="rounded-md p-1 text-muted hover:bg-subtle hover:text-foreground" :aria-label="$t('common.close')">
                        <AppIcon name="xmark" />
                    </DialogClose>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    <slot />
                </div>
                <div v-if="$slots.footer" class="flex shrink-0 justify-end gap-2 border-t border-border px-4 py-3">
                    <slot name="footer" />
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
