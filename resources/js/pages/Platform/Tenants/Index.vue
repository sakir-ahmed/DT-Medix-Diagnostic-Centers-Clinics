<script setup>
import { Head, router } from '@inertiajs/vue3';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import RowActions from '@/components/ui-kit/RowActions.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { post } from '@/lib/http';
import { useI18n } from '@/lib/i18n';

defineProps({
    tenants: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const confirm = useConfirm();

const columns = [
    { key: 'name', label: t('platform.tenant'), type: 'text', grow: true },
    { key: 'slug', label: t('platform.slug'), type: 'id' },
    { key: 'plan', label: t('platform.plan'), type: 'text' },
    { key: 'status', label: t('common.status'), type: 'status' },
    { key: 'trial_days_left', label: t('platform.trial_days_left'), type: 'number' },
    { key: 'branches_count', label: t('platform.branches'), type: 'number' },
    { key: 'users_count', label: t('platform.users'), type: 'number' },
    { key: 'actions', label: '', type: 'actions' },
];

function actions(tenant) {
    return [
        { label: t('common.edit'), icon: 'pen-to-square', onSelect: () => router.visit(route('platform.tenants.edit', tenant.id)) },
        { label: t('platform.impersonate'), icon: 'user-shield', onSelect: () => impersonate(tenant) },
        { label: t('platform.export'), icon: 'file-export', onSelect: () => router.post(route('platform.tenants.export', tenant.id)) },
        { separator: true },
        tenant.status === 'suspended'
            ? { label: t('platform.resume'), icon: 'play', onSelect: () => router.post(route('platform.tenants.resume', tenant.id)) }
            : { label: t('platform.suspend'), icon: 'ban', danger: true, onSelect: () => suspend(tenant) },
    ];
}

function impersonate(tenant) {
    confirm({
        variant: 'warning',
        title: t('platform.impersonate_title', { tenant: tenant.name }),
        message: t('platform.impersonate_message'),
        confirmText: t('platform.impersonate'),
        action: () => post(route('platform.tenants.impersonate', tenant.id), {}, { preserveState: false }),
    });
}

function suspend(tenant) {
    confirm({
        variant: 'danger',
        level: 'typed',
        typedWord: tenant.slug,
        title: t('platform.suspend_title', { tenant: tenant.name }),
        message: t('platform.suspend_message'),
        confirmText: t('platform.suspend'),
        action: () => post(route('platform.tenants.suspend', tenant.id)),
    });
}

const statuses = ['trial', 'active', 'read_only', 'suspended'].map((value) => ({ value, label: t(`statuses.${value}`) }));
</script>

<template>
    <div>
        <Head :title="t('platform.tenants')" />
        <PageHeader :title="t('platform.tenants')" :description="t('platform.tenants_description')">
            <template #actions>
                <UiButton variant="primary" icon="plus" :href="route('platform.tenants.create')">{{ t('platform.new_tenant') }}</UiButton>
            </template>
        </PageHeader>
        <FilterBar v-slot="{ form }" :filters="filters" :search-placeholder="t('platform.search_tenants')">
            <SelectInput v-model="form.status" class="!w-36" :placeholder="t('common.all_statuses')" :options="statuses" :aria-label="t('common.status')" />
        </FilterBar>
        <DataTable
            :columns="columns"
            :rows="tenants"
            :filtered="!!(filters.q || filters.status)"
            :empty-title="t('platform.no_tenants')"
            clickable
            @row-click="(row) => router.visit(route('platform.tenants.edit', row.id))"
            @clear-filters="router.get(route('platform.tenants.index'))"
        >
            <template #cell-plan="{ value }">{{ t(`platform.plans.${value}`) }}</template>
            <template #row-actions="{ row }"><RowActions :items="actions(row)" /></template>
            <template #empty-actions><UiButton variant="primary" icon="plus" :href="route('platform.tenants.create')">{{ t('platform.new_tenant') }}</UiButton></template>
        </DataTable>
    </div>
</template>
