<script setup>
defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, null], default: '' });

defineProps({
    invalid: { type: Boolean, default: false },
    rows: { type: Number, default: 3 },
});
</script>

<template>
    <textarea
        v-model="model"
        v-bind="$attrs"
        :rows="rows"
        :aria-invalid="invalid ? 'true' : undefined"
        class="control h-auto resize-y py-1.5 leading-[18px]"
        :class="invalid ? 'control-invalid' : ''"
    />
</template>
