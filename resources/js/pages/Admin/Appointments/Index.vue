<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import ComboBox from '@/components/ui-kit/ComboBox.vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { usePermissions } from '@/composables/usePermissions';
import { echo } from '@/lib/echo';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    date: { type: String, required: true },
    doctor_id: { type: Number, default: null },
    doctors: { type: Array, default: () => [] },
    appointments: { type: Array, default: () => [] },
    slots: { type: Array, default: () => [] },
    tenant_id: { type: Number, default: null },
    branch_id: { type: Number, default: null },
});

const { t } = useI18n();
const { can } = usePermissions();
const confirm = useConfirm();
const selectedTime = ref(null);
const patientLabel = ref('');

const form = useForm({
    patient_id: null,
    doctor_id: props.doctor_id,
    date: props.date,
    time: '',
    source: 'walk-in',
    notes: '',
});

const columns = [
    { key: 'token_no', label: t('appointments.token'), type: 'number' },
    { key: 'time', label: t('appointments.time'), type: 'text' },
    { key: 'patient', label: t('appointments.patient'), type: 'text', grow: true },
    { key: 'doctor', label: t('appointments.doctor'), type: 'text' },
    { key: 'fee', label: t('appointments.fee'), type: 'money' },
    { key: 'status', label: t('common.status'), type: 'status' },
    { key: 'actions', label: '', type: 'actions' },
];

let channelName = null;

onMounted(() => {
    const connection = echo();
    if (!connection || !props.tenant_id || !props.branch_id) {
        return;
    }
    channelName = `tenant.${props.tenant_id}.branch.${props.branch_id}.reception`;
    connection.private(channelName).listen('.AppointmentUpdated', () => {
        router.reload({ only: ['appointments', 'slots'], preserveScroll: true });
    });
});

onBeforeUnmount(() => {
    if (channelName) {
        echo()?.leave(channelName);
    }
});

function load(extra = {}) {
    router.get(route('admin.appointments.index'), { date: form.date, doctor_id: form.doctor_id, ...extra }, { preserveState: true, preserveScroll: true });
}

async function searchPatients(query) {
    const response = await fetch(`${route('admin.patients.lookup')}?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    if (!response.ok) {
        return [];
    }
    const json = await response.json();

    return json.results ?? [];
}

function choosePatient(option) {
    form.patient_id = option?.value ?? null;
    patientLabel.value = option?.label ?? '';
}

function chooseSlot(slot) {
    if (!slot.available) {
        return;
    }
    selectedTime.value = slot.time;
    form.time = slot.time;
    form.doctor_id = props.doctor_id;
    form.date = props.date;
}

function book() {
    form.post(route('admin.appointments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('patient_id', 'time', 'notes');
            selectedTime.value = null;
            patientLabel.value = '';
        },
    });
}

async function setStatus(row, status) {
    let reason = null;
    if (status === 'cancelled' || status === 'no-show') {
        const result = await confirm({
            variant: 'danger',
            level: 'reason',
            title: t('appointments.status_updated'),
            confirmText: t(`statuses.${status}`),
        });
        if (!result.confirmed) {
            return;
        }
        reason = result.note;
    }
    router.patch(route('admin.appointments.status', row.id), { status, reason }, { preserveScroll: true });
}
</script>

<template>
    <div>
        <Head :title="t('nav.appointments')" />
        <PageHeader :title="t('nav.appointments')" :description="t('appointments.description')" />

        <div class="mb-3 flex flex-wrap items-end gap-2">
            <FormField id="appointment-date" :label="t('appointments.day')">
                <template #default="{ id }">
                    <TextInput :id="id" v-model="form.date" type="date" @change="load()" />
                </template>
            </FormField>
            <FormField id="appointment-doctor" :label="t('appointments.doctor')">
                <template #default="{ id }">
                    <SelectInput :id="id" v-model="form.doctor_id" :options="doctors" :placeholder="t('appointments.all_doctors')" @change="load()" />
                </template>
            </FormField>
        </div>

        <div class="grid items-start gap-3 lg:grid-cols-5">
            <section class="card lg:col-span-2">
                <h2 class="mb-2 text-sm font-semibold">{{ t('appointments.slots') }}</h2>
                <p v-if="!doctor_id" class="text-[13px] text-muted">{{ t('appointments.all_doctors') }}</p>
                <p v-else-if="slots.length === 0" class="text-[13px] text-muted">{{ t('appointments.no_slots') }}</p>
                <div v-else class="flex flex-wrap gap-1.5">
                    <button
                        v-for="slot in slots"
                        :key="slot.time"
                        type="button"
                        class="h-7 rounded-md border px-2 text-xs tabular-nums"
                        :class="slot.available ? (selectedTime === slot.time ? 'border-accent bg-accent/10' : 'border-border hover:bg-subtle') : 'cursor-not-allowed border-border text-muted line-through'"
                        :disabled="!slot.available"
                        @click="chooseSlot(slot)"
                    >
                        {{ slot.time }}
                    </button>
                </div>

                <form v-if="can('appointments.manage') && doctor_id" class="mt-3 space-y-2 border-t border-border pt-3" @submit.prevent="book">
                    <FormField id="book-patient" :label="t('appointments.patient')" required :error="form.errors.patient_id">
                        <template #default="{ id, invalid }">
                            <ComboBox :id="id" :search="searchPatients" clear-on-select :invalid="invalid" :selected-label="patientLabel" @select="choosePatient" />
                        </template>
                    </FormField>
                    <FormField id="book-source" :label="t('appointments.source')">
                        <template #default="{ id }">
                            <SelectInput :id="id" v-model="form.source" :options="[{ value: 'walk-in', label: t('appointments.walk-in') }, { value: 'phone', label: t('appointments.phone') }]" />
                        </template>
                    </FormField>
                    <UiButton type="submit" variant="primary" icon="calendar-check" :loading="form.processing" :disabled="!form.patient_id || !form.time">{{ t('appointments.book') }}</UiButton>
                </form>
            </section>

            <section class="lg:col-span-3">
                <h2 class="mb-2 text-sm font-semibold">{{ t('appointments.queue') }}</h2>
                <DataTable :columns="columns" :rows="appointments" :empty-title="t('appointments.empty')" empty-icon="calendar-check" :sync-url="false">
                    <template #cell-patient="{ row }">{{ row.patient?.name }} <span class="text-xs text-muted">{{ row.patient?.patient_code }}</span></template>
                    <template #cell-doctor="{ row }">{{ row.doctor?.name }}</template>
                    <template #cell-fee="{ row }"><MoneyText :value="row.fee" symbol /></template>
                    <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
                    <template #cell-actions="{ row }">
                        <div v-if="can('appointments.manage')" class="flex justify-end gap-1">
                            <UiButton v-for="status in row.allowed" :key="status" size="xs" @click="setStatus(row, status)">{{ t(`statuses.${status}`) }}</UiButton>
                        </div>
                    </template>
                </DataTable>
            </section>
        </div>
    </div>
</template>
