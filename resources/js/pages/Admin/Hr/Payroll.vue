<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    runs: { type: Array, default: () => [] },
    defaultMonth: { type: String, required: true },
});

const { t } = useI18n();
const { can } = usePermissions();
const form = useForm({ month: props.defaultMonth });
const tones = { draft: 'warning', finalized: 'info', paid: 'success' };

function generate() {
    form.post(route('admin.payroll.generate'), { preserveScroll: true });
}
</script>

<template>
    <div>
        <Head :title="t('nav.payroll')" />
        <PageHeader :title="t('nav.payroll')" :description="t('hr.payroll_description')">
            <template v-if="can('payroll.manage')" #actions>
                <form class="flex items-center gap-2" @submit.prevent="generate">
                    <input v-model="form.month" type="month" class="control !w-40" :aria-label="t('hr.month')" />
                    <UiButton type="submit" variant="primary" icon="wallet" :loading="form.processing">{{ t('hr.generate_payroll') }}</UiButton>
                </form>
            </template>
        </PageHeader>
        <p v-if="form.errors.month" class="mb-2 text-xs text-danger">{{ form.errors.month }}</p>

        <div class="overflow-x-auto rounded-lg border border-border bg-surface">
            <table class="w-full text-[13px]">
                <thead class="bg-subtle text-xs text-muted">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">{{ t('hr.month') }}</th>
                        <th class="px-3 py-2 text-left font-medium">{{ t('fields.branch') }}</th>
                        <th class="px-3 py-2 text-left font-medium">{{ t('hr.status_label') }}</th>
                        <th class="px-3 py-2 text-right font-medium">{{ t('hr.gross') }}</th>
                        <th class="px-3 py-2 text-right font-medium">{{ t('hr.deductions') }}</th>
                        <th class="px-3 py-2 text-right font-medium">{{ t('hr.net') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="run in runs" :key="run.id" class="cursor-pointer hover:bg-subtle/60" @click="router.get(route('admin.payroll.show', run.id))">
                        <td class="px-3 py-2 font-medium tabular-nums">{{ run.month }}</td>
                        <td class="px-3 py-2 text-muted">{{ run.branch }}</td>
                        <td class="px-3 py-2"><StatusBadge :status="run.status" :tone="tones[run.status]" :label="t(`hr.payroll_status.${run.status}`)" /></td>
                        <td class="px-3 py-2 text-right"><MoneyText :value="run.total_gross" /></td>
                        <td class="px-3 py-2 text-right"><MoneyText :value="run.total_deductions" /></td>
                        <td class="px-3 py-2 text-right font-semibold"><MoneyText :value="run.total_net" /></td>
                    </tr>
                    <tr v-if="!runs.length"><td colspan="6" class="px-3 py-8 text-center text-muted">{{ t('hr.no_payroll') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
