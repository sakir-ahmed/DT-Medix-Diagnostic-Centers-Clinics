<script setup>
import { Head, router } from '@inertiajs/vue3';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/lib/i18n';

defineProps({
    tests: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { can } = usePermissions();

const columns = [
    { key: 'code', label: t('fields.code'), type: 'id' },
    { key: 'name', label: t('catalog.test_name'), type: 'text', grow: true },
    { key: 'department', label: t('catalog.department'), type: 'text' },
    { key: 'sample_type', label: t('catalog.sample_type'), type: 'text' },
    { key: 'tat_hours', label: t('catalog.tat_column'), type: 'number' },
    { key: 'price', label: t('catalog.price_column'), type: 'money' },
    { key: 'is_active', label: t('common.active'), type: 'bool' },
];
</script>

<template>
    <div>
        <Head :title="t('nav.tests')" />
        <PageHeader :title="t('nav.tests')" :description="t('catalog.tests_description')">
            <template #actions>
                <UiButton v-if="can('catalog.manage')" variant="primary" icon="plus" :href="route('admin.tests.create')">{{ t('catalog.new_test') }}</UiButton>
            </template>
        </PageHeader>
        <FilterBar v-slot="{ form }" :filters="filters" :search-placeholder="t('catalog.search_tests')">
            <SelectInput v-model="form.department" class="!w-48" :placeholder="t('catalog.all_departments')" :options="departments" :aria-label="t('catalog.department')" />
        </FilterBar>
        <DataTable
            :columns="columns"
            :rows="tests"
            :filtered="!!(filters.q || filters.department)"
            empty-icon="flask"
            :empty-title="t('catalog.no_tests')"
            :clickable="can('catalog.manage')"
            @row-click="(row) => router.visit(route('admin.tests.edit', row.id))"
            @clear-filters="router.get(route('admin.tests.index'))"
        >
            <template #cell-name="{ row }">
                <span class="block truncate font-medium">{{ row.name }}</span>
                <span v-if="row.is_outsourced" class="block truncate text-xs text-muted">{{ t('catalog.outsourced_to', { vendor: row.vendor_name }) }}</span>
            </template>
            <template #cell-sample_type="{ value }">{{ value ? t(`catalog.samples.${value}`) : '' }}</template>
        </DataTable>
    </div>
</template>
