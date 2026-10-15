<script setup>
import TextInput from './TextInput.vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, null], default: '' });

defineProps({ invalid: { type: Boolean, default: false } });

function onInput(event) {
    let value = String(event.target.value ?? '').replace(/[^\d+]/g, '');
    if (value.startsWith('+880')) {
        value = value.slice(3);
    } else if (value.startsWith('880')) {
        value = value.slice(2);
    }
    model.value = value.slice(0, 11);
}
</script>

<template>
    <TextInput
        :model-value="model"
        v-bind="$attrs"
        icon="mobile-screen"
        inputmode="numeric"
        placeholder="01XXXXXXXXX"
        maxlength="14"
        autocomplete="tel"
        :invalid="invalid"
        @input="onInput"
    />
</template>
