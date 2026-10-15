<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ApprovalDialog from '@/components/ui-kit/ApprovalDialog.vue';
import ChartCard from '@/components/ui-kit/ChartCard.vue';
import CheckboxField from '@/components/ui-kit/CheckboxField.vue';
import ComboBox from '@/components/ui-kit/ComboBox.vue';
import DataTable from '@/components/ui-kit/DataTable.vue';
import DateRangePicker from '@/components/ui-kit/DateRangePicker.vue';
import DateText from '@/components/ui-kit/DateText.vue';
import EmptyState from '@/components/ui-kit/EmptyState.vue';
import ErrorState from '@/components/ui-kit/ErrorState.vue';
import FileUpload from '@/components/ui-kit/FileUpload.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import KpiCard from '@/components/ui-kit/KpiCard.vue';
import MobileInput from '@/components/ui-kit/MobileInput.vue';
import ModalDialog from '@/components/ui-kit/ModalDialog.vue';
import MoneyInput from '@/components/ui-kit/MoneyInput.vue';
import MoneyText from '@/components/ui-kit/MoneyText.vue';
import MultiSelect from '@/components/ui-kit/MultiSelect.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import PatientBanner from '@/components/ui-kit/PatientBanner.vue';
import QuantityStepper from '@/components/ui-kit/QuantityStepper.vue';
import RadioCards from '@/components/ui-kit/RadioCards.vue';
import ReasonDialog from '@/components/ui-kit/ReasonDialog.vue';
import ReportPreview from '@/components/ui-kit/ReportPreview.vue';
import ResultGrid from '@/components/ui-kit/ResultGrid.vue';
import RowActions from '@/components/ui-kit/RowActions.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SkeletonRows from '@/components/ui-kit/SkeletonRows.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import StatusStepper from '@/components/ui-kit/StatusStepper.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TabsNav from '@/components/ui-kit/TabsNav.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import TimelineItem from '@/components/ui-kit/TimelineItem.vue';
import TypedConfirmDialog from '@/components/ui-kit/TypedConfirmDialog.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useConfirm } from '@/composables/useConfirm';
import { useI18n } from '@/lib/i18n';
import { notify } from '@/lib/notify';

defineProps({ rows: { type: Array, default: () => [] } });

const { t } = useI18n();
const confirm = useConfirm();

const tab = ref('controls');
const slideOver = ref(false);
const modal = ref(false);
const reasonOpen = ref(false);
const approvalOpen = ref(false);
const typedOpen = ref(false);
const switchValue = ref(true);
const checkbox = ref(false);
const quantity = ref(2);
const payment = ref('cash');
const combo = ref(null);
const multi = ref(['cbc']);
const range = ref({ from: null, to: null, preset: 'today' });
const file = ref(null);
const results = ref({});

const form = useForm({ name: '', mobile: '', amount: '' });
const submitDemo = () => form.post(route('admin.ui-kit.validate'), { preserveScroll: true, onError: focusFirstError });

const tests = [
    { value: 'cbc', label: 'CBC', description: 'EDTA, 4 h', meta: '৳400' },
    { value: 'fbs', label: 'Fasting blood sugar', description: 'Fluoride, 2 h', meta: '৳150' },
    { value: 'lipid', label: 'Lipid profile', description: 'Serum, 6 h', meta: '৳1,200' },
];

const columns = [
    { key: 'code', label: t('ui_kit.invoice'), type: 'id', sortable: true },
    { key: 'name', label: t('ui_kit.item'), type: 'text', grow: true },
    { key: 'created_at', label: t('ui_kit.date'), type: 'datetime', sortable: true },
    { key: 'amount', label: t('ui_kit.amount'), type: 'money', sortable: true },
    { key: 'status', label: t('ui_kit.status'), type: 'status' },
    { key: 'actions', label: '', type: 'actions' },
];

const gridRows = [
    { id: 1, code: 'HB', name: 'Hemoglobin', unit: 'g/dL', result_type: 'number', range: { low: 13, high: 17, critical_low: 7, critical_high: 20, text: '13 - 17' }, previous: { value: '13.5', date: '2026-08-10' } },
    { id: 2, code: 'WBC', name: 'WBC count', unit: '10^3/uL', result_type: 'number', range: { low: 4, high: 11, critical_low: 2, critical_high: 30, text: '4 - 11' }, previous: null },
    { id: 3, code: 'HCT', name: 'Hematocrit', unit: '%', result_type: 'calculated', formula: '{HB} * 3', range: { low: 40, high: 50, text: '40 - 50' } },
    { id: 4, code: 'HBSAG', name: 'HBsAg', unit: '', result_type: 'choice', choices: ['Negative', 'Positive'], range: { abnormal_values: ['Positive'], text: 'Negative' } },
];

const report = {
    center: { name: 'Demo Diagnostic Center', address: 'House 1, Road 2, Dhanmondi, Dhaka', phone: '01700000000', license_no: 'DGHS-123' },
    patient: { name: 'Rahim Uddin', patient_code: 'P-26-000123', age_sex: '32 Y 4 M / M' },
    sample_code: 'BR01-261015-0042',
    reported_at: new Date().toISOString(),
    groups: [{ title: 'Complete Blood Count', rows: [{ name: 'Hemoglobin', value: '11.2', unit: 'g/dL', reference: '13 - 17', flag: 'L' }, { name: 'WBC count', value: '7.4', unit: '10^3/uL', reference: '4 - 11', flag: 'N' }] }],
    signatures: [{ name: 'Dr. A. Karim', qualification: 'MBBS, M.Phil (Pathology)', bmdc: 'A-12345' }],
};

async function askSimple() {
    const result = await confirm({ variant: 'danger', title: t('ui_kit.confirm_title'), message: t('ui_kit.confirm_message'), confirmText: t('ui_kit.confirm_action'), facts: [{ label: t('ui_kit.invoice'), value: 'INV-BR01-26-000123' }] });
    notify[result.confirmed ? 'success' : 'info'](result.confirmed ? t('ui_kit.confirmed') : t('ui_kit.cancelled'));
}

async function askReason() {
    const result = await confirm({ variant: 'warning', level: 'reason', title: t('ui_kit.reason_title'), message: t('ui_kit.confirm_message'), confirmText: t('ui_kit.confirm_action'), reasons: [{ value: 'duplicate', label: t('ui_kit.reason_duplicate') }, { value: 'other', label: t('ui_kit.reason_other') }] });
    if (result.confirmed) {
        notify.success(t('ui_kit.confirmed'));
    }
}
</script>

<template>
    <div>
        <Head :title="t('ui_kit.title')" />
        <PageHeader :title="t('ui_kit.title')" :description="t('ui_kit.description')">
            <template #actions>
                <UiButton icon="file-export">{{ t('common.export') }}</UiButton>
                <UiButton variant="primary" icon="plus" @click="slideOver = true">{{ t('ui_kit.open_slide_over') }}</UiButton>
            </template>
        </PageHeader>

        <TabsNav v-model="tab" :tabs="[{ key: 'controls', label: t('ui_kit.tab_controls') }, { key: 'data', label: t('ui_kit.tab_data') }, { key: 'clinical', label: t('ui_kit.tab_clinical') }]" />

        <div v-show="tab === 'controls'" class="grid gap-3 lg:grid-cols-2">
            <section class="card space-y-3">
                <h2 class="text-sm font-semibold">{{ t('ui_kit.buttons') }}</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <UiButton variant="primary" icon="floppy-disk">{{ t('common.save') }}</UiButton>
                    <UiButton>{{ t('common.cancel') }}</UiButton>
                    <UiButton variant="ghost" icon="print">{{ t('common.print') }}</UiButton>
                    <UiButton variant="accent" icon="print">{{ t('ui_kit.save_and_print') }}</UiButton>
                    <UiButton variant="destructive" icon="trash">{{ t('common.delete') }}</UiButton>
                    <UiButton loading>{{ t('common.save') }}</UiButton>
                    <UiButton disabled :disabled-reason="t('ui_kit.disabled_reason')">{{ t('common.save') }}</UiButton>
                    <UiButton icon-only icon="pen-to-square" :label="t('common.edit')" />
                    <UiButton size="xs" icon="plus">{{ t('common.add') }}</UiButton>
                    <UiButton size="touch" variant="primary">{{ t('ui_kit.touch') }}</UiButton>
                </div>
                <h2 class="pt-2 text-sm font-semibold">{{ t('ui_kit.badges') }}</h2>
                <div class="flex flex-wrap gap-2">
                    <StatusBadge v-for="s in ['paid', 'partial', 'due', 'released', 'verified', 'critical', 'cancelled', 'booked']" :key="s" :status="s" />
                </div>
                <h2 class="pt-2 text-sm font-semibold">{{ t('ui_kit.dialogs') }}</h2>
                <div class="flex flex-wrap gap-2">
                    <UiButton @click="askSimple">{{ t('ui_kit.simple_confirm') }}</UiButton>
                    <UiButton @click="askReason">{{ t('ui_kit.reason_confirm') }}</UiButton>
                    <UiButton @click="reasonOpen = true">ReasonDialog</UiButton>
                    <UiButton @click="approvalOpen = true">ApprovalDialog</UiButton>
                    <UiButton @click="typedOpen = true">TypedConfirmDialog</UiButton>
                    <UiButton @click="modal = true">{{ t('ui_kit.small_dialog') }}</UiButton>
                </div>
                <h2 class="pt-2 text-sm font-semibold">{{ t('ui_kit.toasts') }}</h2>
                <div class="flex flex-wrap gap-2">
                    <UiButton icon="circle-check" @click="notify.success(t('ui_kit.toast_success'))">success</UiButton>
                    <UiButton icon="circle-xmark" @click="notify.error(t('ui_kit.toast_error'))">error</UiButton>
                    <UiButton icon="triangle-exclamation" @click="notify.warning(t('ui_kit.toast_warning'))">warning</UiButton>
                    <UiButton icon="circle-info" @click="notify.info(t('ui_kit.toast_info'))">info</UiButton>
                </div>
            </section>

            <form class="card space-y-3" novalidate @submit.prevent="submitDemo">
                <h2 class="text-sm font-semibold">{{ t('ui_kit.server_validation') }}</h2>
                <p class="text-xs text-muted">{{ t('ui_kit.server_validation_hint') }}</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                    <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required>
                        <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
                    </FormField>
                    <FormField id="mobile" :label="t('fields.mobile')" :error="form.errors.mobile" :helper="t('fields.mobile_helper')" required>
                        <MobileInput id="mobile" v-model="form.mobile" :invalid="!!form.errors.mobile" />
                    </FormField>
                    <FormField id="amount" :label="t('ui_kit.amount')" :error="form.errors.amount" required>
                        <MoneyInput id="amount" v-model="form.amount" :invalid="!!form.errors.amount" />
                    </FormField>
                    <FormField id="note" :label="t('fields.notes')" optional>
                        <TextInput id="note" />
                    </FormField>
                </div>
                <p class="text-xs text-muted">* {{ t('common.required') }}</p>
                <div class="flex justify-end gap-2">
                    <UiButton @click="form.reset(); form.clearErrors()">{{ t('common.reset') }}</UiButton>
                    <UiButton type="submit" variant="primary" :loading="form.processing">{{ t('ui_kit.validate') }}</UiButton>
                </div>
            </form>

            <section class="card space-y-3 lg:col-span-2">
                <h2 class="text-sm font-semibold">{{ t('ui_kit.inputs') }}</h2>
                <div class="grid gap-x-4 gap-y-3 md:grid-cols-3">
                    <FormField id="combo" :label="t('ui_kit.combobox')"><ComboBox id="combo" v-model="combo" :options="tests" allow-create /></FormField>
                    <FormField id="multi" :label="t('ui_kit.multiselect')"><MultiSelect id="multi" v-model="multi" :options="tests" /></FormField>
                    <FormField id="select" :label="t('ui_kit.select')"><SelectInput id="select" :model-value="'a'" :options="[{ value: 'a', label: 'Option A' }, { value: 'b', label: 'Option B' }]" /></FormField>
                    <FormField id="textarea" :label="t('fields.notes')"><TextArea id="textarea" /></FormField>
                    <FormField id="qty" :label="t('ui_kit.quantity')"><QuantityStepper v-model="quantity" /></FormField>
                    <div class="space-y-2 pt-5"><SwitchField v-model="switchValue" :label="t('ui_kit.switch')" /><CheckboxField v-model="checkbox" :label="t('ui_kit.checkbox')" /></div>
                    <FormField id="range" :label="t('periods.label')" span><DateRangePicker id="range" v-model="range" /></FormField>
                    <FormField id="payment" :label="t('ui_kit.payment_method')" span>
                        <RadioCards v-model="payment" name="payment" :columns="4" :options="[{ value: 'cash', label: t('payments.methods.cash'), icon: 'money-bill-wave' }, { value: 'bkash', label: t('payments.methods.bkash'), icon: 'mobile-screen' }, { value: 'card', label: t('payments.methods.card'), icon: 'credit-card' }, { value: 'due', label: t('payments.methods.due'), icon: 'clock' }]" />
                    </FormField>
                    <FormField id="upload" :label="t('ui_kit.upload')" span><FileUpload id="upload" v-model="file" /></FormField>
                </div>
            </section>
        </div>

        <div v-show="tab === 'data'" class="space-y-3">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <KpiCard :label="t('ui_kit.kpi_patients')" :value="128" :change="8.2" :sparkline="[3, 5, 4, 7, 6, 9, 8]" icon="hospital-user" />
                <KpiCard :label="t('ui_kit.kpi_billed')" :value="1234567" format="money" :change="-3.1" :sparkline="[9, 7, 8, 6, 5, 6, 4]" icon="file-invoice" />
                <KpiCard :label="t('ui_kit.kpi_dues')" :value="45200" format="money" :change="2.4" invert-change icon="money-bill-wave" />
                <KpiCard :label="t('ui_kit.kpi_pending')" loading />
            </div>
            <FilterBar :filters="{}">
                <SelectInput :model-value="null" class="!w-36" :placeholder="t('ui_kit.status')" :options="[{ value: 'paid', label: t('statuses.paid') }]" />
            </FilterBar>
            <DataTable :columns="columns" :rows="rows" :totals="{ amount: rows.reduce((s, r) => s + Number(r.amount), 0).toFixed(2) }" selectable clickable :sync-url="false" @row-click="slideOver = true">
                <template #row-actions>
                    <RowActions :items="[{ label: t('common.edit'), icon: 'pen-to-square' }, { label: t('common.print'), icon: 'print' }, { separator: true }, { label: t('common.delete'), icon: 'trash', danger: true, onSelect: askSimple }]" />
                </template>
                <template #bulk-actions><UiButton size="xs" icon="file-export">{{ t('common.export') }}</UiButton></template>
            </DataTable>
            <div class="grid gap-3 lg:grid-cols-3">
                <ChartCard :title="t('ui_kit.chart_trend')" type="line" money :labels="['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" :datasets="[{ label: t('ui_kit.this_week'), data: [12000, 15000, 11000, 18000, 21000, 19000, 24000], fill: true }, { label: t('ui_kit.last_week'), data: [10000, 12000, 13000, 15000, 16000, 17000, 20000], dashed: true }]" />
                <ChartCard :title="t('ui_kit.chart_methods')" type="doughnut" money :labels="['Cash', 'bKash', 'Nagad', 'Card']" :datasets="[{ label: t('ui_kit.amount'), data: [52000, 21000, 9000, 6000] }]" center-label="৳88K" />
                <ChartCard :title="t('ui_kit.chart_top')" type="bar" horizontal :labels="['CBC', 'FBS', 'Lipid', 'TSH', 'Urine RE']" :datasets="[{ label: t('ui_kit.tests'), data: [120, 98, 76, 54, 40] }]" />
            </div>
            <div class="grid gap-3 lg:grid-cols-3">
                <div class="card"><EmptyState :title="t('ui_kit.empty_title')" :description="t('ui_kit.empty_text')"><UiButton variant="primary" icon="plus">{{ t('common.add') }}</UiButton></EmptyState></div>
                <div class="card"><ErrorState reference="REF-1234" /></div>
                <div class="card"><SkeletonRows :rows="4" :columns="3" /></div>
            </div>
            <p class="text-[13px]">
                <MoneyText value="1234567.5" symbol /> | <MoneyText value="-1250" /> | <MoneyText value="0" /> | <DateText :value="new Date().toISOString()" time /> | <DateText :value="new Date(Date.now() - 300000).toISOString()" relative />
            </p>
        </div>

        <div v-show="tab === 'clinical'" class="space-y-3">
            <PatientBanner :patient="{ name: 'Rahim Uddin', name_bn: 'রহিম উদ্দিন', patient_code: 'P-26-000123', age_text: '32 Y 4 M', sex: 'male', mobile: '01711000000', blood_group: 'B+', allergies: 'Penicillin', due: '450.00' }">
                <template #due-action><UiButton size="xs" variant="primary">{{ t('patients.collect_due') }}</UiButton></template>
            </PatientBanner>
            <StatusStepper current="entered" />
            <StatusStepper current="received" failed="received" compact />
            <ResultGrid v-model="results" :rows="gridRows" />
            <ul class="card">
                <TimelineItem :date="new Date().toISOString()" icon="file-medical" :title="t('ui_kit.timeline_report')" status="released" />
                <TimelineItem :date="new Date(Date.now() - 86400000).toISOString()" icon="file-invoice" :title="t('ui_kit.timeline_invoice')" status="partial" last />
            </ul>
            <ReportPreview :report="report" />
        </div>

        <SlideOver v-model:open="slideOver" :title="t('ui_kit.slide_over_title')" :description="t('ui_kit.slide_over_text')">
            <div class="space-y-3">
                <FormField id="so-name" :label="t('fields.name')" required><TextInput id="so-name" /></FormField>
                <FormField id="so-notes" :label="t('fields.notes')" optional><TextArea id="so-notes" /></FormField>
            </div>
            <template #footer>
                <UiButton @click="slideOver = false">{{ t('common.cancel') }}</UiButton>
                <UiButton variant="primary" icon="floppy-disk" @click="slideOver = false">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>

        <ModalDialog v-model:open="modal" :title="t('ui_kit.small_dialog')" size="sm">
            <FormField id="modal-qty" :label="t('ui_kit.quantity')"><TextInput id="modal-qty" inputmode="numeric" /></FormField>
            <template #footer>
                <UiButton @click="modal = false">{{ t('common.cancel') }}</UiButton>
                <UiButton variant="primary" @click="modal = false">{{ t('common.save') }}</UiButton>
            </template>
        </ModalDialog>

        <ReasonDialog v-model:open="reasonOpen" variant="warning" :title="t('ui_kit.reason_title')" :confirm-text="t('ui_kit.confirm_action')" @confirm="reasonOpen = false" />
        <ApprovalDialog v-model:open="approvalOpen" variant="warning" :title="t('ui_kit.approval_title')" :message="t('ui_kit.approval_text')" :confirm-text="t('ui_kit.approve')" @confirm="approvalOpen = false" />
        <TypedConfirmDialog v-model:open="typedOpen" :title="t('ui_kit.typed_title')" :message="t('ui_kit.typed_text')" :confirm-text="t('ui_kit.typed_action')" @confirm="typedOpen = false" />
    </div>
</template>
