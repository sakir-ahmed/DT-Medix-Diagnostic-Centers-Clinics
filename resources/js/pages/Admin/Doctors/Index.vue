<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DoctorForm from '@/components/doctors/DoctorForm.vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/lib/i18n';

defineProps({
    doctors: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    doctorUsers: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { can } = usePermissions();
const open = ref(false);
const form = useForm({ name: '', name_bn: '', specialty: '', qualifications: '', designation: '', bmdc_no: '', mobile: '', email: '', consultation_fee: '0.00', follow_up_fee: '0.00', follow_up_days: '', slot_minutes: 15, is_visiting: false, is_active: true, user_id: null, signature: null });

const columns = [
    { key: 'name', label: t('fields.name'), type: 'text', grow: true },
    { key: 'bmdc_no', label: t('doctors.bmdc'), type: 'id' },
    { key: 'consultation_fee', label: t('doctors.fee_column'), type: 'money' },
    { key: 'follow_up_fee', label: t('doctors.follow_up_fee_column'), type: 'money' },
    { key: 'is_active', label: t('common.active'), type: 'bool' },
];

function submit() {
    form.post(route('admin.doctors.store'), { forceFormData: true, onError: focusFirstError });
}
</script>

<template>
    <div>
        <Head :title="t('nav.doctors')" />
        <PageHeader :title="t('nav.doctors')" :description="t('doctors.description')">
            <template #actions>
                <UiButton v-if="can('doctors.manage')" variant="primary" icon="plus" @click="form.reset(); form.clearErrors(); open = true">{{ t('doctors.new_doctor') }}</UiButton>
            </template>
        </PageHeader>
        <FilterBar :filters="filters" :search-placeholder="t('doctors.search')" />
        <DataTable :columns="columns" :rows="doctors" :filtered="!!filters.q" empty-icon="user-doctor" :empty-title="t('doctors.empty')" clickable @row-click="(row) => router.visit(route('admin.doctors.show', row.id))" @clear-filters="router.get(route('admin.doctors.index'))">
            <template #cell-name="{ row }">
                <span class="block truncate font-medium">{{ row.name }}</span>
                <span class="block truncate text-xs text-muted">{{ [row.specialty, row.qualifications].filter(Boolean).join(' | ') }}</span>
            </template>
        </DataTable>
        <SlideOver v-model:open="open" :title="t('doctors.new_doctor')">
            <DoctorForm id="doctor-form" :form="form" :doctor-users="doctorUsers" @submit="submit" />
            <template #footer>
                <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="doctor-form" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
