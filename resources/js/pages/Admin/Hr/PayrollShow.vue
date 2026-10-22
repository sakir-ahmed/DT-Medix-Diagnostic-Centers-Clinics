<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import FormField from '@/components/ui-kit/FormField.vue';
import KpiCard from '@/components/ui-kit/KpiCard.vue';
import MoneyInput from '@/components/ui-kit/MoneyInput.vue';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { usePermissions } from '@/composables/usePermissions';
import { formatMoney, localizeDigits } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    run: { type: Object, required: true },
    payslips: { type: Array, default: () => [] },
});

const { t } = useI18n();
const print = () => window.print();
const { can } = usePermissions();
const confirm = useConfirm();
const tones = { draft: 'warning', finalized: 'info', paid: 'success' };
const editable = props.run.status === 'draft' && can('payroll.manage');

const deductionOpen = ref(false);
const payOpen = ref(false);
const current = ref(null);
const deductionForm = useForm({ advance_deduction: '0.00', other_deduction: '0.00' });
const payForm = useForm({ payment_method: 'bkash', payment_reference: '' });
const methods = ['cash', 'bkash', 'nagad', 'bank'].map((value) => ({ value, label: t(`payments.methods.${value}`) }));

function editDeductions(slip) {
    current.value = slip;
    deductionForm.clearErrors();
    Object.assign(deductionForm, { advance_deduction: slip.advance_deduction, other_deduction: slip.other_deduction });
    deductionOpen.value = true;
}

function saveDeductions() {
    deductionForm.put(route('admin.payslips.update', current.value.id), { preserveScroll: true, onSuccess: () => (deductionOpen.value = false) });
}

function openPay(slip) {
    current.value = slip;
    payForm.clearErrors();
    payOpen.value = true;
}

function savePay() {
    payForm.post(route('admin.payslips.pay', current.value.id), { preserveScroll: true, onSuccess: () => (payOpen.value = false) });
}

async function finalize() {
    const result = await confirm({ variant: 'warning', title: t('hr.finalize_title'), message: t('hr.finalize_message'), confirmText: t('hr.finalize') });

    if (result.confirmed) {
        router.post(route('admin.payroll.finalize', props.run.id), {}, { preserveScroll: true });
    }
}

const deductions = (slip) => Number(slip.absence_deduction) + Number(slip.late_deduction) + Number(slip.advance_deduction) + Number(slip.other_deduction);
</script>

<template>
    <div>
        <Head :title="t('hr.payroll_of', { month: run.month })" />
        <PageHeader :title="t('hr.payroll_of', { month: run.month })" :description="t('hr.payroll_show_description')" :breadcrumbs="[{ label: t('nav.payroll'), href: route('admin.payroll.index') }, { label: run.month }]">
            <template #actions>
                <StatusBadge :status="run.status" :tone="tones[run.status]" :label="t(`hr.payroll_status.${run.status}`)" />
                <UiButton icon="print" @click="print">{{ t('common.print') }}</UiButton>
                <UiButton v-if="editable" variant="primary" icon="stamp" @click="finalize">{{ t('hr.finalize') }}</UiButton>
            </template>
        </PageHeader>

        <div class="mb-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
            <KpiCard :label="t('hr.gross')" :value="run.total_gross" format="money" />
            <KpiCard :label="t('hr.deductions')" :value="run.total_deductions" format="money" />
            <KpiCard :label="t('hr.net')" :value="run.total_net" format="money" />
        </div>

        <div class="overflow-x-auto rounded-lg border border-border bg-surface">
            <table class="w-full text-[13px]">
                <thead class="bg-subtle text-xs text-muted">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">{{ t('hr.employee') }}</th>
                        <th class="px-3 py-2 text-right font-medium">{{ t('hr.present_days') }}</th>
                        <th class="px-3 py-2 text-right font-medium">{{ t('hr.absent_days') }}</th>
                        <th class="px-3 py-2 text-right font-medium">{{ t('hr.late_count') }}</th>
                        <th class="px-3 py-2 text-right font-medium">{{ t('hr.gross') }}</th>
                        <th class="px-3 py-2 text-right font-medium">{{ t('hr.deductions') }}</th>
                        <th class="px-3 py-2 text-right font-medium">{{ t('hr.net') }}</th>
                        <th class="px-3 py-2 text-left font-medium">{{ t('hr.payment') }}</th>
                        <th class="no-print w-40 px-3 py-2" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="slip in payslips" :key="slip.id">
                        <td class="px-3 py-1.5"><span class="block font-medium">{{ slip.employee_name }}</span><span class="block text-xs text-muted">{{ slip.employee_code }}</span></td>
                        <td class="px-3 py-1.5 text-right tabular-nums">{{ localizeDigits(slip.present_days) }}</td>
                        <td class="px-3 py-1.5 text-right tabular-nums">{{ localizeDigits(Number(slip.absent_days)) }}</td>
                        <td class="px-3 py-1.5 text-right tabular-nums">{{ localizeDigits(slip.late_count) }}</td>
                        <td class="px-3 py-1.5 text-right"><MoneyText :value="slip.gross" /></td>
                        <td class="px-3 py-1.5 text-right"><MoneyText :value="deductions(slip)" /></td>
                        <td class="px-3 py-1.5 text-right font-semibold"><MoneyText :value="slip.net" /></td>
                        <td class="px-3 py-1.5"><StatusBadge :status="slip.paid_at ? 'paid' : 'pending'" :label="slip.paid_at ? t('hr.paid') : t('hr.unpaid')" /></td>
                        <td class="no-print px-3 py-1.5 text-right">
                            <span class="inline-flex gap-1">
                                <UiButton v-if="editable" size="xs" @click="editDeductions(slip)">{{ t('hr.deductions') }}</UiButton>
                                <UiButton v-if="run.status !== 'draft' && !slip.paid_at && can('payroll.manage')" size="xs" variant="primary" @click="openPay(slip)">{{ t('hr.mark_paid') }}</UiButton>
                                <Link :href="route('admin.payslips.show', slip.id)" class="inline-flex h-6 items-center rounded-md border border-border px-2 text-xs hover:bg-subtle">{{ t('hr.payslip') }}</Link>
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!payslips.length"><td colspan="9" class="px-3 py-8 text-center text-muted">{{ t('hr.no_payslips') }}</td></tr>
                </tbody>
            </table>
        </div>

        <SlideOver v-model:open="deductionOpen" :title="t('hr.edit_deductions')">
            <form id="deduction-form" class="grid grid-cols-1 gap-3" novalidate @submit.prevent="saveDeductions">
                <p class="text-[13px] font-medium">{{ current?.employee_name }}</p>
                <FormField id="advance_deduction" :label="t('hr.advance_deduction')" :error="deductionForm.errors.advance_deduction"><MoneyInput id="advance_deduction" v-model="deductionForm.advance_deduction" /></FormField>
                <FormField id="other_deduction" :label="t('hr.other_deduction')" :error="deductionForm.errors.other_deduction"><MoneyInput id="other_deduction" v-model="deductionForm.other_deduction" /></FormField>
            </form>
            <template #footer>
                <UiButton @click="deductionOpen = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="deduction-form" variant="primary" icon="floppy-disk" :loading="deductionForm.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>

        <SlideOver v-model:open="payOpen" :title="t('hr.mark_paid')">
            <form id="pay-form" class="grid grid-cols-1 gap-3" novalidate @submit.prevent="savePay">
                <p class="text-[13px]"><span class="font-medium">{{ current?.employee_name }}</span> - {{ current ? formatMoney(current.net) : '' }}</p>
                <FormField id="payment_method" :label="t('hr.payment_method')" :error="payForm.errors.payment_method" required><SelectInput id="payment_method" v-model="payForm.payment_method" :options="methods" /></FormField>
                <FormField id="payment_reference" :label="t('hr.payment_reference')" :error="payForm.errors.payment_reference" optional><TextInput id="payment_reference" v-model="payForm.payment_reference" /></FormField>
            </form>
            <template #footer>
                <UiButton @click="payOpen = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="pay-form" variant="primary" icon="check" :loading="payForm.processing">{{ t('hr.mark_paid') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
