<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    leaves: { type: Object, required: true },
    employees: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const { can } = usePermissions();
const open = ref(false);
const form = useForm({ employee_id: null, type: 'casual', from_date: '', to_date: '', reason: '' });

const types = ['casual', 'sick', 'annual', 'unpaid'].map((value) => ({ value, label: t(`hr.leave_types.${value}`) }));
const statuses = ['pending', 'approved', 'rejected'].map((value) => ({ value, label: t(`hr.leave_status.${value}`) }));
const employeeOptions = props.employees.map((employee) => ({ value: employee.id, label: `${employee.name} (${employee.code})` }));
const tones = { pending: 'warning', approved: 'success', rejected: 'danger' };

const columns = [
    { key: 'employee', label: t('hr.employee'), type: 'text', grow: true },
    { key: 'type', label: t('hr.leave_type'), type: 'text' },
    { key: 'from_date', label: t('hr.from'), type: 'date' },
    { key: 'to_date', label: t('hr.to'), type: 'date' },
    { key: 'days', label: t('hr.days'), type: 'number' },
    { key: 'status', label: t('hr.status_label'), type: 'text' },
    { key: 'actions', label: '', type: 'text' },
];

function create() {
    form.reset();
    form.clearErrors();
    open.value = true;
}

function submit() {
    form.post(route('admin.leaves.store'), { preserveScroll: true, onError: focusFirstError, onSuccess: () => (open.value = false) });
}

const decide = (leave, action) => router.post(route(`admin.leaves.${action}`, leave.id), {}, { preserveScroll: true });
</script>

<template>
    <div>
        <Head :title="t('nav.leaves')" />
        <PageHeader :title="t('nav.leaves')" :description="t('hr.leaves_description')">
            <template #actions>
                <UiButton v-if="can('leaves.manage')" variant="primary" icon="plus" @click="create">{{ t('hr.new_leave') }}</UiButton>
            </template>
        </PageHeader>
        <FilterBar v-slot="{ form: filter }" :filters="filters" :searchable="false">
            <SelectInput v-model="filter.status" class="!w-44" :placeholder="t('hr.all_statuses')" :options="statuses" :aria-label="t('hr.status_label')" />
        </FilterBar>
        <DataTable :columns="columns" :rows="leaves" :filtered="!!filters.status" empty-icon="calendar" :empty-title="t('hr.no_leaves')" @clear-filters="router.get(route('admin.leaves.index'))">
            <template #cell-employee="{ row }"><span class="block font-medium">{{ row.employee }}</span><span class="block text-xs text-muted">{{ row.code }}</span></template>
            <template #cell-type="{ value }">{{ t(`hr.leave_types.${value}`) }}</template>
            <template #cell-status="{ value }"><StatusBadge :status="value" :tone="tones[value]" :label="t(`hr.leave_status.${value}`)" /></template>
            <template #cell-actions="{ row }">
                <span v-if="row.status === 'pending' && can('leaves.manage')" class="flex justify-end gap-1">
                    <UiButton size="xs" variant="primary" @click="decide(row, 'approve')">{{ t('hr.approve') }}</UiButton>
                    <UiButton size="xs" variant="ghost-danger" @click="decide(row, 'reject')">{{ t('hr.reject') }}</UiButton>
                </span>
            </template>
        </DataTable>

        <SlideOver v-model:open="open" :title="t('hr.new_leave')">
            <form id="leave-form" class="grid grid-cols-2 gap-x-4 gap-y-3" novalidate @submit.prevent="submit">
                <FormField id="employee_id" :label="t('hr.employee')" :error="form.errors.employee_id" required span><SelectInput id="employee_id" v-model="form.employee_id" :options="employeeOptions" :placeholder="t('hr.choose_employee')" /></FormField>
                <FormField id="type" :label="t('hr.leave_type')" :error="form.errors.type" required span><SelectInput id="type" v-model="form.type" :options="types" /></FormField>
                <FormField id="from_date" :label="t('hr.from')" :error="form.errors.from_date" required><input id="from_date" v-model="form.from_date" type="date" class="control" /></FormField>
                <FormField id="to_date" :label="t('hr.to')" :error="form.errors.to_date" required><input id="to_date" v-model="form.to_date" type="date" class="control" /></FormField>
                <FormField id="reason" :label="t('hr.reason')" optional span><TextArea id="reason" v-model="form.reason" :rows="2" /></FormField>
            </form>
            <template #footer>
                <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="leave-form" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
