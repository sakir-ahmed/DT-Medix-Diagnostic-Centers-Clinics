<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import MobileInput from '@/components/ui-kit/MobileInput.vue';
import MoneyInput from '@/components/ui-kit/MoneyInput.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    employees: { type: Object, required: true },
    shifts: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canSeePay: { type: Boolean, default: false },
});

const { t } = useI18n();
const { can } = usePermissions();
const open = ref(false);
const editing = ref(null);

const empty = () => ({
    name: '', name_bn: '', designation: '', department: '', mobile: '', joined_on: new Date().toISOString().slice(0, 10), device_pin: '', work_shift_id: null,
    basic_salary: '', house_allowance: '', medical_allowance: '', transport_allowance: '', other_allowance: '', overtime_rate: '',
    payment_method: null, payment_account: '', notes: '', is_active: true,
});
const form = useForm(empty());

const shiftOptions = props.shifts.map((shift) => ({ value: shift.id, label: shift.name }));
const methods = ['cash', 'bkash', 'nagad', 'bank'].map((value) => ({ value, label: t(`payments.methods.${value}`) }));

const columns = [
    { key: 'code', label: t('fields.code'), type: 'id' },
    { key: 'name', label: t('fields.name'), type: 'text', grow: true },
    { key: 'department', label: t('hr.department'), type: 'text' },
    { key: 'shift', label: t('hr.shift'), type: 'text' },
    { key: 'device_pin', label: t('hr.device_pin'), type: 'id' },
    ...(props.canSeePay ? [{ key: 'gross', label: t('hr.gross_salary'), type: 'money' }] : []),
    { key: 'is_active', label: t('common.active'), type: 'bool' },
];

function edit(row = null) {
    if (!can('hr.manage')) {
        return;
    }
    editing.value = row;
    form.clearErrors();
    Object.assign(form, row ? { ...empty(), ...row, mobile: row.mobile ?? '', payment_account: row.payment_account ?? '' } : empty());
    open.value = true;
}

function submit() {
    const options = { preserveScroll: true, onError: focusFirstError, onSuccess: () => (open.value = false) };
    editing.value ? form.put(route('admin.employees.update', editing.value.id), options) : form.post(route('admin.employees.store'), options);
}
</script>

<template>
    <div>
        <Head :title="t('nav.employees')" />
        <PageHeader :title="t('nav.employees')" :description="t('hr.employees_description')">
            <template #actions>
                <UiButton v-if="can('hr.manage')" variant="primary" icon="plus" @click="edit()">{{ t('hr.new_employee') }}</UiButton>
            </template>
        </PageHeader>
        <FilterBar :filters="filters" :search-placeholder="t('hr.search_employees')" />
        <DataTable :columns="columns" :rows="employees" :filtered="!!filters.q" empty-icon="users" :empty-title="t('hr.no_employees')" :clickable="can('hr.manage')" @row-click="edit" @clear-filters="router.get(route('admin.employees.index'))">
            <template #cell-name="{ row }">
                <span class="block truncate font-medium">{{ row.name }}</span>
                <span v-if="row.designation" class="block truncate text-xs text-muted">{{ row.designation }}</span>
            </template>
        </DataTable>

        <SlideOver v-model:open="open" :title="editing ? t('hr.edit_employee') : t('hr.new_employee')">
            <form id="employee-form" class="grid grid-cols-2 gap-x-4 gap-y-3" novalidate @submit.prevent="submit">
                <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required span><TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" /></FormField>
                <FormField id="name_bn" :label="t('hr.name_bn')" optional span><TextInput id="name_bn" v-model="form.name_bn" /></FormField>
                <FormField id="designation" :label="t('hr.designation')" optional><TextInput id="designation" v-model="form.designation" /></FormField>
                <FormField id="department" :label="t('hr.department')" optional><TextInput id="department" v-model="form.department" /></FormField>
                <FormField id="mobile" :label="t('fields.mobile')" :error="form.errors.mobile" optional><MobileInput id="mobile" v-model="form.mobile" :invalid="!!form.errors.mobile" /></FormField>
                <FormField id="joined_on" :label="t('hr.joined_on')" :error="form.errors.joined_on" required><input id="joined_on" v-model="form.joined_on" type="date" class="control" /></FormField>
                <FormField id="device_pin" :label="t('hr.device_pin')" :helper="t('hr.device_pin_helper')" :error="form.errors.device_pin" optional><TextInput id="device_pin" v-model="form.device_pin" :invalid="!!form.errors.device_pin" /></FormField>
                <FormField id="work_shift_id" :label="t('hr.shift')" optional><SelectInput id="work_shift_id" v-model="form.work_shift_id" :placeholder="t('common.none')" :options="shiftOptions" /></FormField>

                <template v-if="canSeePay">
                    <h3 class="col-span-full mt-2 text-sm font-semibold">{{ t('hr.salary_structure') }}</h3>
                    <FormField id="basic_salary" :label="t('hr.basic_salary')" :error="form.errors.basic_salary"><MoneyInput id="basic_salary" v-model="form.basic_salary" /></FormField>
                    <FormField id="overtime_rate" :label="t('hr.overtime_rate')" :helper="t('hr.overtime_rate_helper')" optional><MoneyInput id="overtime_rate" v-model="form.overtime_rate" /></FormField>
                    <FormField id="house_allowance" :label="t('hr.house_allowance')" optional><MoneyInput id="house_allowance" v-model="form.house_allowance" /></FormField>
                    <FormField id="medical_allowance" :label="t('hr.medical_allowance')" optional><MoneyInput id="medical_allowance" v-model="form.medical_allowance" /></FormField>
                    <FormField id="transport_allowance" :label="t('hr.transport_allowance')" optional><MoneyInput id="transport_allowance" v-model="form.transport_allowance" /></FormField>
                    <FormField id="other_allowance" :label="t('hr.other_allowance')" optional><MoneyInput id="other_allowance" v-model="form.other_allowance" /></FormField>
                    <FormField id="payment_method" :label="t('hr.payment_method')" optional><SelectInput id="payment_method" v-model="form.payment_method" :placeholder="t('common.none')" :options="methods" /></FormField>
                    <FormField id="payment_account" :label="t('hr.payment_account')" optional><TextInput id="payment_account" v-model="form.payment_account" autocomplete="off" /></FormField>
                </template>

                <FormField id="notes" :label="t('fields.notes')" optional span><TextArea id="notes" v-model="form.notes" :rows="2" /></FormField>
                <div class="col-span-full"><SwitchField v-model="form.is_active" :label="t('common.active')" /></div>
            </form>
            <template #footer>
                <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="employee-form" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
