<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { nextTick, onMounted, ref } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import EmptyState from '@/components/ui-kit/EmptyState.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { formatDate } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

/**
 * Scan-to-receive screen: a barcode scanner types the sample ID and
 * presses Enter (design.md section 9).
 */
const props = defineProps({
    awaiting: { type: Object, required: true },
    received: { type: Array, default: () => [] },
});

const { t } = useI18n();
const scan = ref(null);
const error = ref(null);

const form = useForm({
    code: '',
});

onMounted(() => {
    scan.value?.focus();
});

function submit() {
    error.value = null;

    if (form.code.trim() === '') {
        return;
    }

    form.post(route('admin.samples.scan'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            nextTick(() => scan.value?.focus());
        },
        onError: (errors) => {
            error.value = errors.code ?? null;
        },
    });
}

function receive(sample) {
    form
        .transform(() => ({ code: sample.sample_code }))
        .post(route('admin.samples.scan'), { preserveScroll: true });
}
</script>

<template>
    <div>
        <Head :title="t('lab.receive_title')" />
        <PageHeader :title="t('lab.receive_title')" :description="t('lab.receive_description')" />

        <form class="card flex items-center gap-2" @submit.prevent="submit">
            <AppIcon name="barcode" class="shrink-0 text-primary" />
            <input
                :ref="(el) => (scan = el)"
                v-model="form.code"
                type="text"
                autocomplete="off"
                spellcheck="false"
                :placeholder="t('lab.scan_placeholder')"
                :aria-label="t('lab.scan_to_open')"
                class="h-11 w-full bg-transparent text-lg tabular-nums focus:outline-none"
            />
            <UiButton type="submit" variant="primary" size="touch" icon="check" :loading="form.processing">
                {{ t('lab.receive') }}
            </UiButton>
        </form>
        <p v-if="error || form.errors.code" class="mt-2 flex items-center gap-1 text-xs text-danger" role="alert">
            <AppIcon name="circle-exclamation" size="sm" />
            {{ error ?? form.errors.code }}
        </p>

        <h2 class="mt-6 text-sm font-semibold">{{ t('lab.awaiting') }}</h2>
        <EmptyState
            v-if="(awaiting.data ?? []).length === 0"
            icon="vial"
            :title="t('lab.worklist_empty')"
            :description="t('lab.worklist_empty_hint')"
        />
        <ul v-else class="mt-2 space-y-2">
            <li
                v-for="sample in awaiting.data"
                :key="sample.id"
                class="flex flex-col gap-3 rounded-lg border border-border bg-surface p-3 sm:flex-row sm:items-center"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold tabular-nums">{{ sample.sample_code }}</span>
                        <span v-if="sample.urgency === 'urgent'" class="inline-flex h-5 items-center gap-1 rounded-md bg-danger/10 px-2 text-[11px] font-medium text-danger">
                            <AppIcon name="bolt" size="xs" />{{ t('lab.urgent') }}
                        </span>
                    </div>
                    <div class="text-[13px] font-medium">{{ sample.patient?.name }}</div>
                    <div class="text-xs text-muted">
                        {{ sample.patient?.age_text }} / {{ t(`patients.sex_short.${sample.patient?.sex ?? 'male'}`) }} ·
                        {{ sample.tests?.map((test) => test.code).join(', ') }}
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <span class="text-xs text-muted">{{ formatDate(sample.collected_at, { time: true }) }}</span>
                    <UiButton variant="primary" icon="check" @click="receive(sample)">{{ t('lab.receive') }}</UiButton>
                </div>
            </li>
        </ul>

        <h2 class="mt-6 text-sm font-semibold">{{ t('lab.recent') }}</h2>
        <p v-if="received.length === 0" class="mt-1 text-[13px] text-muted">{{ t('common.none') }}</p>
        <ul v-else class="mt-2 divide-y divide-border rounded-lg border border-border bg-surface">
            <li v-for="sample in received" :key="sample.id" class="flex items-center gap-3 px-3 py-1.5 text-[13px]">
                <span class="tabular-nums">{{ sample.sample_code }}</span>
                <span class="min-w-0 flex-1 truncate text-muted">{{ sample.patient?.name }}</span>
                <StatusBadge :status="sample.status" />
                <span class="text-xs text-muted">{{ formatDate(sample.received_at, { time: true }) }}</span>
            </li>
        </ul>
    </div>
</template>
