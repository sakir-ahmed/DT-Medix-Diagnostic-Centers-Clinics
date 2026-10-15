<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ModalDialog from '@/components/ui-kit/ModalDialog.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { localizeDigits } from '@/lib/format';

/**
 * Warns 60 seconds before the idle timeout and signs out when it passes.
 */
const page = usePage();
const minutes = computed(() => Number(page.props.app?.idle_timeout ?? 15));
const warning = ref(false);
const secondsLeft = ref(60);
let last = Date.now();
let timer = null;

function activity() {
    if (!warning.value) {
        last = Date.now();
    }
}

function tick() {
    const idle = (Date.now() - last) / 1000;
    const limit = minutes.value * 60;
    if (idle >= limit) {
        clearInterval(timer);
        router.post(route('logout'));
        return;
    }
    if (idle >= limit - 60) {
        warning.value = true;
        secondsLeft.value = Math.max(0, Math.ceil(limit - idle));
    }
}

function stay() {
    warning.value = false;
    last = Date.now();
    router.reload({ only: ['app'] });
}

const events = ['mousemove', 'keydown', 'click', 'touchstart', 'scroll'];

onMounted(() => {
    events.forEach((e) => window.addEventListener(e, activity, { passive: true }));
    timer = setInterval(tick, 1000);
});
onBeforeUnmount(() => {
    events.forEach((e) => window.removeEventListener(e, activity));
    clearInterval(timer);
});
</script>

<template>
    <ModalDialog v-model:open="warning" :title="$t('auth.session_expiring')" :dismissible="false" size="sm">
        <p class="text-[13px]">{{ $t('auth.session_expiring_message', { seconds: localizeDigits(secondsLeft) }) }}</p>
        <template #footer>
            <UiButton @click="router.post(route('logout'))">{{ $t('auth.sign_out') }}</UiButton>
            <UiButton variant="primary" @click="stay">{{ $t('auth.stay_signed_in') }}</UiButton>
        </template>
    </ModalDialog>
</template>
