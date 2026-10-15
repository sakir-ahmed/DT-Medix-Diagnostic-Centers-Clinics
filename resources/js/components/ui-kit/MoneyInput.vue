<script setup>
import TextInput from './TextInput.vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number, null], default: '' });

defineProps({ invalid: { type: Boolean, default: false } });

function normalize(event) {
    const value = String(event.target.value ?? '').replace(/[^\d.]/g, '');
    if (value === '') {
        model.value = '';
        return;
    }
    const number = Number(value);
    model.value = Number.isFinite(number) ? number.toFixed(2) : '';
}
</script>

<template>
    <TextInput
        v-model="model"
        v-bind="$attrs"
        prefix="৳"
        align="right"
        inputmode="decimal"
        autocomplete="off"
        :invalid="invalid"
        @blur="normalize"
    />
</template>
