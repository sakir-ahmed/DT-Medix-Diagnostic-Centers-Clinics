<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import EmptyState from '@/components/ui-kit/EmptyState.vue';
import FilterBar from '@/components/ui-kit/FilterBar.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import StatusStepper from '@/components/ui-kit/StatusStepper.vue';
import TatCountdown from '@/components/lab/TatCountdown.vue';
import { echo } from '@/lib/echo';
import { useI18n } from '@/lib/i18n';

/**
 * Department worklist with TAT countdown, urgent pinning and scan-to-open
 * (design.md section 9). Refreshes in real time through Reverb.
 */
const props = defineProps({
    samples: { type: Object, required: true },
    departments: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const page = usePage();
const scanCode = ref('');
const scanBox = ref(null);

const rows = computed(() => {
    const list = props.samples.data ?? [];

    return list.map((sample) => {
        const tests = sample.tests ?? [];
        const active = tests.filter((test) => test.status !== 'cancelled');
        const entered = active.filter((test) => ['entered', 'verified', 'released'].includes(test.status));

        return {
            ...sample,
            test_codes: active.map((test) => test.code),
            due_at: active.reduce((min, test) => (test.expected_at && (!min || test.expected_at < min) ? test.expected_at : min), null),
            stage: entered.length === 0 ? sample.status : entered.length === active.length ? 'entered' : 'partly-entered',
            step: entered.length === 0 ? (sample.status === 'collected' ? 'collected' : 'received') : 'entered',
        };
    });
});

const columns = [
    { key: 'sample_code', label: t('lab.sample'), type: 'id' },
    { key: 'patient', label: t('billing.patient'), type: 'text', grow: true },
    { key: 'tests', label: t('lab.tests'), type: 'text' },
    { key: 'collected_at', label: t('lab.collected_by'), type: 'datetime' },
    { key: 'tat', label: t('lab.tat'), type: 'text' },
    { key: 'status', label: t('common.status'), type: 'status' },
];

function openSample(row) {
    router.visit(route('admin.lab.sample', row.id));
}

function scan() {
    const code = scanCode.value.trim();

    if (code === '') {
        return;
    }

    const found = rows.value.find((row) => row.sample_code.toLowerCase() === code.toLowerCase());

    if (found) {
        openSample(found);
    } else {
        router.visit(route('admin.lab.worklist', { ...props.filters, q: code }), { preserveState: true });
    }

    scanCode.value = '';
    scanBox.value?.focus();
}

let channelName = null;

onMounted(() => {
    const connection = echo();
    const tenantId = page.props.tenant?.id;
    const branchId = page.props.current_branch_id;

    if (!connection || !tenantId || !branchId) {
        return;
    }

    channelName = `tenant.${tenantId}.branch.${branchId}.lab`;
    connection.private(channelName).listen('.SampleUpdated', () => {
        router.reload({ only: ['samples'], preserveScroll: true });
    });
    connection.private(channelName).listen('.ResultSubmitted', () => {
        router.reload({ only: ['samples'], preserveScroll: true });
    });
});

onBeforeUnmount(() => {
    if (channelName) {
        echo()?.leave(channelName);
    }
});

const stageLabels = {
    collected: t('lab.status_collected'),
    received: t('lab.status_received'),
    'partly-entered': t('lab.entered_partial'),
    entered: t('statuses.entered'),
};
</script>

<template>
    <div>
        <Head :title="t('lab.worklist_title')" />
        <PageHeader :title="t('lab.worklist_title')" :description="t('lab.worklist_description')" />

        <form class="card mb-3 flex items-center gap-2" @submit.prevent="scan">
            <AppIcon name="barcode" class="shrink-0 text-primary" />
            <input
                :ref="(el) => (scanBox = el)"
                v-model="scanCode"
                type="text"
                autocomplete="off"
                spellcheck="false"
                :placeholder="t('lab.scan_to_open')"
                :aria-label="t('lab.scan_to_open')"
                class="h-9 w-full bg-transparent text-base tabular-nums focus:outline-none"
            />
            <span class="hidden text-xs text-muted sm:block">{{ t('common.search') }}</span>
        </form>

        <FilterBar :filters="filters" :search-placeholder="t('lab.sample')" :only="['samples']">
            <template #default="{ form }">
                <div class="flex items-center gap-2">
                    <SelectInput id="worklist-department" v-model="form.department" :options="[{ value: 0, label: t('lab.all_departments') }, ...departments.map((d) => ({ value: d.id, label: d.name }))]" class="w-44" :aria-label="t('catalog.department')" />
                    <SelectInput
                        id="worklist-status"
                        v-model="form.status"
                        :options="[
                            { value: '', label: t('common.all') },
                            { value: 'collected', label: t('lab.status_collected') },
                            { value: 'received', label: t('lab.status_received') },
                        ]"
                        class="w-40"
                        :aria-label="t('common.status')"
                    />
                    <SelectInput
                        id="worklist-urgency"
                        v-model="form.urgency"
                        :options="[
                            { value: '', label: t('billing.urgency') },
                            { value: 'urgent', label: t('lab.urgent') },
                            { value: 'routine', label: t('lab.routine') },
                        ]"
                        class="w-36"
                        :aria-label="t('billing.urgency')"
                    />
                </div>
            </template>
        </FilterBar>

        <EmptyState v-if="rows.length === 0 && !filters.q" icon="flask" :title="t('lab.worklist_empty')" :description="t('lab.worklist_empty_hint')" />

        <div v-else class="overflow-x-auto rounded-lg border border-border bg-surface">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="h-8 border-b border-border text-xs text-muted">
                        <th class="px-3 text-left font-semibold">{{ t('lab.sample') }}</th>
                        <th class="px-3 text-left font-semibold">{{ t('billing.patient') }}</th>
                        <th class="px-3 text-left font-semibold">{{ t('lab.tests') }}</th>
                        <th class="px-3 text-left font-semibold">{{ t('lab.tat') }}</th>
                        <th class="px-3 text-left font-semibold">{{ t('common.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in rows"
                        :key="row.id"
                        class="cursor-pointer border-b border-border last:border-0 hover:bg-subtle"
                        :class="row.urgency === 'urgent' ? 'bg-warning/5' : ''"
                        @click="openSample(row)"
                        @keydown.enter="openSample(row)"
                        tabindex="0"
                    >
                        <td class="px-3 py-1.5">
                            <span class="flex items-center gap-1 font-medium tabular-nums">
                                <AppIcon v-if="row.urgency === 'urgent'" name="bolt" size="xs" class="text-warning" />
                                {{ row.sample_code }}
                            </span>
                        </td>
                        <td class="px-3 py-1.5">
                            <span class="block font-medium">{{ row.patient?.name }}</span>
                            <span class="block text-xs text-muted">{{ row.patient?.age_text }} / {{ t(`patients.sex_short.${row.patient?.sex ?? 'male'}`) }}</span>
                        </td>
                        <td class="px-3 py-1.5">
                            <span class="flex flex-wrap gap-1">
                                <span v-for="code in row.test_codes" :key="code" class="inline-flex h-5 items-center rounded-md bg-subtle px-1.5 text-[11px] font-medium text-muted">{{ code }}</span>
                            </span>
                        </td>
                        <td class="px-3 py-1.5"><TatCountdown :due-at="row.due_at" /></td>
                        <td class="px-3 py-1.5">
                            <StatusStepper v-if="row.stage === 'entered'" :steps="['ordered', 'collected', 'received', 'entered']" current="entered" compact />
                            <span v-else class="text-muted">{{ stageLabels[row.stage] ?? row.stage }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-if="samples.last_page > 1" class="mt-3 text-center text-xs text-muted">
            {{ samples.current_page }} / {{ samples.last_page }}
        </p>
    </div>
</template>
