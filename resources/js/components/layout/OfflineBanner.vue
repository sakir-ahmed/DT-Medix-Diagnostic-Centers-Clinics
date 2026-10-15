<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';

const offline = ref(false);
const update = () => {
    offline.value = !navigator.onLine;
};

onMounted(() => {
    update();
    window.addEventListener('online', update);
    window.addEventListener('offline', update);
});
onBeforeUnmount(() => {
    window.removeEventListener('online', update);
    window.removeEventListener('offline', update);
});
</script>

<template>
    <div v-if="offline" class="flex items-center justify-center gap-2 bg-danger px-3 py-1 text-xs font-medium text-white" role="alert">
        <AppIcon name="wifi" size="sm" />
        {{ $t('common.offline_banner') }}
    </div>
</template>
