<script setup>
import { Head, router } from '@inertiajs/vue3';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import { useI18n } from '@/lib/i18n';

defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useI18n();

const columns = [
    { key: 'invoice_no', label: t('billing.invoice_no'), type: 'id' },
    { key: 'patient', label: t('billing.patient'), type: 'text', grow: true },
    { key: 'status', label: t('common.status'), type: 'status' },
    { key: 'total', label: t('billing.total'), type: 'money' },
    { key: 'due', label: t('billing.due'), type: 'money' },
    { key: 'created_at', label: t('patients.registered'), type: 'datetime' },
];
</script>

<template>
    <div>
        <Head :title="t('nav.orders')" />
        <PageHeader :title="t('nav.orders')" :description="t('billing.description')" />
        <FilterBar :filters="filters" :search-placeholder="t('billing.invoice_no')" />
        <DataTable
            :columns="columns"
            :rows="orders"
            :filtered="!!filters.q"
            :empty-title="t('billing.empty')"
            :empty-description="t('billing.empty_hint')"
            empty-icon="receipt"
            clickable
            @row-click="(row) => router.visit(route('admin.orders.show', row.id))"
            @clear-filters="router.get(route('admin.orders.index'))"
        >
            <template #cell-invoice_no="{ row }">{{ row.invoice?.invoice_no }}</template>
            <template #cell-patient="{ row }">
                <span class="block truncate font-medium">{{ row.patient?.name }}</span>
                <span class="block text-xs text-muted">{{ row.patient?.patient_code }}</span>
            </template>
            <template #cell-status="{ row }"><StatusBadge :status="row.invoice?.status ?? row.status" /></template>
            <template #cell-total="{ row }"><MoneyText :value="row.invoice?.total" symbol /></template>
            <template #cell-due="{ row }"><MoneyText :value="row.invoice?.due" symbol /></template>
        </DataTable>
    </div>
</template>
