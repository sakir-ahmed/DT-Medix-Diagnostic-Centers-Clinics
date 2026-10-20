<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PatientFormSlideOver from '@/components/patients/PatientFormSlideOver.vue';
import ComboBox from '@/components/ui-kit/ComboBox.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import ModalDialog from '@/components/ui-kit/ModalDialog.vue';
import MoneyInput from '@/components/ui-kit/MoneyInput.vue';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import PatientBanner from '@/components/ui-kit/PatientBanner.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useShortcuts } from '@/composables/useShortcuts';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    doctors: { type: Array, default: () => [] },
    referrers: { type: Array, default: () => [] },
    discount_limit_percent: { type: Number, default: 10 },
    vat_enabled: { type: Boolean, default: false },
    vat_percent: { type: String, default: '0' },
    service_charge_enabled: { type: Boolean, default: false },
    service_charge_percent: { type: String, default: '0' },
    bloodGroups: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { can } = usePermissions();

const patientSearch = ref(null);
const testSearch = ref(null);
const payAmount = ref(null);
const patient = ref(null);
const lines = ref([]);
const cashLines = ref(['']);
const tendered = ref('');
const creating = ref(false);
const helpOpen = ref(false);

const form = useForm({
    patient_id: null,
    referrer_id: null,
    urgency: 'routine',
    notes: '',
    discount_type: 'percent',
    discount_value: '',
    discount_category: 'standard',
    discount_reason: '',
    approval_login: '',
    approval_pin: '',
});

useShortcuts({
    '/': () => patientSearch.value?.focus(),
    F2: () => can('patients.create') && (creating.value = true),
    F3: () => testSearch.value?.focus(),
    F4: () => payAmount.value?.focus(),
    F9: () => submit(),
    Escape: () => {
        helpOpen.value = false;
    },
});

function toCents(value) {
    const text = String(value ?? '0').trim();
    if (text === '' || text === '.') {
        return 0;
    }
    const negative = text.startsWith('-');
    const [whole, fraction = ''] = text.replace('-', '').split('.');
    const cents = Number(whole || '0') * 100 + Number((fraction + '00').slice(0, 2));

    return negative ? -cents : cents;
}

function fromCents(cents) {
    const sign = cents < 0 ? '-' : '';
    const absolute = Math.abs(Math.round(cents));

    return `${sign}${Math.floor(absolute / 100)}.${String(absolute % 100).padStart(2, '0')}`;
}

function percentOf(cents, percent) {
    return Math.round((cents * Number(percent || 0)) / 100);
}

function shares(totalCents, weights) {
    const weightSum = weights.reduce((sum, weight) => sum + weight, 0);
    let remaining = totalCents;

    return weights.map((weight, index) => {
        const share = index === weights.length - 1 || weightSum === 0 ? remaining : Math.min(Math.round((weight * totalCents) / weightSum), remaining);
        remaining -= share;

        return share;
    });
}

const items = computed(() => {
    const covered = new Set();
    const result = [];

    for (const line of lines.value) {
        if (line.type === 'package') {
            const tests = line.tests.filter((test) => !covered.has(test.id));
            if (tests.length === 0) {
                continue;
            }
            const parts = shares(toCents(line.price), tests.map((test) => toCents(test.price)));
            tests.forEach((test, index) => {
                covered.add(test.id);
                result.push({ price: parts[index], vat: test.vat_applicable !== false });
            });
            continue;
        }
        if (covered.has(line.id)) {
            continue;
        }
        covered.add(line.id);
        result.push({ price: toCents(line.price), vat: line.vat_applicable !== false });
    }

    return result;
});

const subtotalCents = computed(() => items.value.reduce((sum, item) => sum + item.price, 0));

const discountCents = computed(() => {
    if (form.discount_category === 'free') {
        return subtotalCents.value;
    }
    const value = toCents(form.discount_value);
    if (value <= 0 || subtotalCents.value <= 0) {
        return 0;
    }
    const amount = form.discount_type === 'fixed' ? Math.min(value, subtotalCents.value) : percentOf(subtotalCents.value, Math.min(Number(form.discount_value), 100));

    return Math.min(amount, subtotalCents.value);
});

const discountPercent = computed(() => (subtotalCents.value === 0 ? 0 : (discountCents.value * 100) / subtotalCents.value));
const held = computed(() => discountCents.value > 0 && discountPercent.value > props.discount_limit_percent && !can('discounts.approve') && form.approval_login.trim() === '');
const appliedDiscount = computed(() => (held.value ? 0 : discountCents.value));

const netItems = computed(() => {
    let remaining = appliedDiscount.value;
    const last = items.value.length - 1;

    return items.value.map((item, index) => {
        const share = index === last || subtotalCents.value === 0 ? remaining : Math.min(Math.round((item.price * appliedDiscount.value) / subtotalCents.value), remaining);
        remaining -= share;

        return { net: item.price - share, vat: item.vat };
    });
});

const vatCents = computed(() => (props.vat_enabled ? percentOf(netItems.value.filter((item) => item.vat).reduce((sum, item) => sum + item.net, 0), props.vat_percent) : 0));
const serviceCents = computed(() => (props.service_charge_enabled ? percentOf(subtotalCents.value - appliedDiscount.value, props.service_charge_percent) : 0));
const totalCents = computed(() => subtotalCents.value - appliedDiscount.value + vatCents.value + serviceCents.value);
const paidCents = computed(() => cashLines.value.reduce((sum, amount) => sum + Math.max(0, toCents(amount)), 0));
const dueCents = computed(() => Math.max(0, totalCents.value - paidCents.value));
const changeCents = computed(() => Math.max(0, toCents(tendered.value) - paidCents.value));

const summary = computed(() => [
    ['billing.subtotal', subtotalCents.value],
    ['billing.discount', appliedDiscount.value],
    ...(props.vat_enabled ? [['billing.vat', vatCents.value]] : []),
    ...(props.service_charge_enabled ? [['billing.service_charge', serviceCents.value]] : []),
    ['billing.total', totalCents.value],
    ['billing.paid', paidCents.value],
    ['billing.due', dueCents.value],
]);

async function searchJson(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    if (!response.ok) {
        return [];
    }
    const json = await response.json();

    return json.results ?? [];
}

function searchPatients(query) {
    return searchJson(`${route('admin.patients.lookup')}?q=${encodeURIComponent(query)}`);
}

function searchTests(query) {
    return searchJson(`${route('admin.tests.lookup')}?q=${encodeURIComponent(query)}`);
}

async function selectPatient(option) {
    if (!option) {
        patient.value = null;
        form.patient_id = null;
        return;
    }
    const response = await fetch(route('admin.patients.summary', option.value), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    if (!response.ok) {
        return;
    }
    const json = await response.json();
    patient.value = json.patient;
    form.patient_id = json.patient.id;
}

function addLine(option) {
    if (!option) {
        return;
    }
    if (option.type === 'package') {
        const tests = (option.tests ?? []).filter((test) => !lines.value.some((line) => line.type === 'test' && line.id === test.id) && !lines.value.some((line) => line.type === 'package' && line.tests.some((existing) => existing.id === test.id)));
        if (tests.length === 0 || lines.value.some((line) => line.key === option.value)) {
            return;
        }
        lines.value = lines.value.filter((line) => !(line.type === 'test' && tests.some((test) => test.id === line.id)));
        lines.value.push({
            key: option.value,
            type: 'package',
            id: option.id,
            code: option.code,
            label: option.label,
            price: option.price,
            tests,
        });
        return;
    }
    const covered = lines.value.some((line) => (line.type === 'test' && line.id === option.id) || (line.type === 'package' && line.tests.some((test) => test.id === option.id)));
    if (covered) {
        return;
    }
    lines.value.push({
        key: option.value,
        type: 'test',
        id: option.id,
        code: option.code,
        label: option.label,
        price: option.price,
        vat_applicable: option.vat_applicable,
        tests: [],
    });
}

function removeLine(key) {
    lines.value = lines.value.filter((line) => line.key !== key);
}

function setPayAmount(element) {
    payAmount.value = element;
}

function fillDue() {
    cashLines.value = [fromCents(dueCents.value + paidCents.value)];
}

function submit() {
    form.transform((data) => ({
        ...data,
        test_ids: lines.value.filter((line) => line.type === 'test').map((line) => line.id),
        package_ids: lines.value.filter((line) => line.type === 'package').map((line) => line.id),
        discount_type: data.discount_category === 'free' ? 'percent' : data.discount_value ? data.discount_type : null,
        discount_value: data.discount_category === 'free' ? '100' : data.discount_value || null,
        payments: cashLines.value.filter((amount) => toCents(amount) > 0).map((amount) => ({ amount, method: 'cash' })),
    })).post(route('admin.reception.store'), {
        onSuccess: (page) => {
            const id = page.props.flash?.print_order_id;
            if (id) {
                window.open(route('admin.orders.print', id), '_blank', 'noopener');
            }
            patient.value = null;
            lines.value = [];
            cashLines.value = [''];
            tendered.value = '';
            form.reset();
        },
    });
}

async function patientCreated(id) {
    if (!id) {
        return;
    }
    await selectPatient({ value: id });
}
</script>

<template>
    <div>
        <Head :title="t('nav.reception')" />
        <PageHeader :title="t('nav.reception')" :description="t('billing.description')">
            <template #actions>
                <UiButton icon="keyboard" @click="helpOpen = true">{{ t('billing.shortcuts') }}</UiButton>
                <UiButton v-if="can('patients.create')" icon="user-plus" @click="creating = true">{{ t('patients.new_patient') }}</UiButton>
            </template>
        </PageHeader>

        <form class="grid items-start gap-3 lg:grid-cols-5" @submit.prevent="submit">
            <div class="space-y-3 lg:col-span-3">
                <section class="card space-y-2">
                    <FormField id="patient-search" :label="t('billing.patient')" required :error="form.errors.patient_id">
                        <template #default="{ id, invalid }">
                            <ComboBox :id="id" ref="patientSearch" :search="searchPatients" clear-on-select :invalid="invalid" :placeholder="t('patients.search_placeholder')" @select="selectPatient" />
                        </template>
                    </FormField>
                    <PatientBanner v-if="patient" :patient="patient" />
                </section>

                <section class="card space-y-2">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <FormField id="referrer" :label="t('nav.referrers')" optional>
                            <template #default="{ id }">
                                <SelectInput :id="id" v-model="form.referrer_id" :options="referrers" :placeholder="t('common.optional')" />
                            </template>
                        </FormField>
                        <FormField id="urgency" :label="t('billing.urgency')">
                            <template #default="{ id }">
                                <SelectInput :id="id" v-model="form.urgency" :options="[{ value: 'routine', label: t('statuses.routine') }, { value: 'urgent', label: t('statuses.urgent') }]" />
                            </template>
                        </FormField>
                    </div>
                    <FormField id="test-search" :label="t('billing.add_test')" :error="form.errors.test_ids">
                        <template #default="{ id, invalid }">
                            <ComboBox :id="id" ref="testSearch" :search="searchTests" clear-on-select :invalid="invalid" :placeholder="t('billing.add_test')" @select="addLine" />
                        </template>
                    </FormField>
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-border text-left text-xs text-muted">
                                <th class="py-1 pr-2 font-medium">{{ t('billing.code') }}</th>
                                <th class="py-1 pr-2 font-medium">{{ t('billing.test') }}</th>
                                <th class="py-1 text-right font-medium">{{ t('billing.price') }}</th>
                                <th class="w-8" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in lines" :key="line.key" class="border-b border-border/60">
                                <td class="py-1 pr-2 font-mono text-xs">{{ line.code }}</td>
                                <td class="py-1 pr-2">
                                    {{ line.label }}
                                    <span v-if="line.type === 'package'" class="block text-xs text-muted">{{ line.tests.map((test) => test.code).join(', ') }}</span>
                                </td>
                                <td class="py-1 text-right"><MoneyText :value="line.price" symbol /></td>
                                <td class="py-1 text-right">
                                    <UiButton icon="xmark" icon-only size="xs" variant="ghost" :label="t('common.remove')" @click="removeLine(line.key)" />
                                </td>
                            </tr>
                            <tr v-if="lines.length === 0">
                                <td colspan="4" class="py-3 text-muted">{{ t('billing.no_lines') }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <FormField id="order-notes" :label="t('fields.notes')" optional>
                        <template #default="{ id }">
                            <TextArea :id="id" v-model="form.notes" rows="2" />
                        </template>
                    </FormField>
                </section>
            </div>

            <aside class="card space-y-3 lg:col-span-2">
                <h2 class="text-sm font-semibold">{{ t('billing.invoice') }}</h2>
                <dl class="space-y-1 text-[13px]">
                    <div v-for="[label, cents] in summary" :key="label" class="flex items-center justify-between gap-3">
                        <dt class="text-muted">{{ t(label) }}</dt>
                        <dd><MoneyText :value="fromCents(cents)" symbol /></dd>
                    </div>
                </dl>
                <p v-if="held" class="rounded-md bg-warning/15 px-2 py-1 text-xs">{{ t('billing.held_for_approval') }}</p>

                <div class="grid gap-2 sm:grid-cols-2">
                    <FormField id="discount-category" :label="t('billing.category')">
                        <template #default="{ id }">
                            <SelectInput :id="id" v-model="form.discount_category" :options="[{ value: 'standard', label: t('billing.categories.standard') }, { value: 'referrer', label: t('billing.categories.referrer') }, { value: 'free', label: t('billing.categories.free') }]" />
                        </template>
                    </FormField>
                    <FormField v-if="form.discount_category !== 'free'" id="discount-type" :label="t('billing.discount')">
                        <template #default="{ id }">
                            <SelectInput :id="id" v-model="form.discount_type" :options="[{ value: 'percent', label: '%' }, { value: 'fixed', label: '৳' }]" />
                        </template>
                    </FormField>
                    <FormField v-if="form.discount_category !== 'free'" id="discount-value" :label="t('billing.discount_value')" :error="form.errors.discount_value">
                        <template #default="{ id, invalid }">
                            <MoneyInput :id="id" v-model="form.discount_value" :invalid="invalid" />
                        </template>
                    </FormField>
                    <FormField id="discount-reason" :label="t('billing.reason')" :required="form.discount_category !== 'standard' || held" class="sm:col-span-2" span>
                        <template #default="{ id }">
                            <TextArea :id="id" v-model="form.discount_reason" rows="2" />
                        </template>
                    </FormField>
                </div>

                <div v-if="held" class="grid gap-2 sm:grid-cols-2">
                    <FormField id="approval-login" :label="t('billing.approval_login')">
                        <template #default="{ id }">
                            <input :id="id" v-model="form.approval_login" class="control" autocomplete="off" />
                        </template>
                    </FormField>
                    <FormField id="approval-pin" :label="t('billing.approval_pin')">
                        <template #default="{ id }">
                            <input :id="id" v-model="form.approval_pin" class="control" type="password" inputmode="numeric" autocomplete="off" />
                        </template>
                    </FormField>
                </div>

                <div class="space-y-2 border-t border-border pt-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold">{{ t('billing.payment') }}</h3>
                        <UiButton size="xs" @click="fillDue">{{ t('billing.due') }}</UiButton>
                    </div>
                    <FormField v-for="(amount, index) in cashLines" :id="`cash-${index}`" :key="index" :label="t('billing.cash_amount')">
                        <template #default="{ id }">
                            <MoneyInput :id="id" :ref="index === 0 ? setPayAmount : undefined" :model-value="amount" @update:model-value="(value) => (cashLines[index] = value)" />
                        </template>
                    </FormField>
                    <UiButton size="xs" icon="plus" @click="cashLines.push('')">{{ t('billing.add_cash_line') }}</UiButton>
                    <FormField id="tendered" :label="t('billing.tendered')">
                        <template #default="{ id }">
                            <MoneyInput :id="id" v-model="tendered" />
                        </template>
                    </FormField>
                    <div class="flex items-center justify-between text-[13px]">
                        <span class="text-muted">{{ t('billing.change') }}</span>
                        <MoneyText :value="fromCents(changeCents)" symbol />
                    </div>
                </div>

                <UiButton type="submit" variant="primary" icon="print" class="w-full" :loading="form.processing" :disabled="lines.length === 0 || !form.patient_id">
                    {{ t('billing.save_print') }}
                </UiButton>
            </aside>
        </form>

        <PatientFormSlideOver v-model:open="creating" stay :blood-groups="bloodGroups" @saved="patientCreated" />
        <ModalDialog v-model:open="helpOpen" :title="t('billing.shortcuts')">
            <ul class="space-y-1 text-[13px]">
                <li>{{ t('billing.shortcut_search') }}</li>
                <li>{{ t('billing.shortcut_new') }}</li>
                <li>{{ t('billing.shortcut_test') }}</li>
                <li>{{ t('billing.shortcut_pay') }}</li>
                <li>{{ t('billing.shortcut_save') }}</li>
            </ul>
        </ModalDialog>
    </div>
</template>
