<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import MobileInput from '@/components/ui-kit/MobileInput.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import RowActions from '@/components/ui-kit/RowActions.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useConfirm } from '@/composables/useConfirm';
import { post } from '@/lib/http';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    branches: { type: Array, required: true },
});

const { t } = useI18n();
const confirm = useConfirm();
const open = ref(false);
const editing = ref(null);
const form = useForm({ code: '', name: '', name_bn: '', address: '', phone: '', email: '', license_no: '' });

const columns = [
    { key: 'code', label: t('fields.code'), type: 'id' },
    { key: 'name', label: t('fields.name'), type: 'text', grow: true },
    { key: 'phone', label: t('fields.phone'), type: 'id' },
    { key: 'users_count', label: t('branches.staff_count'), type: 'number' },
    { key: 'status', label: t('common.status'), type: 'status' },
    { key: 'actions', label: '', type: 'actions' },
];


function edit(branch = null) {
    editing.value = branch;
    form.clearErrors();
    Object.assign(form, branch ? { ...branch, phone: branch.phone ? String(branch.phone).replace(/^88/, '') : '' } : { code: '', name: '', name_bn: '', address: '', phone: '', email: '', license_no: '' });
    open.value = true;
}

function submit() {
    const options = { preserveScroll: true, onError: focusFirstError, onSuccess: () => (open.value = false) };
    editing.value ? form.put(route('admin.branches.update', editing.value.id), options) : form.post(route('admin.branches.store'), options);
}

function toggle(branch) {
    if (branch.is_active) {
        confirm({
            variant: 'danger',
            level: 'typed',
            typedWord: branch.code,
            title: t('branches.deactivate_title', { name: branch.name }),
            message: t('branches.deactivate_message'),
            confirmText: t('branches.deactivate'),
            action: () => post(route('admin.branches.toggle', branch.id), { active: false }),
        });
    } else {
        post(route('admin.branches.toggle', branch.id), { active: true }).catch(() => {});
    }
}
</script>

<template>
    <div>
        <Head :title="t('nav.branches')" />
        <PageHeader :title="t('nav.branches')" :description="t('branches.description')">
            <template #actions>
                <UiButton variant="primary" icon="plus" @click="edit()">{{ t('branches.new_branch') }}</UiButton>
            </template>
        </PageHeader>
        <DataTable :columns="columns" :rows="branches" :sync-url="false" clickable @row-click="edit">
            <template #cell-name="{ row }">
                <span class="block truncate font-medium">{{ row.name }}</span>
                <span v-if="row.address" class="block truncate text-xs text-muted">{{ row.address }}</span>
            </template>
            <template #row-actions="{ row }">
                <RowActions
                    :items="[
                        { label: t('common.edit'), icon: 'pen-to-square', onSelect: () => edit(row) },
                        { separator: true },
                        row.is_active
                            ? { label: t('branches.deactivate'), icon: 'ban', danger: true, onSelect: () => toggle(row) }
                            : { label: t('branches.activate'), icon: 'play', onSelect: () => toggle(row) },
                    ]"
                />
            </template>
        </DataTable>

        <SlideOver v-model:open="open" :title="editing ? t('branches.edit_branch') : t('branches.new_branch')">
            <form id="branch-form" class="grid grid-cols-2 gap-x-4 gap-y-3" novalidate @submit.prevent="submit">
                <FormField id="code" :label="t('fields.code')" :error="form.errors.code" :helper="t('branches.code_helper')" required>
                    <TextInput id="code" v-model="form.code" maxlength="10" :invalid="!!form.errors.code" />
                </FormField>
                <FormField id="license_no" :label="t('branches.license_no')" :error="form.errors.license_no" optional>
                    <TextInput id="license_no" v-model="form.license_no" />
                </FormField>
                <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required span>
                    <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
                </FormField>
                <FormField id="name_bn" :label="t('fields.name_bn')" :error="form.errors.name_bn" optional span>
                    <TextInput id="name_bn" v-model="form.name_bn" lang="bn" />
                </FormField>
                <FormField id="phone" :label="t('fields.phone')" :error="form.errors.phone" optional>
                    <MobileInput id="phone" v-model="form.phone" :invalid="!!form.errors.phone" />
                </FormField>
                <FormField id="email" :label="t('fields.email')" :error="form.errors.email" optional>
                    <TextInput id="email" v-model="form.email" type="email" :invalid="!!form.errors.email" />
                </FormField>
                <FormField id="address" :label="t('fields.address')" :error="form.errors.address" optional span>
                    <TextArea id="address" v-model="form.address" :rows="2" />
                </FormField>
            </form>
            <template #footer>
                <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="branch-form" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
