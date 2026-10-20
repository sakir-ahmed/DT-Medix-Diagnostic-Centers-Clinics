<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import CriticalAckDialog from '@/components/lab/CriticalAckDialog.vue';
import { formatDate } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

/**
 * Every critical value with its acknowledgement (design.md section 9).
 */
const props = defineProps({
    results: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useI18n();

const ackDialog = ref(false);
const activeItem = ref(null);

const rows = computed(() => props.results.data ?? []);

const columns = [
    { key: 'entered_at', label: t('patients.registered'), type: 'datetime' },
    { key: 'patient', label: t('billing.patient'), type: 'text', grow: true },
    { key: 'test_name', label: t('lab.tests'), type: 'text' },
    { key: 'parameter_name', label: t('results.parameter'), type: 'text' },
    { key: 'value', label: t('results.result'), type: 'text' },
    { key: 'acknowledged', label: t('common.status'), type: 'status' },
];

function openAck(itemId) {
    activeItem.value = itemId;
    ackDialog.value = true;
}

function acknowledge(payload) {
    if (!activeItem.value) {
        return;
    }

    router.post(route('admin.lab.results.acknowledge', activeItem.value), payload, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div>
        <Head :title="t('lab.critical_title')" />
        <PageHeader :title="t('lab.critical_title')" :description="t('lab.critical_description')" />

        <FilterBar :filters="filters" :apply-button="true" :only="['results']">
            <template #default="{ form }">
                <SelectInput
                    id="critical-status"
                    v-model="form.status"
                    :options="[
                        { value: '', label: t('lab.critical_all') },
                        { value: 'pending', label: t('lab.critical_pending') },
                    ]"
                    class="w-48"
                    :aria-label="t('common.status')"
                />
            </template>
        </FilterBar>

        <DataTable
            :columns="columns"
            :rows="rows"
            :filtered="filters.status === 'pending'"
            :empty-title="t('lab.critical_empty')"
            :empty-description="t('lab.critical_empty_hint')"
            empty-icon="triangle-exclamation"
            @clear-filters="router.get(route('admin.lab.critical'))"
        >
            <template #cell-entered_at="{ row }">{{ formatDate(row.entered_at, { time: true }) }}</template>
            <template #cell-patient="{ row }">
                <span class="block font-medium">{{ row.patient?.name }}</span>
                <span class="block text-xs text-muted">{{ row.patient?.patient_code }} · {{ row.sample_code }}</span>
            </template>
            <template #cell-test_name="{ row }">
                <span class="block">{{ row.test_name }}</span>
                <span class="block text-xs text-muted">{{ row.parameter_name }}</span>
            </template>
            <template #cell-value="{ row }">
                <span class="font-bold text-danger tabular-nums">{{ row.value }}</span>
                <span v-if="row.unit" class="ml-1 text-xs text-muted">{{ row.unit }}</span>
            </template>
            <template #cell-acknowledged="{ row }">
                <div v-if="row.acknowledged" class="text-xs text-muted">
                    <span class="inline-flex items-center gap-1 text-success"><AppIcon name="circle-check" size="sm" />{{ t('lab.acknowledged_by') }}</span>
                    <p>{{ row.informed_name }} · {{ formatDate(row.informed_at, { time: true }) }}</p>
                </div>
                <UiButton v-else variant="destructive" size="xs" icon="user-shield" @click="openAck(row.item_id)">
                    {{ t('lab.critical_acknowledge') }}
                </UiButton>
            </template>
        </DataTable>

        <CriticalAckDialog v-model:open="ackDialog" @confirm="acknowledge" />
    </div>
</template>
