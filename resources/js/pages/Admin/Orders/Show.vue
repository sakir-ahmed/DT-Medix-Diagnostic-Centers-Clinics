<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import MoneyInput from '@/components/ui-kit/MoneyInput.vue';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import PatientBanner from '@/components/ui-kit/PatientBanner.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    order: { type: Object, required: true },
});

const { t } = useI18n();
const { can } = usePermissions();
const confirm = useConfirm();
const selected = ref([]);

const pay = useForm({ lines: [{ amount: '', method: 'cash' }] });
const refund = useForm({ invoice_id: props.order.invoice?.id, amount: '', reason: '' });

const itemColumns = [
    { key: 'code', label: t('billing.code'), type: 'id' },
    { key: 'name', label: t('billing.test'), type: 'text', grow: true },
    { key: 'price', label: t('billing.price'), type: 'money' },
    { key: 'status', label: t('common.status'), type: 'status' },
];

const activeItems = computed(() => (props.order.items ?? []).filter((item) => item.status !== 'cancelled'));
const overpaid = computed(() => {
    const invoice = props.order.invoice;
    if (!invoice) {
        return 0;
    }

    return Math.max(0, Number(invoice.paid) - Number(invoice.refunded) - Number(invoice.total));
});

function printOrder() {
    window.open(route('admin.orders.print', props.order.id), '_blank', 'noopener');
}

function submitPayment() {
    pay.post(route('admin.orders.payments.store', props.order.id), {
        preserveScroll: true,
        onSuccess: () => pay.reset(),
    });
}

async function cancelItems() {
    const collected = selected.value.some((id) => {
        const item = activeItems.value.find((row) => row.id === id);

        return item && item.status !== 'ordered';
    });
    const result = await confirm({
        variant: 'danger',
        level: collected ? 'approval' : 'reason',
        title: t('billing.cancel_title'),
        message: t('billing.cancel_message'),
        confirmText: t('billing.cancel_items'),
    });
    if (!result.confirmed) {
        return;
    }
    router.post(route('admin.orders.cancel', props.order.id), {
        item_ids: selected.value,
        reason: result.note,
        approval_login: result.login,
        approval_pin: result.pin,
    }, { preserveScroll: true, onSuccess: () => (selected.value = []) });
}

function requestRefund() {
    refund.post(route('admin.refunds.store'), { preserveScroll: true, onSuccess: () => refund.reset('amount', 'reason') });
}
</script>

<template>
    <div>
        <Head :title="order.invoice?.invoice_no ?? t('nav.orders')" />
        <PageHeader :title="order.invoice?.invoice_no ?? t('billing.order')" :breadcrumbs="[{ label: t('nav.orders'), href: route('admin.orders.index') }, { label: order.invoice?.invoice_no ?? String(order.id) }]">
            <template #actions>
                <UiButton icon="print" @click="printOrder">{{ t('common.print') }}</UiButton>
            </template>
        </PageHeader>

        <PatientBanner v-if="order.patient" :patient="order.patient" class="mb-3" />

        <div class="grid items-start gap-3 lg:grid-cols-5">
            <section class="space-y-3 lg:col-span-3">
                <DataTable :columns="itemColumns" :rows="order.items ?? []" selectable :sync-url="false" row-key="id" @update:selected="(ids) => (selected = ids)">
                    <template #cell-price="{ value }"><MoneyText :value="value" symbol /></template>
                    <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
                </DataTable>
                <UiButton v-if="can('orders.cancel') && selected.length" variant="destructive" icon="ban" @click="cancelItems">{{ t('billing.cancel_items') }}</UiButton>

                <section v-if="order.samples?.length" class="card">
                    <h2 class="mb-2 text-sm font-semibold">{{ t('billing.samples') }}</h2>
                    <ul class="space-y-1 text-[13px]">
                        <li v-for="sample in order.samples" :key="sample.id" class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs">{{ sample.sample_code }}</span>
                            <span class="text-muted">{{ sample.sample_type }} <span v-if="sample.container">/ {{ sample.container }}</span></span>
                            <StatusBadge :status="sample.status" />
                        </li>
                    </ul>
                </section>
            </section>

            <aside class="card space-y-3 lg:col-span-2">
                <h2 class="text-sm font-semibold">{{ t('billing.invoice') }}</h2>
                <dl v-if="order.invoice" class="space-y-1 text-[13px]">
                    <div class="flex justify-between"><dt class="text-muted">{{ t('billing.subtotal') }}</dt><dd><MoneyText :value="order.invoice.subtotal" symbol /></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ t('billing.discount') }}</dt><dd><MoneyText :value="order.invoice.discount_amount" symbol /></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ t('billing.total') }}</dt><dd><MoneyText :value="order.invoice.total" symbol /></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ t('billing.paid') }}</dt><dd><MoneyText :value="order.invoice.paid" symbol /></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ t('billing.due') }}</dt><dd><MoneyText :value="order.invoice.due" symbol /></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ t('common.status') }}</dt><dd><StatusBadge :status="order.invoice.status" /></dd></div>
                </dl>

                <form v-if="can('payments.collect') && Number(order.invoice?.due ?? 0) > 0" class="space-y-2 border-t border-border pt-3" @submit.prevent="submitPayment">
                    <h3 class="text-sm font-semibold">{{ t('billing.collect') }}</h3>
                    <FormField v-for="(line, index) in pay.lines" :id="`pay-${index}`" :key="index" :label="t('billing.cash_amount')" :error="pay.errors[`lines.${index}.amount`]">
                        <template #default="{ id, invalid }">
                            <MoneyInput :id="id" v-model="line.amount" :invalid="invalid" />
                        </template>
                    </FormField>
                    <UiButton size="xs" icon="plus" @click="pay.lines.push({ amount: '', method: 'cash' })">{{ t('billing.add_cash_line') }}</UiButton>
                    <UiButton type="submit" variant="primary" icon="money-bill-wave" :loading="pay.processing">{{ t('billing.collect') }}</UiButton>
                </form>

                <ul v-if="order.invoice?.payments?.length" class="space-y-1 border-t border-border pt-3 text-[13px]">
                    <li v-for="payment in order.invoice.payments" :key="payment.id" class="flex justify-between gap-2">
                        <span class="font-mono text-xs">{{ payment.receipt_no }}</span>
                        <MoneyText :value="payment.amount" symbol />
                    </li>
                </ul>

                <form v-if="can('refunds.request') && overpaid > 0" class="space-y-2 border-t border-border pt-3" @submit.prevent="requestRefund">
                    <h3 class="text-sm font-semibold">{{ t('nav.refunds') }}</h3>
                    <FormField id="refund-amount" :label="t('billing.amount')" :error="refund.errors.amount" required>
                        <template #default="{ id, invalid }">
                            <MoneyInput :id="id" v-model="refund.amount" :invalid="invalid" />
                        </template>
                    </FormField>
                    <FormField id="refund-reason" :label="t('billing.reason')" :error="refund.errors.reason" required>
                        <template #default="{ id, invalid }">
                            <TextArea :id="id" v-model="refund.reason" :invalid="invalid" rows="2" />
                        </template>
                    </FormField>
                    <UiButton type="submit" :loading="refund.processing">{{ t('billing.refund_requested') }}</UiButton>
                </form>
            </aside>
        </div>
    </div>
</template>
