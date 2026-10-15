<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import MobileInput from '@/components/ui-kit/MobileInput.vue';
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

defineProps({
    referrers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const { can } = usePermissions();
const open = ref(false);
const editing = ref(null);
const empty = () => ({ type: 'doctor', code: '', name: '', organization: '', specialty: '', mobile: '', email: '', address: '', payment_method: null, payment_account: '', bank_name: '', notes: '', is_active: true });
const form = useForm(empty());

const types = ['doctor', 'agent', 'institution'].map((value) => ({ value, label: t(`referrers.types.${value}`) }));
const methods = ['cash', 'bkash', 'nagad', 'bank'].map((value) => ({ value, label: t(`payments.methods.${value}`) }));

const columns = [
    { key: 'code', label: t('fields.code'), type: 'id' },
    { key: 'name', label: t('fields.name'), type: 'text', grow: true },
    { key: 'type', label: t('fields.type'), type: 'text' },
    { key: 'mobile', label: t('fields.mobile'), type: 'id' },
    { key: 'is_active', label: t('common.active'), type: 'bool' },
];

function edit(row = null) {
    if (!can('referrers.manage')) {
        return;
    }
    editing.value = row;
    form.clearErrors();
    Object.assign(form, row ? { ...empty(), ...row, mobile: row.mobile ?? '', payment_account: row.payment_account ?? '' } : empty());
    open.value = true;
}

function submit() {
    const options = { preserveScroll: true, onError: focusFirstError, onSuccess: () => (open.value = false) };
    editing.value ? form.put(route('admin.referrers.update', editing.value.id), options) : form.post(route('admin.referrers.store'), options);
}
</script>

<template>
    <div>
        <Head :title="t('nav.referrers')" />
        <PageHeader :title="t('nav.referrers')" :description="t('referrers.description')">
            <template #actions>
                <UiButton v-if="can('referrers.manage')" variant="primary" icon="plus" @click="edit()">{{ t('referrers.new_referrer') }}</UiButton>
            </template>
        </PageHeader>
        <FilterBar v-slot="{ form: filter }" :filters="filters" :search-placeholder="t('referrers.search')">
            <SelectInput v-model="filter.type" class="!w-40" :placeholder="t('referrers.all_types')" :options="types" :aria-label="t('fields.type')" />
        </FilterBar>
        <DataTable :columns="columns" :rows="referrers" :filtered="!!(filters.q || filters.type)" empty-icon="handshake" :empty-title="t('referrers.empty')" :clickable="can('referrers.manage')" @row-click="edit" @clear-filters="router.get(route('admin.referrers.index'))">
            <template #cell-name="{ row }">
                <span class="block truncate font-medium">{{ row.name }}</span>
                <span v-if="row.organization" class="block truncate text-xs text-muted">{{ row.organization }}</span>
            </template>
            <template #cell-type="{ value }">{{ t(`referrers.types.${value}`) }}</template>
        </DataTable>
        <SlideOver v-model:open="open" :title="editing ? t('referrers.edit_referrer') : t('referrers.new_referrer')">
            <form id="referrer-form" class="grid grid-cols-2 gap-x-4 gap-y-3" novalidate @submit.prevent="submit">
                <FormField id="type" :label="t('fields.type')" :error="form.errors.type" required>
                    <SelectInput id="type" v-model="form.type" :options="types" />
                </FormField>
                <FormField id="code" :label="t('fields.code')" :error="form.errors.code" optional>
                    <TextInput id="code" v-model="form.code" :invalid="!!form.errors.code" />
                </FormField>
                <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required span>
                    <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
                </FormField>
                <FormField id="organization" :label="t('referrers.organization')" optional>
                    <TextInput id="organization" v-model="form.organization" />
                </FormField>
                <FormField id="specialty" :label="t('doctors.specialty')" optional>
                    <TextInput id="specialty" v-model="form.specialty" />
                </FormField>
                <FormField id="mobile" :label="t('fields.mobile')" :error="form.errors.mobile" optional>
                    <MobileInput id="mobile" v-model="form.mobile" :invalid="!!form.errors.mobile" />
                </FormField>
                <FormField id="email" :label="t('fields.email')" :error="form.errors.email" optional>
                    <TextInput id="email" v-model="form.email" type="email" />
                </FormField>
                <FormField id="payment_method" :label="t('referrers.payment_method')" optional>
                    <SelectInput id="payment_method" v-model="form.payment_method" :placeholder="t('common.none')" :options="methods" />
                </FormField>
                <FormField id="payment_account" :label="t('referrers.payment_account')" :helper="t('referrers.payment_account_helper')" optional>
                    <TextInput id="payment_account" v-model="form.payment_account" autocomplete="off" />
                </FormField>
                <FormField id="bank_name" :label="t('referrers.bank_name')" optional span>
                    <TextInput id="bank_name" v-model="form.bank_name" />
                </FormField>
                <FormField id="address" :label="t('fields.address')" optional span>
                    <TextArea id="address" v-model="form.address" :rows="2" />
                </FormField>
                <FormField id="notes" :label="t('fields.notes')" optional span>
                    <TextArea id="notes" v-model="form.notes" :rows="2" />
                </FormField>
                <div class="col-span-full"><SwitchField v-model="form.is_active" :label="t('common.active')" /></div>
            </form>
            <template #footer>
                <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="referrer-form" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
