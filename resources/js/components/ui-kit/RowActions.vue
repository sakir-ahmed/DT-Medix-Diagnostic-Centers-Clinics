<script setup>
import { DropdownMenuContent, DropdownMenuItem, DropdownMenuPortal, DropdownMenuRoot, DropdownMenuSeparator, DropdownMenuTrigger } from 'reka-ui';
import AppIcon from './AppIcon.vue';

/**
 * Ellipsis menu for table rows. Destructive items go last in danger color.
 * items: [{ label, icon, onSelect, danger, disabled, separator }]
 */
defineProps({
    items: { type: Array, required: true },
    label: { type: String, default: null },
});
</script>

<template>
    <DropdownMenuRoot>
        <DropdownMenuTrigger
            class="inline-flex h-6 w-6 items-center justify-center rounded-md text-muted hover:bg-subtle hover:text-foreground focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none"
            :aria-label="label ?? $t('table.actions')"
            @click.stop
        >
            <AppIcon name="ellipsis" />
        </DropdownMenuTrigger>
        <DropdownMenuPortal>
            <DropdownMenuContent align="end" :side-offset="4" class="z-50 min-w-40 rounded-md border border-border bg-surface p-1 text-[13px] shadow-md">
                <template v-for="(item, index) in items" :key="index">
                    <DropdownMenuSeparator v-if="item.separator" class="my-1 h-px bg-border" />
                    <DropdownMenuItem
                        v-else
                        :disabled="item.disabled"
                        class="flex h-7 cursor-pointer items-center gap-2 rounded px-2 outline-none select-none data-[disabled]:cursor-not-allowed data-[disabled]:opacity-50 data-[highlighted]:bg-subtle"
                        :class="item.danger ? 'text-danger' : ''"
                        @select="item.onSelect?.()"
                    >
                        <AppIcon v-if="item.icon" :name="item.icon" size="sm" />
                        {{ item.label }}
                    </DropdownMenuItem>
                </template>
            </DropdownMenuContent>
        </DropdownMenuPortal>
    </DropdownMenuRoot>
</template>
