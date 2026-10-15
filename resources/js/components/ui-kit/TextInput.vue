<script setup>
import { ref } from 'vue';
import AppIcon from './AppIcon.vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number, null], default: '' });

defineProps({
    invalid: { type: Boolean, default: false },
    prefix: { type: String, default: null },
    suffix: { type: String, default: null },
    icon: { type: String, default: null },
    clearable: { type: Boolean, default: false },
    align: { type: String, default: 'left' },
    size: { type: String, default: 'default' },
});

const input = ref(null);

defineExpose({ focus: () => input.value?.focus(), select: () => input.value?.select(), el: input });
</script>

<template>
    <div
        class="flex w-full items-stretch overflow-hidden rounded-md border bg-surface focus-within:ring-2"
        :class="[
            invalid ? 'border-danger focus-within:ring-danger/30' : 'border-border hover:border-muted/50 focus-within:border-accent focus-within:ring-accent/30',
            size === 'lg' ? 'h-9' : size === 'touch' ? 'h-11 text-base' : 'h-7',
        ]"
    >
        <span v-if="prefix" class="flex items-center border-r border-border bg-subtle px-2 text-muted">{{ prefix }}</span>
        <span v-if="icon" class="flex items-center pl-2 text-muted"><AppIcon :name="icon" size="sm" /></span>
        <input
            ref="input"
            v-model="model"
            v-bind="$attrs"
            :aria-invalid="invalid ? 'true' : undefined"
            class="h-full w-full min-w-0 border-0 bg-transparent px-2 text-[13px] text-foreground placeholder:text-muted/60 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
            :class="[align === 'right' ? 'text-right tabular-nums' : '', size === 'touch' ? 'text-base' : '']"
        />
        <button
            v-if="clearable && model"
            type="button"
            class="flex items-center px-2 text-muted hover:text-foreground"
            :aria-label="$t('common.clear')"
            @click="model = ''"
        >
            <AppIcon name="xmark" size="sm" />
        </button>
        <span v-if="suffix" class="flex items-center border-l border-border bg-subtle px-2 text-muted">{{ suffix }}</span>
    </div>
</template>
