<script setup>
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';
import AdminLayout from './AdminLayout.vue';

const { t } = useI18n();

const navigation = computed(() => [
    {
        group: t('platform.title'),
        items: [{ key: 'tenants', label: t('platform.tenants'), href: route('platform.tenants.index'), icon: 'building', route: 'platform.tenants.index' }],
    },
]);
</script>

<template>
    <AdminLayout platform :navigation="navigation">
        <slot />
    </AdminLayout>
</template>
