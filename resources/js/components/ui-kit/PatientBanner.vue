<script setup>
import { computed } from 'vue';
import { formatAge, localizeDigits } from '@/lib/format';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';
import MoneyText from './MoneyText.vue';

/**
 * Persistent patient identity strip to prevent wrong-patient errors.
 */
const props = defineProps({
    patient: { type: Object, required: true },
    compact: { type: Boolean, default: false },
});

const { t, locale } = useI18n();

const age = computed(() => props.patient.age_text ?? formatAge(props.patient.date_of_birth, t));
const sex = computed(() => (props.patient.sex ? t(`patients.sex_short.${props.patient.sex}`) : ''));
const name = computed(() => (locale.value === 'bn' && props.patient.name_bn ? props.patient.name_bn : props.patient.name));
const hasDue = computed(() => Number(props.patient.due ?? 0) > 0);
</script>

<template>
    <section class="flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border border-border bg-surface px-3 py-2" :aria-label="t('patients.banner')">
        <div class="flex min-w-0 items-center gap-2">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-subtle text-muted">
                <img v-if="patient.photo_url" :src="patient.photo_url" alt="" class="h-full w-full object-cover" />
                <AppIcon v-else name="user" />
            </span>
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold">{{ name }}</div>
                <div class="text-xs text-muted tabular-nums">{{ patient.patient_code }}</div>
            </div>
        </div>
        <div class="text-[13px]"><span class="text-muted">{{ t('patients.age_sex') }}:</span> {{ age }} / {{ sex }}</div>
        <div v-if="patient.mobile_display ?? patient.mobile" class="text-[13px] tabular-nums"><AppIcon name="phone" size="sm" class="text-muted" /> {{ localizeDigits(patient.mobile_display ?? patient.mobile) }}</div>
        <div v-if="patient.blood_group && !compact" class="text-[13px]"><span class="text-muted">{{ t('patients.blood_group') }}:</span> {{ patient.blood_group }}</div>
        <div v-if="patient.allergies" class="inline-flex items-center gap-1 rounded-md bg-danger/10 px-2 py-0.5 text-xs font-medium text-danger" role="alert">
            <AppIcon name="triangle-exclamation" size="sm" />
            {{ t('patients.allergy') }}: {{ patient.allergies }}
        </div>
        <div v-if="hasDue" class="ml-auto inline-flex items-center gap-2 rounded-md bg-warning/15 px-2 py-0.5 text-xs font-medium">
            <AppIcon name="money-bill-wave" size="sm" />
            {{ t('patients.previous_due') }}: <MoneyText :value="patient.due" symbol />
            <slot name="due-action" />
        </div>
        <slot />
    </section>
</template>
