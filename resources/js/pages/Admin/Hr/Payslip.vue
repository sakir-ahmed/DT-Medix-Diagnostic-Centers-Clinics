<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { formatDate, formatMoney, localizeDigits } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    payslip: { type: Object, required: true },
});

const { t } = useI18n();
const print = () => window.print();
const center = computed(() => usePage().props.center);

const earnings = computed(() => [
    [t('hr.basic_salary'), props.payslip.basic_salary],
    [t('hr.allowances'), props.payslip.allowances],
    [t('hr.overtime_pay'), props.payslip.overtime_pay],
]);
const deductions = computed(() => [
    [t('hr.absence_deduction'), props.payslip.absence_deduction],
    [t('hr.late_deduction'), props.payslip.late_deduction],
    [t('hr.advance_deduction'), props.payslip.advance_deduction],
    [t('hr.other_deduction'), props.payslip.other_deduction],
]);
</script>

<template>
    <div class="mx-auto max-w-2xl">
        <Head :title="t('hr.payslip')" />
        <div class="no-print mb-3 flex items-center justify-between">
            <Link :href="route('admin.payroll.show', payslip.payroll_run_id)" class="text-[13px] text-primary hover:underline">{{ t('common.back') }}</Link>
            <UiButton icon="print" variant="primary" @click="print">{{ t('common.print') }}</UiButton>
        </div>

        <article class="rounded-lg border border-border bg-surface p-6 text-[13px]">
            <header class="flex items-start justify-between border-b border-border pb-3">
                <div>
                    <h1 class="text-base font-semibold">{{ center?.name }}</h1>
                    <p class="text-muted">{{ t('hr.payslip_for', { month: payslip.run.month }) }}</p>
                </div>
                <p class="text-right text-xs text-muted">{{ payslip.paid_at ? t('hr.paid_on', { date: formatDate(payslip.paid_at) }) : t('hr.unpaid') }}</p>
            </header>

            <dl class="grid grid-cols-2 gap-x-6 gap-y-1 py-3">
                <div class="flex justify-between"><dt class="text-muted">{{ t('hr.employee') }}</dt><dd class="font-medium">{{ payslip.employee_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ t('fields.code') }}</dt><dd class="tabular-nums">{{ payslip.employee_code }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ t('hr.designation') }}</dt><dd>{{ payslip.designation ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ t('hr.month_days') }}</dt><dd class="tabular-nums">{{ localizeDigits(payslip.month_days) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ t('hr.present_days') }}</dt><dd class="tabular-nums">{{ localizeDigits(payslip.present_days) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ t('hr.absent_days') }}</dt><dd class="tabular-nums">{{ localizeDigits(Number(payslip.absent_days)) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ t('hr.late_count') }}</dt><dd class="tabular-nums">{{ localizeDigits(payslip.late_count) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ t('hr.paid_leave_days') }}</dt><dd class="tabular-nums">{{ localizeDigits(payslip.paid_leave_days) }}</dd></div>
            </dl>

            <div class="grid grid-cols-2 gap-6 border-t border-border pt-3">
                <section>
                    <h2 class="mb-1 text-xs font-semibold text-muted uppercase">{{ t('hr.earnings') }}</h2>
                    <div v-for="[label, value] in earnings" :key="label" class="flex justify-between py-0.5"><span>{{ label }}</span><span class="tabular-nums">{{ formatMoney(value) }}</span></div>
                    <div class="mt-1 flex justify-between border-t border-border pt-1 font-semibold"><span>{{ t('hr.gross') }}</span><span class="tabular-nums">{{ formatMoney(payslip.gross) }}</span></div>
                </section>
                <section>
                    <h2 class="mb-1 text-xs font-semibold text-muted uppercase">{{ t('hr.deductions') }}</h2>
                    <div v-for="[label, value] in deductions" :key="label" class="flex justify-between py-0.5"><span>{{ label }}</span><span class="tabular-nums">{{ formatMoney(value) }}</span></div>
                </section>
            </div>

            <div class="mt-4 flex items-center justify-between rounded-md bg-subtle px-3 py-2 text-base font-semibold"><span>{{ t('hr.net_payable') }}</span><span class="tabular-nums">{{ formatMoney(payslip.net) }}</span></div>
            <p v-if="payslip.payment_method" class="mt-2 text-xs text-muted">{{ t(`payments.methods.${payslip.payment_method}`) }}<template v-if="payslip.payment_reference"> - {{ payslip.payment_reference }}</template></p>
            <footer class="mt-10 grid grid-cols-2 gap-6 text-xs text-muted"><p class="border-t border-border pt-1">{{ t('hr.employee_signature') }}</p><p class="border-t border-border pt-1 text-right">{{ t('hr.authorized_signature') }}</p></footer>
        </article>
    </div>
</template>
