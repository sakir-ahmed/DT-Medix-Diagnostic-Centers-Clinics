<script setup>
import { formatDate } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

/**
 * On-screen preview of the A4 report layout (design.md section 10).
 */
defineProps({
    report: { type: Object, required: true },
    withHeader: { type: Boolean, default: true },
});

const { t } = useI18n();
const flagLabel = (flag) => (flag && flag !== 'N' ? flag : '');
</script>

<template>
    <article class="mx-auto w-full max-w-[794px] rounded-lg border border-border bg-white p-8 text-[13px] text-slate-900 shadow-sm">
        <header v-if="withHeader" class="mb-3 border-b-2 border-slate-900 pb-2">
            <div class="text-lg font-bold">{{ report.center?.name }}</div>
            <div class="text-xs">{{ report.center?.address }} <span v-if="report.center?.phone">| {{ report.center.phone }}</span></div>
            <div v-if="report.center?.license_no" class="text-xs">{{ t('reports.license') }}: {{ report.center.license_no }}</div>
        </header>
        <div v-if="report.amended" class="mb-3 rounded border-2 border-slate-900 px-2 py-1 text-center font-bold">
            {{ t('reports.amended_banner', { version: report.version }) }}
            <div class="text-xs font-normal">{{ t('reports.amendment_reason') }}: {{ report.amendment_reason }}</div>
        </div>
        <dl class="mb-3 grid grid-cols-2 gap-x-6 gap-y-0.5 text-xs">
            <div class="flex gap-2"><dt class="w-24 text-slate-500">{{ t('patients.name') }}</dt><dd class="font-semibold">{{ report.patient?.name }}</dd></div>
            <div class="flex gap-2"><dt class="w-24 text-slate-500">{{ t('patients.patient_id') }}</dt><dd>{{ report.patient?.patient_code }}</dd></div>
            <div class="flex gap-2"><dt class="w-24 text-slate-500">{{ t('patients.age_sex') }}</dt><dd>{{ report.patient?.age_sex }}</dd></div>
            <div class="flex gap-2"><dt class="w-24 text-slate-500">{{ t('reports.sample_id') }}</dt><dd>{{ report.sample_code }}</dd></div>
            <div class="flex gap-2"><dt class="w-24 text-slate-500">{{ t('reports.referred_by') }}</dt><dd>{{ report.referrer ?? t('reports.self') }}</dd></div>
            <div class="flex gap-2"><dt class="w-24 text-slate-500">{{ t('reports.reported_at') }}</dt><dd>{{ formatDate(report.reported_at, { time: true }) }}</dd></div>
        </dl>
        <section v-for="group in report.groups ?? []" :key="group.title" class="mb-3">
            <h3 class="mb-1 border-b border-slate-300 text-sm font-bold">{{ group.title }}</h3>
            <table v-if="group.rows?.length" class="w-full">
                <thead>
                    <tr class="text-left text-xs text-slate-500">
                        <th class="py-0.5 font-semibold">{{ t('results.parameter') }}</th>
                        <th class="py-0.5 text-right font-semibold">{{ t('results.result') }}</th>
                        <th class="py-0.5 pl-3 font-semibold">{{ t('results.unit') }}</th>
                        <th class="py-0.5 font-semibold">{{ t('results.reference') }}</th>
                        <th class="py-0.5 text-center font-semibold">{{ t('results.flag') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in group.rows" :key="row.name">
                        <td class="py-0.5">{{ row.name }}</td>
                        <td class="py-0.5 text-right tabular-nums" :class="row.flag && row.flag !== 'N' ? 'font-bold' : ''">{{ row.value }}</td>
                        <td class="py-0.5 pl-3">{{ row.unit }}</td>
                        <td class="py-0.5 tabular-nums">{{ row.reference }}</td>
                        <td class="py-0.5 text-center font-bold">{{ flagLabel(row.flag) }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-if="group.findings" class="whitespace-pre-line"><strong>{{ t('reports.findings') }}:</strong> {{ group.findings }}</div>
            <div v-if="group.impression" class="mt-1 whitespace-pre-line"><strong>{{ t('reports.impression') }}:</strong> {{ group.impression }}</div>
            <p v-if="group.notes" class="mt-1 text-xs text-slate-500">{{ group.notes }}</p>
        </section>
        <footer class="mt-6 grid grid-cols-2 gap-6 text-xs">
            <div v-for="signer in report.signatures ?? []" :key="signer.name" class="border-t border-slate-400 pt-1">
                <div class="font-semibold">{{ signer.name }}</div>
                <div>{{ signer.qualification }}</div>
                <div v-if="signer.bmdc">{{ t('doctors.bmdc') }}: {{ signer.bmdc }}</div>
            </div>
        </footer>
        <p class="mt-4 text-center text-xs italic">{{ t('reports.clinical_correlation') }}</p>
    </article>
</template>
