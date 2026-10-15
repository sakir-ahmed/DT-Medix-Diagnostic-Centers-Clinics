<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ErrorPage from '@/components/ui-kit/ErrorPage.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: null });

const props = defineProps({
    status: { type: [Number, String], default: 500 },
    message: { type: String, default: null },
});

const page = usePage();
const layout = computed(() => (page.props.auth?.user && page.props.tenant ? AdminLayout : GuestLayout));
const home = computed(() => (page.props.auth?.user?.is_super_admin ? '/platform' : page.props.auth?.user ? '/admin' : '/'));
</script>

<template>
    <Head :title="String(props.status)" />
    <component :is="layout">
        <ErrorPage :status="status" :message="message" :home-href="home" />
    </component>
</template>
