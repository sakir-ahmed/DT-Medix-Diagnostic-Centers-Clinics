<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    variant: { type: String, default: 'secondary' },
    size: { type: String, default: 'default' },
    icon: { type: String, default: null },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    disabledReason: { type: String, default: null },
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
    iconOnly: { type: Boolean, default: false },
    label: { type: String, default: null },
});

const variants = {
    primary: 'bg-primary text-primary-foreground hover:opacity-90',
    secondary: 'bg-surface text-foreground border border-border hover:bg-subtle',
    ghost: 'text-foreground hover:bg-subtle',
    accent: 'bg-accent text-accent-foreground hover:opacity-90',
    destructive: 'bg-danger text-white hover:opacity-90',
    'ghost-danger': 'text-danger hover:bg-danger/10',
};

const sizes = {
    xs: 'h-6 px-2 text-xs gap-1',
    default: 'h-7 px-2.5 text-[13px] gap-1.5',
    lg: 'h-9 px-3 text-sm gap-1.5',
    touch: 'h-11 px-4 text-base gap-2',
};

const squares = { xs: 'h-6 w-6', default: 'h-7 w-7', lg: 'h-9 w-9', touch: 'h-11 w-11' };
const iconSizes = { xs: 'sm', default: 'md', lg: 'lg', touch: 'lg' };

const classes = computed(() => [
    'inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-md font-medium transition-colors duration-100 select-none',
    'focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-1 focus-visible:outline-none',
    'disabled:cursor-not-allowed disabled:opacity-50',
    variants[props.variant] ?? variants.secondary,
    props.iconOnly ? `${squares[props.size]} p-0` : sizes[props.size],
]);

const isDisabled = computed(() => props.disabled || props.loading);
</script>

<template>
    <Link
        v-if="href && !isDisabled"
        :href="href"
        :class="classes"
        :aria-label="label ?? undefined"
        :title="iconOnly ? label ?? undefined : undefined"
    >
        <AppIcon v-if="icon" :name="icon" :size="iconSizes[size]" />
        <slot />
    </Link>
    <button
        v-else
        :type="type"
        :class="classes"
        :disabled="isDisabled"
        :aria-busy="loading ? 'true' : undefined"
        :aria-label="label ?? undefined"
        :title="disabled && disabledReason ? disabledReason : iconOnly ? label ?? undefined : undefined"
    >
        <AppIcon v-if="loading" name="spinner" spin :size="iconSizes[size]" />
        <AppIcon v-else-if="icon" :name="icon" :size="iconSizes[size]" />
        <slot />
    </button>
</template>
