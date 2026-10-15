<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

defineProps({ departments: { type: Array, required: true } });

const { t } = useI18n();
const open = ref(false);
const editing = ref(null);
const empty = () => ({ code: '', name: '', name_bn: '', category: 'pathology', report_style: 'table', sort_order: 0, is_active: true });
const form = useForm(empty());

const categories = ['pathology', 'imaging', 'cardiology', 'other'].map((value) => ({ value, label: t(`catalog.categories.${value}`) }));
const styles = ['table', 'narrative'].map((value) => ({ value, label: t(`catalog.styles.${value}`) }));
const columns = [
    { key: 'code', label: t('fields.code'), type: 'id' },
    { key: 'name', label: t('fields.name'), type: 'text', grow: true },
    { key: 'category', label: t('catalog.category'), type: 'text' },
    { key: 'report_style', label: t('catalog.report_style'), type: 'text' },
    { key: 'tests_count', label: t('nav.tests'), type: 'number' },
    { key: 'is_active', label: t('common.active'), type: 'bool' },
];

function edit(row = null) {
    editing.value = row;
    form.clearErrors();
    Object.assign(form, row ? { ...empty(), ...row } : empty());
    open.value = true;
}

function submit() {
    const options = { preserveScroll: true, onError: focusFirstError, onSuccess: () => (open.value = false) };
    editing.value ? form.put(route('admin.departments.update', editing.value.id), options) : form.post(route('admin.departments.store'), options);
}
</script>

<template>
    <div>
        <Head :title="t('nav.departments')" />
        <PageHeader :title="t('nav.departments')" :description="t('catalog.departments_description')">
            <template #actions><UiButton variant="primary" icon="plus" @click="edit()">{{ t('catalog.new_department') }}</UiButton></template>
        </PageHeader>
        <DataTable :columns="columns" :rows="departments" :sync-url="false" clickable @row-click="edit">
            <template #cell-category="{ value }">{{ t(`catalog.categories.${value}`) }}</template>
            <template #cell-report_style="{ value }">{{ t(`catalog.styles.${value}`) }}</template>
        </DataTable>
        <SlideOver v-model:open="open" :title="editing ? t('catalog.edit_department') : t('catalog.new_department')" width="sm">
            <form id="department-form" class="grid grid-cols-2 gap-x-4 gap-y-3" novalidate @submit.prevent="submit">
                <FormField id="code" :label="t('fields.code')" :error="form.errors.code" required>
                    <TextInput id="code" v-model="form.code" :invalid="!!form.errors.code" />
                </FormField>
                <FormField id="sort_order" :label="t('catalog.sort_order')" optional>
                    <TextInput id="sort_order" v-model="form.sort_order" inputmode="numeric" align="right" />
                </FormField>
                <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required span>
                    <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
                </FormField>
                <FormField id="name_bn" :label="t('fields.name_bn')" optional span>
                    <TextInput id="name_bn" v-model="form.name_bn" lang="bn" />
                </FormField>
                <FormField id="category" :label="t('catalog.category')" required>
                    <SelectInput id="category" v-model="form.category" :options="categories" />
                </FormField>
                <FormField id="report_style" :label="t('catalog.report_style')" required>
                    <SelectInput id="report_style" v-model="form.report_style" :options="styles" />
                </FormField>
                <div class="col-span-full"><SwitchField v-model="form.is_active" :label="t('common.active')" /></div>
            </form>
            <template #footer>
                <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="department-form" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
