<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DoctorForm from '@/components/doctors/DoctorForm.vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useConfirm } from '@/composables/useConfirm';
import { usePermissions } from '@/composables/usePermissions';
import { destroy } from '@/lib/http';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    doctor: { type: Object, required: true },
    schedules: { type: Array, default: () => [] },
    leaves: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    signatureUrl: { type: String, default: null },
    doctorUsers: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { can } = usePermissions();
const confirm = useConfirm();
const editing = ref(false);

const doctorForm = useForm({ ...props.doctor, mobile: props.doctor.mobile ?? '', follow_up_days: props.doctor.follow_up_days ?? '', signature: null });
const schedule = useForm({ branch_id: props.branches[0]?.id ?? null, weekday: 0, start_time: '17:00', end_time: '21:00', slot_minutes: '', max_patients: '' });
const leave = useForm({ start_date: '', end_date: '', reason: '' });

const weekdays = [0, 1, 2, 3, 4, 5, 6].map((value) => ({ value, label: t(`doctors.weekdays.${value}`) }));

function saveDoctor() {
    doctorForm
        .transform((data) => {
            const payload = { ...data };
            if (!payload.signature) delete payload.signature;
            return payload;
        })
        .post(route('admin.doctors.update', props.doctor.id), { forceFormData: true, preserveScroll: true, onError: focusFirstError, onSuccess: () => (editing.value = false) });
}

const addSchedule = () => schedule.post(route('admin.doctors.schedules.store', props.doctor.id), { preserveScroll: true, onError: focusFirstError, onSuccess: () => schedule.reset('slot_minutes', 'max_patients') });
const addLeave = () => leave.post(route('admin.doctors.leaves.store', props.doctor.id), { preserveScroll: true, onError: focusFirstError, onSuccess: () => leave.reset() });

function removeSchedule(row) {
    confirm({
        variant: 'danger',
        title: t('doctors.remove_schedule_title'),
        message: t('doctors.remove_schedule_message'),
        confirmText: t('doctors.remove_schedule'),
        facts: [{ label: t('doctors.weekday'), value: t(`doctors.weekdays.${row.weekday}`) }, { label: t('doctors.time'), value: `${row.start_time} - ${row.end_time}` }],
        action: () => destroy(route('admin.doctors.schedules.destroy', [props.doctor.id, row.id])),
    });
}

function removeLeave(row) {
    confirm({
        variant: 'danger',
        title: t('doctors.remove_leave_title'),
        confirmText: t('doctors.remove_leave'),
        facts: [{ label: t('periods.from'), value: row.start_date }, { label: t('periods.to'), value: row.end_date }],
        action: () => destroy(route('admin.doctors.leaves.destroy', [props.doctor.id, row.id])),
    });
}

const scheduleColumns = [
    { key: 'weekday', label: t('doctors.weekday'), type: 'text' },
    { key: 'time', label: t('doctors.time'), type: 'id' },
    { key: 'branch', label: t('fields.branch'), type: 'text', grow: true },
    { key: 'slot_minutes', label: t('doctors.slot_minutes'), type: 'number' },
    { key: 'max_patients', label: t('doctors.max_patients'), type: 'number' },
    { key: 'actions', label: '', type: 'actions' },
];
const leaveColumns = [
    { key: 'start_date', label: t('periods.from'), type: 'date' },
    { key: 'end_date', label: t('periods.to'), type: 'date' },
    { key: 'reason', label: t('fields.reason'), type: 'text', grow: true },
    { key: 'actions', label: '', type: 'actions' },
];
</script>

<template>
    <div>
        <Head :title="doctor.name" />
        <PageHeader :title="doctor.name" :description="[doctor.specialty, doctor.qualifications].filter(Boolean).join(' | ')" :breadcrumbs="[{ label: t('nav.doctors'), href: route('admin.doctors.index') }, { label: doctor.name }]">
            <template #actions>
                <StatusBadge :status="doctor.is_active ? 'active' : 'cancelled'" :label="doctor.is_active ? t('common.active') : t('common.inactive')" />
                <UiButton v-if="can('doctors.manage')" icon="pen-to-square" @click="editing = true">{{ t('common.edit') }}</UiButton>
            </template>
        </PageHeader>

        <div class="mb-3 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="card"><div class="text-xs text-muted">{{ t('doctors.bmdc') }}</div><div class="font-semibold tabular-nums">{{ doctor.bmdc_no || '–' }}</div></div>
            <div class="card"><div class="text-xs text-muted">{{ t('doctors.consultation_fee') }}</div><MoneyText :value="doctor.consultation_fee" symbol class="font-semibold" /></div>
            <div class="card"><div class="text-xs text-muted">{{ t('doctors.follow_up_fee') }}</div><MoneyText :value="doctor.follow_up_fee" symbol class="font-semibold" /></div>
            <div class="card"><div class="text-xs text-muted">{{ t('doctors.slot_minutes') }}</div><div class="font-semibold tabular-nums">{{ doctor.slot_minutes }}</div></div>
        </div>

        <section class="mb-4 space-y-2">
            <h2 class="text-sm font-semibold">{{ t('doctors.weekly_schedule') }}</h2>
            <form v-if="can('schedules.manage')" class="card flex flex-wrap items-end gap-2" novalidate @submit.prevent="addSchedule">
                <FormField id="branch_id" :label="t('fields.branch')" :error="schedule.errors.branch_id" required>
                    <SelectInput id="branch_id" v-model="schedule.branch_id" :options="branches.map((b) => ({ value: b.id, label: `${b.code} - ${b.name}` }))" class="!w-48" />
                </FormField>
                <FormField id="weekday" :label="t('doctors.weekday')" :error="schedule.errors.weekday" required>
                    <SelectInput id="weekday" v-model="schedule.weekday" :options="weekdays" class="!w-32" />
                </FormField>
                <FormField id="start_time" :label="t('doctors.start')" :error="schedule.errors.start_time" required>
                    <input id="start_time" v-model="schedule.start_time" type="time" class="control !w-28" />
                </FormField>
                <FormField id="end_time" :label="t('doctors.end')" :error="schedule.errors.end_time" required>
                    <input id="end_time" v-model="schedule.end_time" type="time" class="control !w-28" />
                </FormField>
                <FormField id="slot" :label="t('doctors.slot_minutes')" optional>
                    <TextInput id="slot" v-model="schedule.slot_minutes" inputmode="numeric" align="right" class="!w-24" />
                </FormField>
                <FormField id="max_patients" :label="t('doctors.max_patients')" optional>
                    <TextInput id="max_patients" v-model="schedule.max_patients" inputmode="numeric" align="right" class="!w-24" />
                </FormField>
                <UiButton type="submit" variant="primary" icon="plus" :loading="schedule.processing">{{ t('doctors.add_schedule') }}</UiButton>
            </form>
            <DataTable :columns="scheduleColumns" :rows="schedules" :sync-url="false" empty-icon="calendar-check" :empty-title="t('doctors.no_schedule')">
                <template #cell-weekday="{ value }">{{ t(`doctors.weekdays.${value}`) }}</template>
                <template #cell-time="{ row }">{{ row.start_time }} - {{ row.end_time }}</template>
                <template #row-actions="{ row }">
                    <UiButton v-if="can('schedules.manage')" size="xs" variant="ghost-danger" icon-only icon="trash" :label="t('doctors.remove_schedule')" @click="removeSchedule(row)" />
                </template>
            </DataTable>
        </section>

        <section class="space-y-2">
            <h2 class="text-sm font-semibold">{{ t('doctors.leave_days') }}</h2>
            <form v-if="can('schedules.manage')" class="card flex flex-wrap items-end gap-2" novalidate @submit.prevent="addLeave">
                <FormField id="start_date" :label="t('periods.from')" :error="leave.errors.start_date" required>
                    <input id="start_date" v-model="leave.start_date" type="date" class="control !w-36" />
                </FormField>
                <FormField id="end_date" :label="t('periods.to')" :error="leave.errors.end_date" required>
                    <input id="end_date" v-model="leave.end_date" type="date" class="control !w-36" :min="leave.start_date || undefined" />
                </FormField>
                <FormField id="reason" :label="t('fields.reason')" optional>
                    <TextInput id="reason" v-model="leave.reason" class="!w-64" />
                </FormField>
                <UiButton type="submit" variant="primary" icon="plus" :loading="leave.processing">{{ t('doctors.add_leave') }}</UiButton>
            </form>
            <DataTable :columns="leaveColumns" :rows="leaves" :sync-url="false" empty-icon="calendar" :empty-title="t('doctors.no_leave')">
                <template #row-actions="{ row }">
                    <UiButton v-if="can('schedules.manage')" size="xs" variant="ghost-danger" icon-only icon="trash" :label="t('doctors.remove_leave')" @click="removeLeave(row)" />
                </template>
            </DataTable>
        </section>

        <SlideOver v-model:open="editing" :title="t('doctors.edit_doctor')">
            <DoctorForm id="doctor-edit-form" :form="doctorForm" :doctor-users="doctorUsers" :signature-url="signatureUrl" @submit="saveDoctor" />
            <template #footer>
                <UiButton @click="editing = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="doctor-edit-form" variant="primary" icon="floppy-disk" :loading="doctorForm.processing">{{ t('common.save_changes') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
