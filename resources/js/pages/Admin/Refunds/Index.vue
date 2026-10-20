<script setup>
import { Head, router } from '@inertiajs/vue3';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useI18n } from '@/lib/i18n';

defineProps({
    refunds: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const confirm = useConfirm();

const columns = [
    { key: 'invoice_no', label: t('billing.invoice_no'), type: 'id' },
    { key: 'patient', label: t('billing.patient'), type: 'text', grow: true },
    { key: 'amount', label: t('billing.amount'), type: 'money' },
    { key: 'reason', label: t('billing.reason'), type: 'text' },
    { key: 'status', label: t('common.status'), type: 'status' },
    { key: 'actions', label: '', type: 'actions' },
];

const statuses = [
    { value: '', label: t('common.all_statuses') },
    { value: 'pending', label: t('statuses.pending') },
    { value: 'approved', label: t('statuses.approved') },
    { value: 'rejected', label: t('statuses.rejected') },
];

async function decide(row, approve) {
    const result = await confirm({
        variant: approve ? 'info' : 'danger',
        level: 'approval',
        title: t('billing.refund_title'),
        message: row.reason,
        confirmText: approve ? t('billing.approve') : t('billing.reject'),
    });
    if (!result.confirmed) {
        return;
    }
    router.post(route('admin.refunds.decide', row.id), {
        approve,
        approval_login: result.login,
        approval_pin: result.pin,
    }, { preserveScroll: true });
}
</script>

<template>
    <div>
        <Head :title="t('nav.refunds')" />
        <PageHeader :title="t('nav.refunds')" />
        <FilterBar :filters="filters" search-key="unused">
            <template #default="{ form }">
                <SelectInput v-model="form.status" :options="statuses" class="w-40" />
            </template>
        </FilterBar>
        <DataTable :columns="columns" :rows="refunds" :empty-title="t('billing.refunds_empty')" empty-icon="money-bill-wave" :sync-url="false" @clear-filters="router.get(route('admin.refunds.index'))">
            <template #cell-patient="{ row }">
                <span class="block truncate">{{ row.patient_name }}</span>
                <span class="block text-xs text-muted">{{ row.patient_code }}</span>
            </template>
            <template #cell-amount="{ value }"><MoneyText :value="value" symbol /></template>
            <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
            <template #cell-actions="{ row }">
                <div v-if="row.status === 'pending'" class="flex justify-end gap-1">
                    <UiButton size="xs" variant="primary" @click="decide(row, true)">{{ t('billing.approve') }}</UiButton>
                    <UiButton size="xs" variant="destructive" @click="decide(row, false)">{{ t('billing.reject') }}</UiButton>
                </div>
            </template>
        </DataTable>
    </div>
</template>
