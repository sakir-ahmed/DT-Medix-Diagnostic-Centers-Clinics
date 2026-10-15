<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import MoneyInput from '@/components/ui-kit/MoneyInput.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useDraft } from '@/composables/useDraft';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    test: { type: Object, default: null },
    branchPrices: { type: Object, default: () => ({}) },
    departments: { type: Array, required: true },
    templates: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    sampleTypes: { type: Array, default: () => [] },
    containers: { type: Array, default: () => [] },
    resultTypes: { type: Array, default: () => [] },
    ageUnits: { type: Array, default: () => [] },
});

const { t } = useI18n();

const blankRange = () => ({ sex: 'any', age_min: 0, age_max: null, age_unit: 'years', low: '', high: '', critical_low: '', critical_high: '', display_text: '' });
const blankParameter = () => ({ id: null, code: '', name: '', group_label: '', unit: '', result_type: 'number', choices: [], abnormal_values: [], formula: '', decimals: null, default_value: '', is_active: true, ranges: [blankRange()] });

const form = useForm({
    department_id: props.test?.department_id ?? props.departments[0]?.value ?? null,
    report_template_id: props.test?.report_template_id ?? null,
    code: props.test?.code ?? '',
    name: props.test?.name ?? '',
    name_bn: props.test?.name_bn ?? '',
    sample_type: props.test?.sample_type ?? 'blood',
    container: props.test?.container ?? null,
    price: props.test?.price ?? '',
    tat_hours: props.test?.tat_hours ?? 24,
    preparation: props.test?.preparation ?? '',
    method: props.test?.method ?? '',
    interpretation: props.test?.interpretation ?? '',
    home_collection: props.test?.home_collection ?? false,
    is_outsourced: props.test?.is_outsourced ?? false,
    vendor_name: props.test?.vendor_name ?? '',
    vendor_cost: props.test?.vendor_cost ?? '',
    vat_applicable: props.test?.vat_applicable ?? true,
    is_active: props.test?.is_active ?? true,
    parameters: (props.test?.parameters ?? []).map((p) => ({
        ...p,
        choices: p.choices ?? [],
        abnormal_values: p.abnormal_values ?? [],
        ranges: p.ranges.map((r) => ({ ...blankRange(), ...r })),
    })),
    branch_prices: Object.fromEntries(props.branches.map((b) => [b.id, props.branchPrices?.[b.id] ?? ''])),
});

const draft = useDraft(`test-${props.test?.id ?? 'new'}`, form, { t });

const options = (list, prefix) => list.map((value) => ({ value, label: t(`${prefix}.${value}`) }));
const sexOptions = ['any', 'male', 'female'].map((value) => ({ value, label: t(`catalog.sex.${value}`) }));
const title = computed(() => (props.test ? `${props.test.code} - ${props.test.name}` : t('catalog.new_test')));

function addParameter() {
    form.parameters.push(blankParameter());
}

function removeParameter(index) {
    form.parameters.splice(index, 1);
}

function move(index, offset) {
    const target = index + offset;
    if (target < 0 || target >= form.parameters.length) {
        return;
    }
    const [item] = form.parameters.splice(index, 1);
    form.parameters.splice(target, 0, item);
}

function listInput(value) {
    return String(value ?? '')
        .split(',')
        .map((v) => v.trim())
        .filter(Boolean);
}

function submit() {
    const payload = (data) => ({
        ...data,
        parameters: data.parameters.map((p) => ({
            ...p,
            ranges: p.result_type === 'number' || p.result_type === 'calculated' ? p.ranges.map((r) => ({ ...r, age_max: r.age_max === '' ? null : r.age_max })) : [],
        })),
    });
    const options = {
        preserveScroll: true,
        onError: focusFirstError,
        onSuccess: () => draft.clear(),
    };
    form.transform(payload);
    props.test ? form.put(route('admin.tests.update', props.test.id), options) : form.post(route('admin.tests.store'), options);
}

const err = (path) => form.errors[path];
</script>

<template>
    <div>
        <Head :title="title" />
        <PageHeader :title="title" :breadcrumbs="[{ label: t('nav.tests'), href: route('admin.tests.index') }, { label: test ? test.code : t('catalog.new_test') }]" />

        <form class="max-w-5xl space-y-4 pb-20" novalidate @submit.prevent="submit" @keydown.ctrl.enter.prevent="submit">
            <section class="card space-y-3">
                <h2 class="text-sm font-semibold">{{ t('catalog.test_details') }}</h2>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3 lg:grid-cols-4">
                    <FormField id="code" :label="t('fields.code')" :error="err('code')" required>
                        <TextInput id="code" v-model="form.code" :invalid="!!err('code')" />
                    </FormField>
                    <FormField id="name" :label="t('catalog.test_name')" :error="err('name')" required class="lg:col-span-2">
                        <TextInput id="name" v-model="form.name" :invalid="!!err('name')" />
                    </FormField>
                    <FormField id="department_id" :label="t('catalog.department')" :error="err('department_id')" required>
                        <SelectInput id="department_id" v-model="form.department_id" :options="departments" />
                    </FormField>
                    <FormField id="price" :label="t('catalog.price')" :error="err('price')" required>
                        <MoneyInput id="price" v-model="form.price" :invalid="!!err('price')" />
                    </FormField>
                    <FormField id="tat_hours" :label="t('catalog.tat')" :error="err('tat_hours')" required>
                        <TextInput id="tat_hours" v-model="form.tat_hours" inputmode="numeric" align="right" :suffix="t('settings.hours')" :invalid="!!err('tat_hours')" />
                    </FormField>
                    <FormField id="sample_type" :label="t('catalog.sample_type')" :error="err('sample_type')" optional>
                        <SelectInput id="sample_type" v-model="form.sample_type" :placeholder="t('common.none')" :options="options(sampleTypes, 'catalog.samples')" />
                    </FormField>
                    <FormField id="container" :label="t('catalog.container')" :error="err('container')" optional>
                        <SelectInput id="container" v-model="form.container" :placeholder="t('common.none')" :options="options(containers, 'catalog.containers')" />
                    </FormField>
                    <FormField id="name_bn" :label="t('fields.name_bn')" optional class="lg:col-span-2">
                        <TextInput id="name_bn" v-model="form.name_bn" lang="bn" />
                    </FormField>
                    <FormField id="report_template_id" :label="t('catalog.report_template')" optional class="lg:col-span-2">
                        <SelectInput id="report_template_id" v-model="form.report_template_id" :placeholder="t('catalog.department_default')" :options="templates" />
                    </FormField>
                    <FormField id="preparation" :label="t('catalog.preparation')" :helper="t('catalog.preparation_helper')" optional span>
                        <TextInput id="preparation" v-model="form.preparation" />
                    </FormField>
                    <FormField id="method" :label="t('catalog.method')" optional class="lg:col-span-2">
                        <TextInput id="method" v-model="form.method" />
                    </FormField>
                    <FormField id="interpretation" :label="t('catalog.interpretation')" optional class="lg:col-span-2">
                        <TextArea id="interpretation" v-model="form.interpretation" :rows="2" />
                    </FormField>
                </div>
                <div class="flex flex-wrap gap-4">
                    <SwitchField v-model="form.home_collection" :label="t('catalog.home_collection')" />
                    <SwitchField v-model="form.vat_applicable" :label="t('catalog.vat_applicable')" />
                    <SwitchField v-model="form.is_outsourced" :label="t('catalog.outsourced')" />
                    <SwitchField v-model="form.is_active" :label="t('common.active')" />
                </div>
                <div v-if="form.is_outsourced" class="grid grid-cols-2 gap-x-4 gap-y-3 lg:grid-cols-4">
                    <FormField id="vendor_name" :label="t('catalog.vendor')" :error="err('vendor_name')" required class="lg:col-span-2">
                        <TextInput id="vendor_name" v-model="form.vendor_name" :invalid="!!err('vendor_name')" />
                    </FormField>
                    <FormField id="vendor_cost" :label="t('catalog.vendor_cost')" :error="err('vendor_cost')" optional>
                        <MoneyInput id="vendor_cost" v-model="form.vendor_cost" />
                    </FormField>
                </div>
            </section>

            <section class="card space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold">{{ t('catalog.parameters') }}</h2>
                        <p class="text-xs text-muted">{{ t('catalog.parameters_hint') }}</p>
                    </div>
                    <UiButton icon="plus" @click="addParameter">{{ t('catalog.add_parameter') }}</UiButton>
                </div>
                <p v-if="!form.parameters.length" class="rounded-md bg-subtle p-3 text-[13px] text-muted">{{ t('catalog.no_parameters') }}</p>
                <p v-if="form.errors.error_code || err('parameters')" class="text-xs text-danger">{{ err('parameters') }}</p>

                <div v-for="(parameter, index) in form.parameters" :key="index" class="rounded-md border border-border p-3">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-muted">#{{ index + 1 }}</span>
                        <div class="flex gap-1">
                            <UiButton size="xs" variant="ghost" icon-only icon="arrow-up" :label="t('catalog.move_up')" :disabled="index === 0" @click="move(index, -1)" />
                            <UiButton size="xs" variant="ghost" icon-only icon="arrow-down" :label="t('catalog.move_down')" :disabled="index === form.parameters.length - 1" @click="move(index, 1)" />
                            <UiButton size="xs" variant="ghost-danger" icon-only icon="trash" :label="t('catalog.remove_parameter')" @click="removeParameter(index)" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-x-3 gap-y-2 lg:grid-cols-6">
                        <FormField :id="`parameters.${index}.code`" :label="t('catalog.parameter_code')" :error="err(`parameters.${index}.code`)" required>
                            <TextInput :id="`parameters.${index}.code`" v-model="parameter.code" :invalid="!!err(`parameters.${index}.code`)" />
                        </FormField>
                        <FormField :id="`parameters.${index}.name`" :label="t('results.parameter')" :error="err(`parameters.${index}.name`)" required class="lg:col-span-2">
                            <TextInput :id="`parameters.${index}.name`" v-model="parameter.name" :invalid="!!err(`parameters.${index}.name`)" />
                        </FormField>
                        <FormField :id="`parameters.${index}.unit`" :label="t('results.unit')" optional>
                            <TextInput :id="`parameters.${index}.unit`" v-model="parameter.unit" />
                        </FormField>
                        <FormField :id="`parameters.${index}.result_type`" :label="t('catalog.result_type')" required>
                            <SelectInput :id="`parameters.${index}.result_type`" v-model="parameter.result_type" :options="options(resultTypes, 'catalog.result_types')" />
                        </FormField>
                        <FormField :id="`parameters.${index}.group_label`" :label="t('catalog.group_label')" optional>
                            <TextInput :id="`parameters.${index}.group_label`" v-model="parameter.group_label" />
                        </FormField>
                        <FormField v-if="parameter.result_type === 'choice'" :id="`parameters.${index}.choices`" :label="t('catalog.choices')" :helper="t('catalog.comma_separated')" :error="err(`parameters.${index}.choices`)" required class="lg:col-span-3">
                            <TextInput :id="`parameters.${index}.choices`" :model-value="parameter.choices.join(', ')" @update:model-value="(v) => (parameter.choices = listInput(v))" />
                        </FormField>
                        <FormField v-if="parameter.result_type === 'choice' || parameter.result_type === 'text'" :id="`parameters.${index}.abnormal_values`" :label="t('catalog.abnormal_values')" :helper="t('catalog.comma_separated')" optional class="lg:col-span-3">
                            <TextInput :id="`parameters.${index}.abnormal_values`" :model-value="parameter.abnormal_values.join(', ')" @update:model-value="(v) => (parameter.abnormal_values = listInput(v))" />
                        </FormField>
                        <FormField v-if="parameter.result_type === 'calculated'" :id="`parameters.${index}.formula`" :label="t('catalog.formula')" :helper="t('catalog.formula_helper')" :error="err(`parameters.${index}.formula`)" required class="lg:col-span-4">
                            <TextInput :id="`parameters.${index}.formula`" v-model="parameter.formula" :placeholder="'{TC} - {HDL} - {TG} / 5'" />
                        </FormField>
                        <FormField v-if="parameter.result_type === 'number' || parameter.result_type === 'calculated'" :id="`parameters.${index}.decimals`" :label="t('catalog.decimals')" optional>
                            <TextInput :id="`parameters.${index}.decimals`" v-model="parameter.decimals" inputmode="numeric" align="right" />
                        </FormField>
                    </div>

                    <div v-if="parameter.result_type === 'number' || parameter.result_type === 'calculated'" class="mt-3 overflow-x-auto">
                        <table class="w-full text-[13px]">
                            <thead>
                                <tr class="text-xs text-muted">
                                    <th class="px-1 py-1 text-left font-semibold">{{ t('catalog.sex_label') }}</th>
                                    <th class="px-1 py-1 text-right font-semibold">{{ t('catalog.age_from') }}</th>
                                    <th class="px-1 py-1 text-right font-semibold">{{ t('catalog.age_to') }}</th>
                                    <th class="px-1 py-1 text-left font-semibold">{{ t('catalog.age_unit') }}</th>
                                    <th class="px-1 py-1 text-right font-semibold">{{ t('catalog.low') }}</th>
                                    <th class="px-1 py-1 text-right font-semibold">{{ t('catalog.high') }}</th>
                                    <th class="px-1 py-1 text-right font-semibold">{{ t('catalog.critical_low') }}</th>
                                    <th class="px-1 py-1 text-right font-semibold">{{ t('catalog.critical_high') }}</th>
                                    <th class="px-1 py-1 text-left font-semibold">{{ t('catalog.display_text') }}</th>
                                    <th class="w-8" />
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(range, rIndex) in parameter.ranges" :key="rIndex">
                                    <td class="px-1 py-0.5"><SelectInput v-model="range.sex" :options="sexOptions" :aria-label="t('catalog.sex_label')" class="!w-24" /></td>
                                    <td class="px-1 py-0.5"><TextInput :id="`parameters.${index}.ranges.${rIndex}.age_min`" v-model="range.age_min" inputmode="numeric" align="right" class="!w-16" :invalid="!!err(`parameters.${index}.ranges.${rIndex}.age_min`)" :aria-label="t('catalog.age_from')" /></td>
                                    <td class="px-1 py-0.5"><TextInput :id="`parameters.${index}.ranges.${rIndex}.age_max`" v-model="range.age_max" inputmode="numeric" align="right" class="!w-16" :placeholder="t('catalog.no_limit')" :invalid="!!err(`parameters.${index}.ranges.${rIndex}.age_max`)" :aria-label="t('catalog.age_to')" /></td>
                                    <td class="px-1 py-0.5"><SelectInput v-model="range.age_unit" :options="options(ageUnits, 'catalog.age_units')" class="!w-24" :aria-label="t('catalog.age_unit')" /></td>
                                    <td v-for="key in ['low', 'high', 'critical_low', 'critical_high']" :key="key" class="px-1 py-0.5">
                                        <TextInput :id="`parameters.${index}.ranges.${rIndex}.${key}`" v-model="range[key]" inputmode="decimal" align="right" class="!w-20" :invalid="!!err(`parameters.${index}.ranges.${rIndex}.${key}`)" :aria-label="t(`catalog.${key}`)" />
                                    </td>
                                    <td class="px-1 py-0.5"><TextInput v-model="range.display_text" class="!w-28" :aria-label="t('catalog.display_text')" /></td>
                                    <td class="px-1 py-0.5">
                                        <UiButton size="xs" variant="ghost-danger" icon-only icon="xmark" :label="t('catalog.remove_range')" @click="parameter.ranges.splice(rIndex, 1)" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <UiButton size="xs" variant="ghost" icon="plus" class="mt-1" @click="parameter.ranges.push(blankRange())">{{ t('catalog.add_range') }}</UiButton>
                    </div>
                </div>
            </section>

            <section v-if="branches.length > 1" class="card space-y-3">
                <h2 class="text-sm font-semibold">{{ t('catalog.branch_prices') }}</h2>
                <p class="text-xs text-muted">{{ t('catalog.branch_prices_hint') }}</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3 lg:grid-cols-4">
                    <FormField v-for="branch in branches" :id="`branch_prices.${branch.id}`" :key="branch.id" :label="`${branch.code} - ${branch.name}`" :error="err(`branch_prices.${branch.id}`)" optional>
                        <MoneyInput :id="`branch_prices.${branch.id}`" v-model="form.branch_prices[branch.id]" />
                    </FormField>
                </div>
            </section>

            <div class="sticky bottom-0 z-10 -mx-1 flex items-center justify-end gap-2 border-t border-border bg-background px-1 py-2">
                <span class="mr-auto flex items-center gap-1 text-xs text-muted"><AppIcon name="keyboard" size="sm" /> {{ t('catalog.save_shortcut') }}</span>
                <UiButton :href="route('admin.tests.index')">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" variant="primary" icon="floppy-disk" :loading="form.processing">{{ t('catalog.save_test') }}</UiButton>
            </div>
        </form>
    </div>
</template>
