<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import DateText from '@/components/ui-kit/DateText.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import MobileInput from '@/components/ui-kit/MobileInput.vue';
import MultiSelect from '@/components/ui-kit/MultiSelect.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    staff: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    roles: { type: Array, required: true },
    branches: { type: Array, required: true },
});

const { t } = useI18n();
const open = ref(false);
const editing = ref(null);

const empty = () => ({ name: '', designation: '', username: '', email: '', mobile: '', password: '', role: props.roles[0] ?? null, branch_ids: props.branches.length === 1 ? [props.branches[0].id] : [], is_active: true, locale: 'en' });
const form = useForm(empty());

const roleOptions = props.roles.map((role) => ({ value: role, label: t(`roles.${role}`) }));
const branchOptions = props.branches.map((b) => ({ value: b.id, label: `${b.code} - ${b.name}` }));

const columns = [
    { key: 'name', label: t('fields.name'), type: 'text', grow: true },
    { key: 'username', label: t('fields.username'), type: 'id' },
    { key: 'role', label: t('staff.role'), type: 'text' },
    { key: 'branches', label: t('nav.branches'), type: 'id' },
    { key: 'last_login_at', label: t('staff.last_login'), type: 'datetime' },
    { key: 'status', label: t('common.status'), type: 'status' },
];

function edit(row = null) {
    editing.value = row;
    form.clearErrors();
    Object.assign(form, row ? { ...empty(), ...row, password: '', mobile: row.mobile ?? '' } : empty());
    open.value = true;
}

function submit() {
    const options = { preserveScroll: true, onError: focusFirstError, onSuccess: () => (open.value = false) };
    editing.value ? form.put(route('admin.staff.update', editing.value.id), options) : form.post(route('admin.staff.store'), options);
}
</script>

<template>
    <div>
        <Head :title="t('nav.staff')" />
        <PageHeader :title="t('nav.staff')" :description="t('staff.description')">
            <template #actions>
                <UiButton variant="primary" icon="user-plus" @click="edit()">{{ t('staff.new_staff') }}</UiButton>
            </template>
        </PageHeader>
        <FilterBar v-slot="{ form: filter }" :filters="filters" :search-placeholder="t('staff.search')">
            <SelectInput v-model="filter.role" class="!w-44" :placeholder="t('staff.all_roles')" :options="roleOptions" :aria-label="t('staff.role')" />
        </FilterBar>
        <DataTable :columns="columns" :rows="staff" :filtered="!!(filters.q || filters.role)" clickable @row-click="edit" @clear-filters="router.get(route('admin.staff.index'))">
            <template #cell-name="{ row }">
                <span class="block truncate font-medium">{{ row.name }}</span>
                <span v-if="row.designation" class="block truncate text-xs text-muted">{{ row.designation }}</span>
            </template>
            <template #cell-role="{ value }">{{ value ? t(`roles.${value}`) : '' }}</template>
            <template #cell-last_login_at="{ value }"><DateText :value="value" relative /></template>
        </DataTable>

        <SlideOver v-model:open="open" :title="editing ? t('staff.edit_staff') : t('staff.new_staff')">
            <form id="staff-form" class="grid grid-cols-2 gap-x-4 gap-y-3" novalidate @submit.prevent="submit">
                <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required>
                    <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
                </FormField>
                <FormField id="designation" :label="t('staff.designation')" :error="form.errors.designation" optional>
                    <TextInput id="designation" v-model="form.designation" />
                </FormField>
                <FormField id="username" :label="t('fields.username')" :error="form.errors.username" :helper="t('staff.login_helper')">
                    <TextInput id="username" v-model="form.username" autocomplete="off" :invalid="!!form.errors.username" />
                </FormField>
                <FormField id="email" :label="t('fields.email')" :error="form.errors.email" optional>
                    <TextInput id="email" v-model="form.email" type="email" :invalid="!!form.errors.email" />
                </FormField>
                <FormField id="mobile" :label="t('fields.mobile')" :error="form.errors.mobile ?? form.errors.mobile_normalized" optional>
                    <MobileInput id="mobile" v-model="form.mobile" :invalid="!!(form.errors.mobile ?? form.errors.mobile_normalized)" />
                </FormField>
                <FormField id="password" :label="t('fields.password')" :error="form.errors.password" :helper="editing ? t('staff.password_keep') : null" :required="!editing">
                    <TextInput id="password" v-model="form.password" type="password" autocomplete="new-password" :invalid="!!form.errors.password" />
                </FormField>
                <FormField id="role" :label="t('staff.role')" :error="form.errors.role" required>
                    <SelectInput id="role" v-model="form.role" :options="roleOptions" />
                </FormField>
                <FormField id="locale" :label="t('common.language')" optional>
                    <SelectInput id="locale" v-model="form.locale" :options="[{ value: 'en', label: 'English' }, { value: 'bn', label: 'বাংলা' }]" />
                </FormField>
                <FormField id="branch_ids" :label="t('nav.branches')" :error="form.errors.branch_ids ?? form.errors['branch_ids.0']" required span>
                    <MultiSelect id="branch_ids" v-model="form.branch_ids" :options="branchOptions" :invalid="!!form.errors.branch_ids" />
                </FormField>
                <div class="col-span-full"><SwitchField v-model="form.is_active" :label="t('staff.active_account')" /></div>
            </form>
            <template #footer>
                <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="staff-form" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
