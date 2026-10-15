<script setup>
import { Head, router } from '@inertiajs/vue3';
import DataTable from '@/components/ui-kit/DataTable.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useI18n } from '@/lib/i18n';

defineProps({ templates: { type: Array, required: true } });

const { t } = useI18n();
const columns = [
    { key: 'name', label: t('fields.name'), type: 'text', grow: true },
    { key: 'type', label: t('catalog.report_style'), type: 'text' },
    { key: 'department', label: t('catalog.department'), type: 'text' },
    { key: 'phrases_count', label: t('catalog.quick_phrases'), type: 'number' },
    { key: 'is_active', label: t('common.active'), type: 'bool' },
];
</script>

<template>
    <div>
        <Head :title="t('nav.report_templates')" />
        <PageHeader :title="t('nav.report_templates')" :description="t('catalog.templates_description')">
            <template #actions><UiButton variant="primary" icon="plus" :href="route('admin.report-templates.create')">{{ t('catalog.new_template') }}</UiButton></template>
        </PageHeader>
        <DataTable :columns="columns" :rows="templates" :sync-url="false" empty-icon="file-lines" :empty-title="t('catalog.no_templates')" clickable @row-click="(row) => router.visit(route('admin.report-templates.edit', row.id))">
            <template #cell-type="{ value }">{{ t(`catalog.styles.${value}`) }}</template>
        </DataTable>
    </div>
</template>
