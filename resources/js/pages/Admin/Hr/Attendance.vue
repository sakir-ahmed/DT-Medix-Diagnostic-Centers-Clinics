<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import KpiCard from '@/components/ui-kit/KpiCard.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { usePermissions } from '@/composables/usePermissions';
import { formatDuration, localizeDigits } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    view: { type: String, default: 'day' },
    date: { type: String, required: true },
    month: { type: String, required: true },
    statuses: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({}) },
    daysInMonth: { type: Number, default: 30 },
});

const { t } = useI18n();
const { can } = usePermissions();

const tones = { present: 'success', late: 'warning', half_day: 'warning', absent: 'danger', leave: 'info', weekly_off: 'muted', holiday: 'muted' };
const codes = { present: 'P', late: 'L', half_day: 'H', absent: 'A', leave: 'V', weekly_off: 'W', holiday: 'O' };
const cellTone = { present: 'bg-success/15 text-success', late: 'bg-warning/20 text-[oklch(0.5_0.13_70)]', half_day: 'bg-warning/20 text-[oklch(0.5_0.13_70)]', absent: 'bg-danger/15 text-danger', leave: 'bg-info/15 text-info', weekly_off: 'bg-subtle text-muted', holiday: 'bg-subtle text-muted' };

const statusOptions = props.statuses.map((value) => ({ value, label: t(`hr.status.${value}`) }));
const dateModel = ref(props.date);
const monthModel = ref(props.month);

function go(params) {
    router.get(route('admin.attendance.index'), { view: props.view, date: props.date, month: props.month, ...params }, { preserveScroll: true, preserveState: true });
}

const open = ref(false);
const form = useForm({ employee_id: null, work_date: props.date, status: 'present', check_in: '', check_out: '', note: '' });
const editingName = ref('');

function mark(row) {
    if (!can('attendance.manage')) {
        return;
    }
    editingName.value = row.name;
    form.clearErrors();
    Object.assign(form, { employee_id: row.employee_id, work_date: props.date, status: row.status ?? 'present', check_in: row.check_in ?? '', check_out: row.check_out ?? '', note: row.note ?? '' });
    open.value = true;
}

function submit() {
    form.post(route('admin.attendance.store'), { preserveScroll: true, onError: focusFirstError, onSuccess: () => (open.value = false) });
}

const importForm = useForm({ file: null });
const fileInput = ref(null);
function importFile(event) {
    importForm.file = event.target.files[0] ?? null;
    if (importForm.file) {
        importForm.post(route('admin.attendance.import'), { forceFormData: true, preserveScroll: true, onFinish: () => { importForm.reset(); fileInput.value.value = ''; } });
    }
}

const monthDays = computed(() => Array.from({ length: props.daysInMonth }, (_, index) => index + 1));
const time = (minutes) => (minutes ? formatDuration(minutes, t) : '-');
</script>

<template>
    <div>
        <Head :title="t('nav.attendance')" />
        <PageHeader :title="t('nav.attendance')" :description="t('hr.attendance_description')">
            <template #actions>
                <template v-if="can('attendance.manage')">
                    <input ref="fileInput" type="file" accept=".dat,.csv,.txt" class="hidden" @change="importFile" />
                    <UiButton icon="file-import" :loading="importForm.processing" @click="fileInput.click()">{{ t('hr.import_punches') }}</UiButton>
                    <UiButton icon="arrows-rotate" @click="router.post(route('admin.attendance.recalculate'), { date }, { preserveScroll: true })">{{ t('hr.recalculate') }}</UiButton>
                </template>
            </template>
        </PageHeader>

        <div class="mb-3 flex flex-wrap items-center gap-2">
            <div class="inline-flex overflow-hidden rounded-md border border-border text-[13px]" role="group">
                <button type="button" class="h-7 px-3" :class="view === 'day' ? 'bg-primary text-primary-foreground' : 'bg-surface hover:bg-subtle'" @click="go({ view: 'day' })">{{ t('hr.daily') }}</button>
                <button type="button" class="h-7 px-3" :class="view === 'month' ? 'bg-primary text-primary-foreground' : 'bg-surface hover:bg-subtle'" @click="go({ view: 'month' })">{{ t('hr.monthly_sheet') }}</button>
            </div>
            <input v-if="view === 'day'" v-model="dateModel" type="date" class="control !w-40" :aria-label="t('fields.date')" @change="go({ date: dateModel })" />
            <input v-else v-model="monthModel" type="month" class="control !w-40" :aria-label="t('hr.month')" @change="go({ month: monthModel })" />
        </div>

        <template v-if="view === 'day'">
            <div class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                <KpiCard v-for="key in ['present', 'late', 'half_day', 'absent', 'leave', 'weekly_off', 'unmarked']" :key="key" :label="key === 'unmarked' ? t('hr.unmarked') : t(`hr.status.${key}`)" :value="localizeDigits(counts[key] ?? 0)" />
            </div>
            <div class="overflow-x-auto rounded-lg border border-border bg-surface">
                <table class="w-full text-[13px]">
                    <thead class="bg-subtle text-xs text-muted">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium">{{ t('hr.employee') }}</th>
                            <th class="px-3 py-2 text-left font-medium">{{ t('hr.shift') }}</th>
                            <th class="px-3 py-2 text-left font-medium">{{ t('hr.status_label') }}</th>
                            <th class="px-3 py-2 text-right font-medium">{{ t('hr.check_in') }}</th>
                            <th class="px-3 py-2 text-right font-medium">{{ t('hr.check_out') }}</th>
                            <th class="px-3 py-2 text-right font-medium">{{ t('hr.late') }}</th>
                            <th class="px-3 py-2 text-right font-medium">{{ t('hr.worked') }}</th>
                            <th class="px-3 py-2 text-right font-medium">{{ t('hr.overtime') }}</th>
                            <th class="px-3 py-2 text-left font-medium">{{ t('hr.source') }}</th>
                            <th class="w-24 px-3 py-2" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="row in rows" :key="row.employee_id" class="hover:bg-subtle/60">
                            <td class="px-3 py-1.5"><span class="block font-medium">{{ row.name }}</span><span class="block text-xs text-muted">{{ row.code }}</span></td>
                            <td class="px-3 py-1.5 text-muted">{{ row.shift ?? '-' }}</td>
                            <td class="px-3 py-1.5"><StatusBadge v-if="row.status" :status="row.status" :tone="tones[row.status]" :label="t(`hr.status.${row.status}`)" /><span v-else class="text-muted">{{ t('hr.unmarked') }}</span></td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ row.check_in ? localizeDigits(row.check_in) : '-' }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ row.check_out ? localizeDigits(row.check_out) : '-' }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ time(row.late_minutes) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ time(row.worked_minutes) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ time(row.overtime_minutes) }}</td>
                            <td class="px-3 py-1.5 text-xs text-muted"><AppIcon v-if="row.record_id" :name="row.is_manual ? 'pen-to-square' : 'fingerprint'" size="sm" /> {{ row.record_id ? (row.is_manual ? t('hr.source_manual') : t('hr.source_device')) : '' }}</td>
                            <td class="px-3 py-1.5 text-right">
                                <span v-if="can('attendance.manage')" class="inline-flex gap-1">
                                    <UiButton v-if="row.is_manual" size="xs" variant="ghost-danger" :label="t('hr.revert')" icon="rotate-left" icon-only @click="router.delete(route('admin.attendance.destroy', row.record_id), { preserveScroll: true })" />
                                    <UiButton size="xs" @click="mark(row)">{{ t('hr.mark') }}</UiButton>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!rows.length"><td colspan="10" class="px-3 py-8 text-center text-muted">{{ t('hr.no_employees') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </template>

        <template v-else>
            <div class="mb-2 flex flex-wrap gap-3 text-xs text-muted">
                <span v-for="(code, status) in codes" :key="status" class="inline-flex items-center gap-1"><span class="inline-flex h-5 w-5 items-center justify-center rounded text-[11px] font-semibold" :class="cellTone[status]">{{ code }}</span> {{ t(`hr.status.${status}`) }}</span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-border bg-surface">
                <table class="min-w-full text-[12px]">
                    <thead class="bg-subtle text-muted">
                        <tr>
                            <th class="sticky left-0 z-10 bg-subtle px-3 py-2 text-left font-medium">{{ t('hr.employee') }}</th>
                            <th v-for="day in monthDays" :key="day" class="w-7 px-0.5 py-2 text-center font-medium tabular-nums">{{ localizeDigits(day) }}</th>
                            <th class="px-2 py-2 text-right font-medium">{{ t('hr.status.present') }}</th>
                            <th class="px-2 py-2 text-right font-medium">{{ t('hr.status.late') }}</th>
                            <th class="px-2 py-2 text-right font-medium">{{ t('hr.status.absent') }}</th>
                            <th class="px-2 py-2 text-right font-medium">{{ t('hr.status.leave') }}</th>
                            <th class="px-2 py-2 text-right font-medium">{{ t('hr.overtime') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="row in rows" :key="row.employee_id">
                            <td class="sticky left-0 z-10 bg-surface px-3 py-1.5 font-medium whitespace-nowrap">{{ row.name }}</td>
                            <td v-for="day in monthDays" :key="day" class="px-0.5 py-1 text-center">
                                <span v-if="row.cells[day]" class="inline-flex h-5 w-5 items-center justify-center rounded text-[11px] font-semibold" :class="cellTone[row.cells[day]]" :title="t(`hr.status.${row.cells[day]}`)">{{ codes[row.cells[day]] }}</span>
                            </td>
                            <td class="px-2 py-1.5 text-right tabular-nums">{{ localizeDigits(row.present) }}</td>
                            <td class="px-2 py-1.5 text-right tabular-nums">{{ localizeDigits(row.late) }}</td>
                            <td class="px-2 py-1.5 text-right tabular-nums">{{ localizeDigits(row.absent) }}</td>
                            <td class="px-2 py-1.5 text-right tabular-nums">{{ localizeDigits(row.leave) }}</td>
                            <td class="px-2 py-1.5 text-right tabular-nums">{{ time(row.overtime_minutes) }}</td>
                        </tr>
                        <tr v-if="!rows.length"><td :colspan="daysInMonth + 6" class="px-3 py-8 text-center text-muted">{{ t('hr.no_employees') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </template>

        <SlideOver v-model:open="open" :title="t('hr.mark_attendance')">
            <form id="attendance-form" class="grid grid-cols-2 gap-x-4 gap-y-3" novalidate @submit.prevent="submit">
                <p class="col-span-full text-[13px]"><span class="font-medium">{{ editingName }}</span> <span class="text-muted">{{ form.work_date }}</span></p>
                <FormField id="status" :label="t('hr.status_label')" :error="form.errors.status" required span><SelectInput id="status" v-model="form.status" :options="statusOptions" /></FormField>
                <FormField id="check_in" :label="t('hr.check_in')" :error="form.errors.check_in" optional><input id="check_in" v-model="form.check_in" type="time" class="control" /></FormField>
                <FormField id="check_out" :label="t('hr.check_out')" :error="form.errors.check_out" optional><input id="check_out" v-model="form.check_out" type="time" class="control" /></FormField>
                <FormField id="note" :label="t('fields.notes')" :error="form.errors.note" optional span><TextInput id="note" v-model="form.note" /></FormField>
                <p class="col-span-full text-xs text-muted">{{ t('hr.manual_note') }}</p>
            </form>
            <template #footer>
                <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="attendance-form" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
