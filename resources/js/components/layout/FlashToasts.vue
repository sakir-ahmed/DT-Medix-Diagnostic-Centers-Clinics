<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';
import { notify } from '@/lib/notify';

/**
 * Turns server flash messages into toasts after every visit.
 */
const page = usePage();
let off = null;

function show() {
    const flash = page.props.flash ?? {};
    ['success', 'error', 'warning', 'info'].forEach((type) => {
        if (flash[type]) {
            notify[type](flash[type]);
        }
    });
}

onMounted(() => {
    show();
    off = router.on('success', show);
});

onBeforeUnmount(() => off?.());
</script>

<template>
    <span class="hidden" aria-hidden="true" />
</template>
