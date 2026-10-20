<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import EmptyState from '@/components/ui-kit/EmptyState.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useI18n } from '@/lib/i18n';

/**
 * Touch-friendly collection worklist for the sample-collection tablet
 * (design.md section 1: 44px targets, text-base).
 */
const props = defineProps({
    samples: { type: Object, required: true },
});

const { t, locale } = useI18n();

const rows = computed(() => props.samples.data ?? []);

function collect(sample) {
    router.post(route('admin.samples.collect', sample.id), {}, { preserveScroll: true });
}

function patientName(sample) {
    return locale.value === 'bn' && sample.patient?.name_bn ? sample.patient.name_bn : sample.patient?.name;
}
</script>

<template>
    <div>
        <Head :title="t('lab.collection_title')" />
        <PageHeader :title="t('lab.collection_title')" :description="t('lab.collection_description')" />

        <EmptyState
            v-if="rows.length === 0"
            icon="vial"
            :title="t('lab.collection_empty')"
            :description="t('lab.collection_empty_hint')"
        />

        <ul v-else class="space-y-2">
            <li
                v-for="sample in rows"
                :key="sample.id"
                class="flex flex-col gap-3 rounded-lg border border-border bg-surface p-3 sm:flex-row sm:items-center"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <AppIcon name="vial" class="text-primary" />
                        <span class="font-semibold tabular-nums">{{ sample.sample_code }}</span>
                        <span v-if="sample.urgency === 'urgent'" class="inline-flex h-5 items-center gap-1 rounded-md bg-danger/10 px-2 text-[11px] font-medium text-danger">
                            <AppIcon name="bolt" size="xs" />{{ t('lab.urgent') }}
                        </span>
                    </div>
                    <div class="mt-1 text-base font-medium">{{ patientName(sample) }}</div>
                    <div class="text-sm text-muted">
                        {{ sample.patient?.age_text }} / {{ t(`patients.sex_short.${sample.patient?.sex ?? 'male'}`) }} ·
                        {{ sample.patient?.patient_code }}
                    </div>
                    <div class="mt-1 flex flex-wrap gap-1">
                        <span v-for="test in sample.tests" :key="test.id" class="inline-flex h-5 items-center rounded-md bg-subtle px-2 text-[11px] font-medium text-muted">{{ test.code }}</span>
                        <span v-if="sample.sample_type" class="inline-flex h-5 items-center rounded-md bg-subtle px-2 text-[11px] font-medium text-muted">{{ sample.sample_type }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 gap-2">
                    <UiButton
                        variant="primary"
                        size="touch"
                        icon="check"
                        :aria-label="t('lab.collect')"
                        @click="collect(sample)"
                    >
                        {{ t('lab.collect') }}
                    </UiButton>
                    <UiButton
                        variant="secondary"
                        size="touch"
                        icon="print"
                        :href="route('admin.samples.label', sample.id)"
                        :aria-label="t('lab.print_label')"
                    >
                        {{ t('lab.print_label') }}
                    </UiButton>
                </div>
            </li>
        </ul>

        <p v-if="samples.last_page > 1" class="mt-4 text-center text-[13px] text-muted">
            {{ samples.current_page }} / {{ samples.last_page }}
        </p>
    </div>
</template>
