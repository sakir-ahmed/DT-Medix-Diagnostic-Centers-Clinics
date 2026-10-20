<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import EmptyState from '@/components/ui-kit/EmptyState.vue';
import FileUpload from '@/components/ui-kit/FileUpload.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import PatientBanner from '@/components/ui-kit/PatientBanner.vue';
import ResultGrid from '@/components/ui-kit/ResultGrid.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import TatCountdown from '@/components/lab/TatCountdown.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import CriticalAckDialog from '@/components/lab/CriticalAckDialog.vue';
import { formatDate, formatFileSize } from '@/lib/format';
import { useI18n } from '@/lib/i18n';
import { notify } from '@/lib/notify';

/**
 * Keyboard-first result entry (design.md section 9): the grid for
 * parameter tests, findings and impression for narrative tests, drafts
 * autosaved, Ctrl+Enter submits and opens the next sample.
 */
const props = defineProps({
    item: { type: Object, required: true },
    sample: { type: Object, required: true },
    patient: { type: Object, required: true },
    isNarrative: { type: Boolean, default: false },
    rows: { type: Array, default: () => [] },
    narrative: { type: Object, default: () => ({ findings: null, impression: null }) },
    phrases: { type: Array, default: () => [] },
    attachments: { type: Array, default: () => [] },
    deltaPercent: { type: Number, default: 50 },
    nextUrl: { type: String, default: null },
});

const { t } = useI18n();

const editable = computed(() => ['received', 'entered'].includes(props.item.status));

const results = ref(
    Object.fromEntries(props.rows.map((row) => [row.id, { value: row.value ?? '', remarks: row.remarks ?? '' }])),
);
const findings = ref(props.narrative.findings ?? '');
const impression = ref(props.narrative.impression ?? '');
const flags = ref({});
const grid = ref(null);

const criticalRows = computed(() =>
    props.rows.filter((row) => flags.value[row.id] === 'C' && !row.critical_acknowledged),
);
const hasCritical = computed(() => props.rows.some((row) => flags.value[row.id] === 'C'));
const ackDialog = ref(false);
const saving = ref(false);
const autosaveNote = ref(null);
let autosaveTimer = null;

function payload() {
    return {
        results: Object.fromEntries(Object.entries(results.value).map(([id, entry]) => [id, { value: entry.value, remarks: entry.remarks }])),
        findings: props.isNarrative ? findings.value : null,
        impression: props.isNarrative ? impression.value : null,
    };
}

function autosave() {
    if (!editable.value) {
        return;
    }

    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(saveDraft, 1500, true);
}

function saveDraft(silent = false) {
    if (!editable.value) {
        return Promise.resolve();
    }

    saving.value = true;

    return router.put(route('admin.lab.results.update', props.item.id), payload(), {
        preserveScroll: true,
        onSuccess: () => {
            autosaveNote.value = new Date();

            if (!silent) {
                notify.success(t('lab.draft_saved'));
            }
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

function submit(andNext = false) {
    if (criticalRows.value.length > 0) {
        ackDialog.value = true;

        return;
    }

    saving.value = true;

    router.put(route('admin.lab.results.update', props.item.id), payload(), {
        preserveScroll: true,
        onSuccess: () => {
            router.post(route('admin.lab.results.submit', props.item.id), {}, {
                onSuccess: () => {
                    if (andNext && props.nextUrl) {
                        router.visit(props.nextUrl);
                    }
                },
                onFinish: () => {
                    saving.value = false;
                },
            });
        },
        onError: () => {
            saving.value = false;
        },
    });
}

function onSaveNext() {
    submit(true);
}

function acknowledge(payload) {
    router.post(route('admin.lab.results.acknowledge', props.item.id), payload, {
        preserveScroll: true,
        onSuccess: () => notify.success(t('lab.critical_acknowledged')),
    });
}

const upload = ref(null);

function uploadAttachment(file) {
    if (!file) {
        return;
    }

    router.post(
        route('admin.lab.results.attachments', props.item.id),
        { file },
        {
            preserveScroll: true,
            onSuccess: () => {
                upload.value = null;
                notify.success(t('lab.attachment_added'));
            },
        },
    );
}

function removeAttachment(attachment) {
    router.delete(route('admin.lab.attachments.destroy', attachment.id), {
        preserveScroll: true,
        preserveState: false,
    });
}

const phraseMenu = ref({ open: false, field: null, query: '' });

const filteredPhrases = computed(() =>
    phraseMenu.value.query === ''
        ? props.phrases
        : props.phrases.filter((phrase) => phrase.shortcut.toLowerCase().includes(phraseMenu.value.query.toLowerCase()) || phrase.text.toLowerCase().includes(phraseMenu.value.query.toLowerCase())),
);

function onNarrativeKey(event, field) {
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        submit(false);

        return;
    }

    if (phraseMenu.value.open) {
        if (event.key === 'Escape') {
            phraseMenu.value.open = false;
        }

        return;
    }

    if (event.key === '/') {
        const value = field === 'findings' ? findings.value : impression.value;
        const lastWord = value.slice(0, event.target.selectionStart ?? value.length).split(/\s+/).pop() ?? '';

        if (lastWord === '') {
            phraseMenu.value = { open: true, field, query: '' };
            event.preventDefault();
        }
    }
}

function onNarrativeInput(event, field) {
    const value = field === 'findings' ? findings.value : impression.value;
    const lastWord = value.slice(0, event.target.selectionStart ?? value.length).split(/\s+/).pop() ?? '';

    if (phraseMenu.value.open && lastWord.startsWith('/')) {
        phraseMenu.value.query = lastWord.slice(1);
    } else if (phraseMenu.value.open) {
        phraseMenu.value.open = false;
    }

    autosave();
}

function insertPhrase(phrase) {
    const field = phraseMenu.value.field;

    if (!field) {
        return;
    }

    const current = field === 'findings' ? findings.value : impression.value;

    if (current.endsWith('/')) {
        const next = current.slice(0, -1) + phrase.text + ' ';

        if (field === 'findings') {
            findings.value = next;
        } else {
            impression.value = next;
        }
    }

    phraseMenu.value = { open: false, field: null, query: '' };
    autosave();
}

onBeforeUnmount(() => {
    clearTimeout(autosaveTimer);
});

watch(results, autosave, { deep: true });

onMounted(() => {
    if (editable.value && !props.isNarrative) {
        grid.value?.focusFirst?.();
    }
});
</script>

<template>
    <div>
        <Head :title="item.name" />
        <PageHeader :title="item.name">
            <template #actions>
                <UiButton variant="secondary" icon="arrow-left" :href="route('admin.lab.sample', sample.id)">{{ t('common.back') }}</UiButton>
                <UiButton v-if="editable" variant="secondary" icon="floppy-disk" :loading="saving" @click="saveDraft(false)">
                    {{ t('lab.save_draft') }}
                </UiButton>
                <UiButton v-if="editable" variant="accent" icon="paper-plane" :disabled="saving" @click="submit(false)">
                    {{ t('lab.submit_results') }}
                </UiButton>
            </template>
        </PageHeader>

        <PatientBanner :patient="patient" />

        <div class="card mt-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px]">
                <span class="flex items-center gap-1 font-semibold tabular-nums"><AppIcon name="vial" class="text-primary" />{{ sample.sample_code }}</span>
                <StatusBadge :status="item.status" />
                <span v-if="item.urgency === 'urgent'" class="inline-flex h-5 items-center gap-1 rounded-md bg-danger/10 px-2 text-[11px] font-medium text-danger">
                    <AppIcon name="bolt" size="xs" />{{ t('lab.urgent') }}
                </span>
                <span v-if="sample.received_at" class="text-muted">{{ t('lab.received_by') }}: {{ formatDate(sample.received_at, { time: true }) }}</span>
                <TatCountdown :due-at="item.expected_at" />
            </div>
            <span v-if="autosaveNote" class="text-xs text-muted">
                <AppIcon name="circle-check" size="xs" class="text-success" />
                {{ t('lab.autosaved') }} {{ formatDate(autosaveNote, { time: true }) }}
            </span>
        </div>

        <div
            v-if="hasCritical"
            class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-danger/40 bg-danger/10 px-3 py-2"
            role="alert"
        >
            <span class="flex items-center gap-2 text-sm font-medium text-danger">
                <AppIcon name="triangle-exclamation" />
                {{ t('lab.critical_banner') }}
            </span>
            <UiButton v-if="criticalRows.length > 0" variant="destructive" size="xs" icon="user-shield" @click="ackDialog = true">
                {{ t('lab.critical_acknowledge') }}
            </UiButton>
            <span v-else class="inline-flex items-center gap-1 text-xs text-success"><AppIcon name="circle-check" size="xs" />{{ t('lab.acknowledged_by') }}</span>
        </div>

        <template v-if="!isNarrative">
            <div class="mt-3">
                <ResultGrid
                    :ref="(el) => (grid = el)"
                    v-model="results"
                    :rows="rows"
                    :readonly="!editable"
                    :delta-percent="deltaPercent"
                    @flags="(value) => (flags = value)"
                    @save-next="onSaveNext"
                />
            </div>
            <p class="mt-1 text-xs text-muted">{{ t('results.delta_warning') }}</p>
            <div v-if="item.method || item.interpretation" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <p v-if="item.method" class="text-[13px]"><span class="text-muted">{{ t('lab.method') }}:</span> {{ item.method }}</p>
                <p v-if="item.interpretation" class="text-[13px]"><span class="text-muted">{{ t('lab.interpretation') }}:</span> {{ item.interpretation }}</p>
            </div>
        </template>

        <template v-else>
            <div class="relative mt-3 grid grid-cols-1 gap-3">
                <FormField id="result-findings" :label="t('lab.findings')" :helper="phrases.length > 0 ? t('lab.quick_phrases_hint') : null" required>
                    <template #default="{ id }">
                        <TextArea
                            :id="id"
                            v-model="findings"
                            :rows="7"
                            :readonly="!editable"
                            @keydown="(event) => onNarrativeKey(event, 'findings')"
                            @input="(event) => onNarrativeInput(event, 'findings')"
                        />
                    </template>
                </FormField>
                <FormField id="result-impression" :label="t('lab.impression')" required>
                    <template #default="{ id }">
                        <TextArea
                            :id="id"
                            v-model="impression"
                            :rows="3"
                            :readonly="!editable"
                            @keydown="(event) => onNarrativeKey(event, 'impression')"
                            @input="(event) => onNarrativeInput(event, 'impression')"
                        />
                    </template>
                </FormField>

                <div
                    v-if="phraseMenu.open"
                    class="absolute right-0 z-10 mt-8 max-h-60 w-72 overflow-y-auto rounded-lg border border-border bg-surface p-1 shadow-lg"
                    role="listbox"
                    :aria-label="t('lab.quick_phrases')"
                >
                    <button
                        v-for="phrase in filteredPhrases"
                        :key="phrase.shortcut"
                        type="button"
                        class="block w-full rounded-md px-2 py-1 text-left text-[13px] hover:bg-subtle"
                        role="option"
                        @click="insertPhrase(phrase)"
                        @keydown.enter.prevent="insertPhrase(phrase)"
                    >
                        <span class="font-medium tabular-nums">/{{ phrase.shortcut }}</span>
                        <span class="block truncate text-xs text-muted">{{ phrase.text }}</span>
                    </button>
                    <p v-if="filteredPhrases.length === 0" class="px-2 py-1 text-xs text-muted">{{ t('common.none') }}</p>
                </div>
            </div>

            <section class="mt-4">
                <h2 class="text-sm font-semibold">{{ t('lab.attachments') }}</h2>
                <EmptyState v-if="attachments.length === 0" icon="image" :title="t('lab.attachment_empty')" />
                <ul v-else class="mt-2 divide-y divide-border rounded-lg border border-border bg-surface">
                    <li v-for="attachment in attachments" :key="attachment.id" class="flex items-center gap-3 px-3 py-1.5 text-[13px]">
                        <AppIcon :name="attachment.name.toLowerCase().endsWith('.pdf') ? 'file-pdf' : 'image'" class="shrink-0 text-muted" />
                        <span class="min-w-0 flex-1 truncate">{{ attachment.name }}</span>
                        <span class="text-xs text-muted tabular-nums">{{ formatFileSize(attachment.size) }}</span>
                        <UiButton
                            variant="ghost"
                            size="xs"
                            icon="download"
                            :href="route('admin.lab.attachments.download', attachment.id)"
                            :aria-label="t('lab.attachment_download')"
                        />
                        <UiButton
                            v-if="editable"
                            variant="ghost-danger"
                            size="xs"
                            icon="trash"
                            :aria-label="t('common.delete')"
                            @click="removeAttachment(attachment)"
                        />
                    </li>
                </ul>
                <div v-if="editable" class="mt-2">
                    <FileUpload :id="'result-attachment'" v-model="upload" :accept="'image/*,application/pdf'" @update:model-value="uploadAttachment" />
                </div>
            </section>
        </template>

        <CriticalAckDialog v-model:open="ackDialog" @confirm="acknowledge" />
    </div>
</template>
