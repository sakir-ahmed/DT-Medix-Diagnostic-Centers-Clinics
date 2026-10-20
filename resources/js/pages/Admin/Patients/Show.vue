<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PatientFormSlideOver from '@/components/patients/PatientFormSlideOver.vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import DateText from '@/components/ui-kit/DateText.vue';
import EmptyState from '@/components/ui-kit/EmptyState.vue';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import PatientBanner from '@/components/ui-kit/PatientBanner.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import TabsNav from '@/components/ui-kit/TabsNav.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    patient: { type: Object, required: true },
    family: { type: Array, default: () => [] },
    tab: { type: String, default: 'overview' },
    bloodGroups: { type: Array, default: () => [] },
    visits: { type: Array, default: () => [] },
    ordered_tests: { type: Array, default: () => [] },
    invoices: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { can } = usePermissions();
const editing = ref(false);
const activeTab = ref(props.tab);

const tabs = [
    { key: 'overview', label: t('patients.tabs.overview') },
    { key: 'visits', label: t('patients.tabs.visits') },
    { key: 'tests', label: t('patients.tabs.tests') },
    { key: 'billing', label: t('patients.tabs.billing') },
    { key: 'documents', label: t('patients.tabs.documents') },
    { key: 'timeline', label: t('patients.tabs.timeline') },
];

const details = [
    ['patients.patient_id', 'patient_code'],
    ['patients.date_of_birth', 'date_of_birth', 'date'],
    ['patients.age', 'age_text'],
    ['fields.mobile', 'mobile'],
    ['patients.relation', 'relation_to_mobile_owner'],
    ['patients.blood_group', 'blood_group'],
    ['patients.nid', 'nid_last4', 'masked'],
    ['fields.address', 'address'],
    ['patients.emergency_contact', 'emergency_contact_name'],
    ['patients.emergency_mobile', 'emergency_mobile'],
    ['patients.preferred_language', 'preferred_locale', 'locale'],
    ['patients.registered_at_branch', 'registered_branch'],
    ['patients.registered', 'created_at', 'date'],
];

const visitColumns = [
    { key: 'date', label: t('appointments.day'), type: 'date' },
    { key: 'time', label: t('appointments.time'), type: 'text' },
    { key: 'doctor', label: t('appointments.doctor'), type: 'text', grow: true },
    { key: 'visit_type', label: t('appointments.visit_type'), type: 'text' },
    { key: 'fee', label: t('appointments.fee'), type: 'money' },
    { key: 'status', label: t('common.status'), type: 'status' },
];

const testColumns = [
    { key: 'code', label: t('billing.code'), type: 'id' },
    { key: 'name', label: t('billing.test'), type: 'text', grow: true },
    { key: 'price', label: t('billing.price'), type: 'money' },
    { key: 'status', label: t('common.status'), type: 'status' },
    { key: 'created_at', label: t('patients.registered'), type: 'datetime' },
];

const invoiceColumns = [
    { key: 'invoice_no', label: t('billing.invoice_no'), type: 'id' },
    { key: 'total', label: t('billing.total'), type: 'money' },
    { key: 'paid', label: t('billing.paid'), type: 'money' },
    { key: 'due', label: t('billing.due'), type: 'money' },
    { key: 'status', label: t('common.status'), type: 'status' },
    { key: 'created_at', label: t('patients.registered'), type: 'datetime' },
];

const familyColumns = [
    { key: 'patient_code', label: t('patients.patient_id'), type: 'id' },
    { key: 'name', label: t('patients.name'), type: 'text', grow: true },
    { key: 'age_text', label: t('patients.age'), type: 'text' },
    { key: 'relation_to_mobile_owner', label: t('patients.relation'), type: 'text' },
];
</script>

<template>
    <div>
        <Head :title="patient.patient_code" />
        <PageHeader :title="t('patients.record')" :breadcrumbs="[{ label: t('nav.patients'), href: route('admin.patients.index') }, { label: patient.patient_code }]">
            <template #actions>
                <UiButton v-if="can('patients.update')" icon="pen-to-square" @click="editing = true">{{ t('common.edit') }}</UiButton>
            </template>
        </PageHeader>

        <PatientBanner :patient="patient" class="mb-3" />

        <TabsNav v-model="activeTab" :tabs="tabs" />

        <div v-if="activeTab === 'overview'" class="grid gap-3 lg:grid-cols-3">
            <section class="card lg:col-span-2">
                <h2 class="mb-2 text-sm font-semibold">{{ t('patients.details') }}</h2>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-1.5 sm:grid-cols-2">
                    <div v-for="[label, key, type] in details" :key="key" class="flex gap-2 text-[13px]">
                        <dt class="w-36 shrink-0 text-muted">{{ t(label) }}</dt>
                        <dd class="min-w-0 break-words">
                            <DateText v-if="type === 'date'" :value="patient[key]" />
                            <template v-else-if="type === 'masked'">{{ patient[key] ? `**** ${patient[key]}` : '–' }}</template>
                            <template v-else-if="type === 'locale'">{{ patient[key] === 'bn' ? 'বাংলা' : 'English' }}</template>
                            <template v-else>{{ patient[key] || '–' }}</template>
                        </dd>
                    </div>
                </dl>
                <div v-if="patient.notes" class="mt-3 rounded-md bg-subtle p-2 text-[13px]">
                    <span class="text-muted">{{ t('fields.notes') }}:</span> {{ patient.notes }}
                </div>
            </section>
            <section class="card">
                <h2 class="mb-2 text-sm font-semibold">{{ t('patients.photo') }}</h2>
                <img v-if="patient.photo_url" :src="patient.photo_url" alt="" class="h-40 w-40 rounded-md border border-border object-cover" />
                <EmptyState v-else icon="user" :title="t('patients.no_photo')" />
            </section>
            <section class="lg:col-span-3">
                <h2 class="mb-2 text-sm font-semibold">{{ t('patients.family_members') }}</h2>
                <DataTable
                    :columns="familyColumns"
                    :rows="family"
                    :empty-title="t('patients.no_family')"
                    empty-icon="users"
                    clickable
                    :sync-url="false"
                    @row-click="(row) => router.visit(route('admin.patients.show', row.id))"
                />
            </section>
        </div>
        <DataTable
            v-else-if="activeTab === 'visits'"
            :columns="visitColumns"
            :rows="visits"
            :empty-title="t('patients.tab_empty')"
            empty-icon="calendar-check"
            :sync-url="false"
        >
            <template #cell-visit_type="{ value }">{{ value === 'follow_up' ? t('appointments.visit_follow_up') : t('appointments.visit_new') }}</template>
            <template #cell-fee="{ value }"><MoneyText :value="value" symbol /></template>
            <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
        </DataTable>
        <DataTable
            v-else-if="activeTab === 'tests'"
            :columns="testColumns"
            :rows="ordered_tests"
            :empty-title="t('patients.tab_empty')"
            empty-icon="vial"
            :sync-url="false"
            clickable
            @row-click="(row) => router.visit(route('admin.orders.show', row.order_id))"
        >
            <template #cell-price="{ value }"><MoneyText :value="value" symbol /></template>
            <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
        </DataTable>
        <DataTable
            v-else-if="activeTab === 'billing'"
            :columns="invoiceColumns"
            :rows="invoices"
            :empty-title="t('patients.tab_empty')"
            empty-icon="receipt"
            :sync-url="false"
            clickable
            @row-click="(row) => router.visit(route('admin.orders.show', row.order_id))"
        >
            <template #cell-total="{ value }"><MoneyText :value="value" symbol /></template>
            <template #cell-paid="{ value }"><MoneyText :value="value" symbol /></template>
            <template #cell-due="{ value }"><MoneyText :value="value" symbol /></template>
            <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
        </DataTable>
        <div v-else class="card">
            <EmptyState icon="clock-rotate-left" :title="t('patients.tab_empty')" />
        </div>

        <PatientFormSlideOver v-model:open="editing" :patient="patient" :blood-groups="bloodGroups" @saved="router.reload()" />
    </div>
</template>
