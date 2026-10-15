<script setup>
import { DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import AppIcon from './AppIcon.vue';

const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
    size: { type: String, default: 'md' },
    dismissible: { type: Boolean, default: true },
});

const widths = { sm: 'sm:max-w-sm', md: 'sm:max-w-md', lg: 'sm:max-w-lg', xl: 'sm:max-w-2xl' };

function guard(event) {
    if (!props.dismissible) {
        event.preventDefault();
    }
}
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-black/40 backdrop-blur-[2px] data-[state=open]:animate-[fade-in_150ms]" />
            <DialogContent
                class="fixed inset-x-0 bottom-0 z-50 max-h-[90vh] w-full overflow-y-auto rounded-t-xl border border-border bg-surface p-4 shadow-lg focus:outline-none sm:inset-auto sm:top-1/2 sm:left-1/2 sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-xl"
                :class="widths[size] ?? widths.md"
                @escape-key-down="guard"
                @pointer-down-outside="guard"
                @interact-outside="guard"
            >
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <DialogTitle class="text-base font-semibold">{{ title }}</DialogTitle>
                        <DialogDescription v-if="description" class="mt-0.5 text-[13px] text-muted">{{ description }}</DialogDescription>
                    </div>
                    <DialogClose v-if="dismissible" class="rounded-md p-1 text-muted hover:bg-subtle hover:text-foreground" :aria-label="$t('common.close')">
                        <AppIcon name="xmark" />
                    </DialogClose>
                </div>
                <slot />
                <div v-if="$slots.footer" class="mt-4 flex justify-end gap-2">
                    <slot name="footer" />
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
