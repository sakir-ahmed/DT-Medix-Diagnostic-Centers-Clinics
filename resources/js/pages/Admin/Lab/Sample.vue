<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import EmptyState from '@/components/ui-kit/EmptyState.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import ModalDialog from '@/components/ui-kit/ModalDialog.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import PatientBanner from '@/components/ui-kit/PatientBanner.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import StatusStepper from '@/components/ui-kit/StatusStepper.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import TatCountdown from '@/components/lab/TatCountdown.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { formatDate } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

/**
 * One sample in the lab: patient banner, status stepper and its tests
 * with entry actions.
 */
const props = defineProps({
    sample: { type: Object, required: true },
});

const { t } = useI18n();

const tests = computed(() => (props.sample.tests ?? []).filter((test) => test.status !== 'cancelled'));

const dueAt = computed(() => tests.value.reduce((min, test) => (test.expected_at && (!min || test.expected_at < min) ? test.expected_at : min), null));

const currentStep = computed(() => {
    const statuses = tests.value.map((test) => test.status);

    if (statuses.every((status) => ['entered', 'verified', 'released'].includes(status))) {
        return 'entered';
    }

    return props.sample.status === 'collected' ? 'collected' : 'received';
});

const canReject = computed(() => ['collected', 'received'].includes(props.sample.status));

const rejectForm = ref({
    open: false,
    reason: '',
    request_recollection: true,
});

function enter(test) {
    router.visit(route('admin.lab.results', test.id));
}

function reject() {
    router.post(
        route('admin.samples.reject', props.sample.id),
        {
            reason: rejectForm.value.reason,
            request_recollection: rejectForm.value.request_recollection,
        },
        {
            onSuccess: () => {
                rejectForm.value.open = false;
            },
        },
    );
}
</script>

<template>
    <div>
        <Head :title="t('lab.sample_title', { code: sample.sample_code })" />
        <PageHeader :title="t('lab.sample_title', { code: sample.sample_code })">
            <template #actions>
                <UiButton variant="secondary" icon="arrow-left" :href="route('admin.lab.worklist')">{{ t('common.back') }}</UiButton>
                <UiButton v-if="canReject" variant="ghost-danger" icon="ban" @click="rejectForm.open = true">{{ t('lab.reject') }}</UiButton>
            </template>
        </PageHeader>

        <PatientBanner :patient="sample.patient" />

        <div class="card mt-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px]">
                <span class="flex items-center gap-1 font-semibold tabular-nums"><AppIcon name="vial" class="text-primary" />{{ sample.sample_code }}</span>
                <span v-if="sample.sample_type" class="text-muted">{{ sample.sample_type }} · {{ sample.container }}</span>
                <span class="text-muted">{{ t('lab.collected_by') }}: {{ sample.collected_at ? formatDate(sample.collected_at, { time: true }) : t('common.none') }}</span>
                <span class="text-muted">{{ t('lab.received_by') }}: {{ sample.received_at ? formatDate(sample.received_at, { time: true }) : t('common.none') }}</span>
            </div>
            <StatusStepper :current="currentStep" compact />
        </div>

        <EmptyState v-if="tests.length === 0" icon="flask" :title="t('lab.worklist_empty')" :description="t('lab.worklist_empty_hint')" />

        <div v-else class="mt-3 overflow-x-auto rounded-lg border border-border bg-surface">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="h-8 border-b border-border text-xs text-muted">
                        <th class="px-3 text-left font-semibold">{{ t('lab.tests') }}</th>
                        <th class="px-3 text-left font-semibold">{{ t('lab.tat') }}</th>
                        <th class="px-3 text-left font-semibold">{{ t('common.status') }}</th>
                        <th class="w-40 px-3 text-right font-semibold">{{ t('table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="test in tests" :key="test.id" class="border-b border-border last:border-0 hover:bg-subtle">
                        <td class="px-3 py-1.5">
                            <span class="block font-medium">{{ test.name }}</span>
                            <span class="block text-xs text-muted">{{ test.code }}</span>
                        </td>
                        <td class="px-3 py-1.5"><TatCountdown :due-at="test.expected_at" /></td>
                        <td class="px-3 py-1.5"><StatusBadge :status="test.status" /></td>
                        <td class="px-3 py-1.5 text-right">
                            <UiButton
                                v-if="test.status === 'received' || test.status === 'entered'"
                                variant="primary"
                                size="xs"
                                icon="pen-to-square"
                                @click="enter(test)"
                            >
                                {{ test.status === 'entered' ? t('lab.continue_results') : t('lab.enter_results') }}
                            </UiButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ModalDialog v-model:open="rejectForm.open" :title="t('lab.reject_title', { code: sample.sample_code })">
            <p class="text-[13px] text-muted">{{ t('lab.reject_message') }}</p>
            <div class="mt-3 space-y-3">
                <FormField id="reject-reason" :label="t('lab.reason')" :helper="t('lab.reason_hint')" required>
                    <template #default="{ id }">
                        <TextArea :id="id" v-model="rejectForm.reason" :rows="3" />
                    </template>
                </FormField>
                <label class="flex items-center gap-2 text-[13px]">
                    <input v-model="rejectForm.request_recollection" type="checkbox" class="h-4 w-4" />
                    {{ t('lab.request_recollection') }}
                </label>
                <p class="text-xs text-muted">{{ t('lab.recollection_note') }}</p>
            </div>
            <template #footer>
                <UiButton @click="rejectForm.open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton variant="destructive" icon="ban" :disabled="rejectForm.reason.trim() === ''" @click="reject">{{ t('lab.reject') }}</UiButton>
            </template>
        </ModalDialog>
    </div>
</template>
