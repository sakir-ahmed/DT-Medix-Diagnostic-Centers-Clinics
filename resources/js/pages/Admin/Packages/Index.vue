<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import MoneyInput from '@/components/ui-kit/MoneyInput.vue';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import MultiSelect from '@/components/ui-kit/MultiSelect.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { usePermissions } from '@/composables/usePermissions';
import { formatMoney } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    packages: { type: Array, required: true },
    tests: { type: Array, required: true },
});

const { t } = useI18n();
const { can } = usePermissions();
const open = ref(false);
const editing = ref(null);
const empty = () => ({ code: '', name: '', name_bn: '', price: '', description: '', is_active: true, test_ids: [] });
const form = useForm(empty());

const testsTotal = computed(() => props.tests.filter((test) => form.test_ids.includes(test.value)).reduce((sum, test) => sum + Number(test.price), 0));
const saving = computed(() => testsTotal.value - Number(form.price || 0));

const columns = [
    { key: 'code', label: t('fields.code'), type: 'id' },
    { key: 'name', label: t('fields.name'), type: 'text', grow: true },
    { key: 'tests_total', label: t('catalog.tests_total'), type: 'money' },
    { key: 'price', label: t('catalog.package_price'), type: 'money' },
    { key: 'is_active', label: t('common.active'), type: 'bool' },
];

function edit(row = null) {
    if (!can('catalog.manage')) {
        return;
    }
    editing.value = row;
    form.clearErrors();
    Object.assign(form, row ? { ...empty(), ...row, test_ids: [...row.test_ids] } : empty());
    open.value = true;
}

function submit() {
    const options = { preserveScroll: true, onError: focusFirstError, onSuccess: () => (open.value = false) };
    editing.value ? form.put(route('admin.packages.update', editing.value.id), options) : form.post(route('admin.packages.store'), options);
}
</script>

<template>
    <div>
        <Head :title="t('nav.packages')" />
        <PageHeader :title="t('nav.packages')" :description="t('catalog.packages_description')">
            <template #actions><UiButton v-if="can('catalog.manage')" variant="primary" icon="plus" @click="edit()">{{ t('catalog.new_package') }}</UiButton></template>
        </PageHeader>
        <DataTable :columns="columns" :rows="packages" :sync-url="false" empty-icon="boxes-stacked" :empty-title="t('catalog.no_packages')" :clickable="can('catalog.manage')" @row-click="edit">
            <template #cell-name="{ row }">
                <span class="block truncate font-medium">{{ row.name }}</span>
                <span class="block truncate text-xs text-muted">{{ row.tests.map((test) => test.code).join(', ') }}</span>
            </template>
        </DataTable>
        <SlideOver v-model:open="open" :title="editing ? t('catalog.edit_package') : t('catalog.new_package')">
            <form id="package-form" class="grid grid-cols-2 gap-x-4 gap-y-3" novalidate @submit.prevent="submit">
                <FormField id="code" :label="t('fields.code')" :error="form.errors.code" required>
                    <TextInput id="code" v-model="form.code" :invalid="!!form.errors.code" />
                </FormField>
                <FormField id="price" :label="t('catalog.package_price')" :error="form.errors.price" required>
                    <MoneyInput id="price" v-model="form.price" :invalid="!!form.errors.price" />
                </FormField>
                <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required span>
                    <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
                </FormField>
                <FormField id="name_bn" :label="t('fields.name_bn')" optional span>
                    <TextInput id="name_bn" v-model="form.name_bn" lang="bn" />
                </FormField>
                <FormField id="test_ids" :label="t('nav.tests')" :error="form.errors.test_ids" :helper="t('catalog.package_tests_helper')" required span>
                    <MultiSelect id="test_ids" v-model="form.test_ids" :options="tests" :invalid="!!form.errors.test_ids" />
                </FormField>
                <div class="col-span-full rounded-md bg-subtle p-2 text-[13px]">
                    <div class="flex justify-between"><span class="text-muted">{{ t('catalog.tests_total') }}</span><MoneyText :value="testsTotal" symbol /></div>
                    <div class="flex justify-between"><span class="text-muted">{{ t('catalog.patient_saves') }}</span><span class="tabular-nums" :class="saving < 0 ? 'text-danger' : ''">{{ formatMoney(saving) }}</span></div>
                </div>
                <FormField id="description" :label="t('fields.description')" optional span>
                    <TextArea id="description" v-model="form.description" :rows="2" />
                </FormField>
                <div class="col-span-full"><SwitchField v-model="form.is_active" :label="t('common.active')" /></div>
            </form>
            <template #footer>
                <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="package-form" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
