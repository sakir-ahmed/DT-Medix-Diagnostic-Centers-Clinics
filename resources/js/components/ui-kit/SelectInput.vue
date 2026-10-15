<script setup>
defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number, Boolean, null], default: null });

defineProps({
    options: { type: Array, default: () => [] },
    invalid: { type: Boolean, default: false },
    placeholder: { type: String, default: null },
});
</script>

<template>
    <select
        v-model="model"
        v-bind="$attrs"
        :aria-invalid="invalid ? 'true' : undefined"
        class="control appearance-none bg-[length:12px] bg-[right_8px_center] bg-no-repeat pr-7"
        :class="invalid ? 'control-invalid' : ''"
        style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='%2394a3b8' d='M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z'/%3E%3C/svg%3E&quot;)"
    >
        <option v-if="placeholder !== null" :value="null">{{ placeholder }}</option>
        <option v-for="option in options" :key="String(option.value)" :value="option.value" :disabled="option.disabled">
            {{ option.label }}
        </option>
    </select>
</template>
