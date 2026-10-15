<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PatientFormSlideOver from '@/components/patients/PatientFormSlideOver.vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useShortcuts } from '@/composables/useShortcuts';
import { useI18n } from '@/lib/i18n';

defineProps({
    patients: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    bloodGroups: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { can } = usePermissions();
const creating = ref(false);

useShortcuts({
    '/': () => document.querySelector('[data-search]')?.focus(),
    F2: () => can('patients.create') && (creating.value = true),
});

const columns = [
    { key: 'patient_code', label: t('patients.patient_id'), type: 'id' },
    { key: 'name', label: t('patients.name'), type: 'text', grow: true },
    { key: 'age_text', label: t('patients.age'), type: 'text' },
    { key: 'sex', label: t('patients.sex_label'), type: 'text' },
    { key: 'mobile', label: t('fields.mobile'), type: 'id' },
    { key: 'created_at', label: t('patients.registered'), type: 'date' },
];
</script>

<template>
    <div>
        <Head :title="t('nav.patients')" />
        <PageHeader :title="t('nav.patients')" :description="t('patients.index_description')">
            <template #actions>
                <UiButton v-if="can('patients.create')" variant="primary" icon="user-plus" @click="creating = true">{{ t('patients.new_patient') }}</UiButton>
            </template>
        </PageHeader>
        <FilterBar :filters="filters" :search-placeholder="t('patients.search_placeholder')" />
        <DataTable
            :columns="columns"
            :rows="patients"
            :filtered="!!filters.q"
            :empty-title="t('patients.empty')"
            :empty-description="t('patients.empty_hint')"
            empty-icon="hospital-user"
            clickable
            @row-click="(row) => router.visit(route('admin.patients.show', row.id))"
            @clear-filters="router.get(route('admin.patients.index'))"
        >
            <template #cell-name="{ row }">
                <span class="block truncate font-medium">{{ row.name }}</span>
                <span v-if="row.name_bn" class="block truncate text-xs text-muted" lang="bn">{{ row.name_bn }}</span>
            </template>
            <template #cell-sex="{ value }">{{ t(`patients.sex.${value}`) }}</template>
            <template #empty-actions>
                <UiButton v-if="can('patients.create')" variant="primary" icon="user-plus" @click="creating = true">{{ t('patients.new_patient') }}</UiButton>
            </template>
        </DataTable>
        <PatientFormSlideOver v-model:open="creating" :blood-groups="bloodGroups" :initial-query="filters.q ?? ''" />
    </div>
</template>
