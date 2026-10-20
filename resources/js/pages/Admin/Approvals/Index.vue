<script setup>
import { Head, router } from '@inertiajs/vue3';
import DataTable from '@/components/ui-kit/DataTable.vue';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useI18n } from '@/lib/i18n';

defineProps({
    requests: { type: Array, default: () => [] },
});

const { t } = useI18n();
const confirm = useConfirm();

const columns = [
    { key: 'invoice_no', label: t('billing.invoice_no'), type: 'id' },
    { key: 'patient', label: t('billing.patient'), type: 'text', grow: true },
    { key: 'discount_value', label: t('billing.discount'), type: 'money' },
    { key: 'reason', label: t('billing.reason'), type: 'text' },
    { key: 'actions', label: '', type: 'actions' },
];

async function decide(row, approve) {
    const result = await confirm({
        variant: approve ? 'info' : 'danger',
        level: 'approval',
        title: t('billing.discount_title'),
        message: row.reason,
        confirmText: approve ? t('billing.approve') : t('billing.reject'),
    });
    if (!result.confirmed) {
        return;
    }
    router.post(route('admin.approvals.decide', row.id), {
        approve,
        approval_login: result.login,
        approval_pin: result.pin,
    }, { preserveScroll: true });
}
</script>

<template>
    <div>
        <Head :title="t('nav.approvals')" />
        <PageHeader :title="t('nav.approvals')" />
        <DataTable :columns="columns" :rows="requests" :empty-title="t('billing.approvals_empty')" empty-icon="user-shield" :sync-url="false">
            <template #cell-patient="{ row }">
                <span class="block truncate">{{ row.patient_name }}</span>
                <span class="block text-xs text-muted">{{ row.patient_code }}</span>
            </template>
            <template #cell-discount_value="{ row }">
                <MoneyText v-if="row.discount_type === 'fixed'" :value="row.discount_value" symbol />
                <span v-else class="tabular-nums">{{ row.discount_value }}%</span>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <UiButton size="xs" variant="primary" @click="decide(row, true)">{{ t('billing.approve') }}</UiButton>
                    <UiButton size="xs" variant="destructive" @click="decide(row, false)">{{ t('billing.reject') }}</UiButton>
                </div>
            </template>
        </DataTable>
    </div>
</template>
