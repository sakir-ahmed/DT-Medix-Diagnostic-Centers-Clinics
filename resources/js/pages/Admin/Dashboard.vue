<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import { useI18n } from '@/lib/i18n';

defineProps({ checklist: { type: Array, default: () => [] } });

const { t } = useI18n();
const page = usePage();
</script>

<template>
    <Head :title="t('nav.dashboard')" />
    <PageHeader :title="t('dashboard.welcome', { name: page.props.auth?.user?.name ?? '' })" :description="t('dashboard.setup_hint')" />
    <section class="card max-w-xl">
        <h2 class="mb-2 text-sm font-semibold">{{ t('dashboard.setup_checklist') }}</h2>
        <ul class="space-y-1.5">
            <li v-for="item in checklist" :key="item.key" class="flex items-center gap-2 text-[13px]">
                <AppIcon :name="item.done ? 'circle-check' : 'circle'" :variant="item.done ? 'solid' : 'regular'" :class="item.done ? 'text-success' : 'text-muted'" />
                <span :class="item.done ? 'text-muted line-through' : ''">{{ t(`dashboard.checklist.${item.key}`) }}</span>
            </li>
        </ul>
    </section>
</template>
