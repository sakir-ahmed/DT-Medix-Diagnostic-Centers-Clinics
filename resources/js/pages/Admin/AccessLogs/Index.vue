<script setup>
import { Head, router } from '@inertiajs/vue3';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import { useI18n } from '@/lib/i18n';

defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const actions = ['view', 'update', 'photo', 'report', 'history', 'download'].map((value) => ({ value, label: t(`access_log.actions.${value}`) }));

const columns = [
    { key: 'created_at', label: t('access_log.time'), type: 'datetime' },
    { key: 'user', label: t('access_log.user'), type: 'text', grow: true },
    { key: 'patient_code', label: t('patients.patient_id'), type: 'id' },
    { key: 'action', label: t('access_log.action'), type: 'text' },
    { key: 'channel', label: t('access_log.channel'), type: 'text' },
    { key: 'ip_address', label: t('access_log.ip'), type: 'id' },
];
</script>

<template>
    <div>
        <Head :title="t('nav.access_log')" />
        <PageHeader :title="t('nav.access_log')" :description="t('access_log.description')" />
        <FilterBar v-slot="{ form }" :filters="filters" search-key="patient" :search-placeholder="t('access_log.search')">
            <SelectInput v-model="form.action" class="!w-40" :placeholder="t('access_log.all_actions')" :options="actions" :aria-label="t('access_log.action')" />
        </FilterBar>
        <DataTable :columns="columns" :rows="logs" :filtered="!!(filters.patient || filters.action)" empty-icon="eye" :empty-title="t('access_log.empty')" @clear-filters="router.get(route('admin.access-logs.index'))">
            <template #cell-action="{ value }">{{ t(`access_log.actions.${value}`) }}</template>
            <template #cell-channel="{ value }">{{ t(`access_log.channels.${value}`) }}</template>
            <template #cell-user="{ value, row }">{{ value ?? t(`access_log.channels.${row.channel}`) }}</template>
        </DataTable>
    </div>
</template>
