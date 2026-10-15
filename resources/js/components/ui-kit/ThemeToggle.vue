<script setup>
import { onMounted, ref } from 'vue';
import UiButton from './UiButton.vue';

const dark = ref(false);

onMounted(() => {
    dark.value = document.documentElement.classList.contains('dark');
});

function toggle() {
    dark.value = !dark.value;
    document.documentElement.classList.toggle('dark', dark.value);
    try {
        localStorage.setItem('theme', dark.value ? 'dark' : 'light');
    } catch {
        // Storage unavailable: the choice lasts for this page only.
    }
}
</script>

<template>
    <UiButton variant="ghost" icon-only :icon="dark ? 'sun' : 'moon'" :label="dark ? $t('common.light_mode') : $t('common.dark_mode')" @click="toggle" />
</template>
