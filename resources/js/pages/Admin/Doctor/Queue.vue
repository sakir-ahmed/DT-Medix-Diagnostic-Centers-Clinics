<script setup>
import { Head, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import EmptyState from '@/components/ui-kit/EmptyState.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { echo } from '@/lib/echo';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    doctor: { type: Object, default: null },
    appointments: { type: Array, default: () => [] },
    branch_id: { type: Number, default: null },
});

const { t } = useI18n();

const columns = [
    { key: 'token_no', label: t('appointments.token'), type: 'number' },
    { key: 'time', label: t('appointments.time'), type: 'text' },
    { key: 'patient', label: t('appointments.patient'), type: 'text', grow: true },
    { key: 'waiting_minutes', label: t('appointments.waiting'), type: 'number' },
    { key: 'status', label: t('common.status'), type: 'status' },
];

let channelName = null;

onMounted(() => {
    const connection = echo();
    if (!connection || !props.doctor) {
        return;
    }
    channelName = `doctor.${props.doctor.id}.queue`;
    connection.private(channelName).listen('.AppointmentUpdated', () => {
        router.reload({ only: ['appointments'], preserveScroll: true });
    });
});

onBeforeUnmount(() => {
    if (channelName) {
        echo()?.leave(channelName);
    }
});

function nextPatient() {
    router.post(route('admin.doctor.queue.next'), {}, { preserveScroll: true });
}
</script>

<template>
    <div>
        <Head :title="t('nav.my_queue')" />
        <PageHeader :title="doctor?.name ?? t('nav.my_queue')" :description="t('appointments.queue_description')">
            <template v-if="doctor" #actions>
                <UiButton variant="primary" icon="user-doctor" @click="nextPatient">{{ t('appointments.next_patient') }}</UiButton>
            </template>
        </PageHeader>
        <EmptyState v-if="!doctor" icon="user-doctor" :title="t('appointments.no_doctor')" />
        <DataTable v-else :columns="columns" :rows="appointments" :empty-title="t('appointments.empty')" empty-icon="user-doctor" :sync-url="false">
            <template #cell-patient="{ row }">{{ row.patient?.name }} <span class="text-xs text-muted">{{ row.patient?.patient_code }}</span></template>
            <template #cell-waiting_minutes="{ value }">{{ value === null || value === undefined ? '–' : t('appointments.minutes', { count: value }) }}</template>
            <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
        </DataTable>
    </div>
</template>
