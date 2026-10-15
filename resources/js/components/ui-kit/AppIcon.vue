<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    variant: { type: String, default: 'solid' },
    size: { type: String, default: 'md' },
    spin: { type: Boolean, default: false },
    label: { type: String, default: null },
});

const sizes = { xs: 'text-[10px]', sm: 'text-[12px]', md: 'text-[14px]', lg: 'text-[16px]', xl: 'text-[20px]', '2xl': 'text-[24px]' };
const icon = computed(() => [props.variant === 'regular' ? 'far' : 'fas', props.name]);
</script>

<template>
    <FontAwesomeIcon
        :icon="icon"
        :spin="spin"
        :class="sizes[size] ?? sizes.md"
        :aria-hidden="label ? undefined : 'true'"
        :aria-label="label ?? undefined"
        :role="label ? 'img' : undefined"
        fixed-width
    />
</template>
