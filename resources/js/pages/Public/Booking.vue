<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatClock, formatMoney } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const props = defineProps({
    centerName: { type: String, default: null },
    doctors: { type: Array, default: () => [] },
    selectedDoctorId: { type: Number, default: null },
    booked: { type: Object, default: null },
});

const { t, locale } = useI18n();
const intlLocale = computed(() => (locale.value === 'bn' ? 'bn-BD' : 'en-GB'));

const form = useForm({
    doctor_id: props.selectedDoctorId,
    date: null,
    time: null,
    name: '',
    mobile: '',
    sex: '',
    age_years: '',
});

const days = computed(() => {
    const list = [];
    const today = new Date();
    for (let offset = 0; offset < 14; offset += 1) {
        const date = new Date(today.getFullYear(), today.getMonth(), today.getDate() + offset);
        list.push({
            value: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`,
            weekday: date.toLocaleDateString(intlLocale.value, { weekday: 'short' }),
            label: date.toLocaleDateString(intlLocale.value, { day: 'numeric', month: 'short' }),
        });
    }
    return list;
});

const selectedDoctor = computed(() => props.doctors.find((doctor) => doctor.id === Number(form.doctor_id)) ?? null);

const slots = ref([]);
const slotsLoading = ref(false);
const slotsFailed = ref(false);
let slotsRequest = 0;

async function loadSlots() {
    form.time = null;
    slots.value = [];

    if (! form.doctor_id || ! form.date) {
        return;
    }

    const requestId = slotsRequest + 1;
    slotsRequest = requestId;
    slotsLoading.value = true;
    slotsFailed.value = false;

    try {
        const params = new URLSearchParams({ doctor: String(form.doctor_id), date: form.date });
        const response = await fetch(`${route('public.booking.slots')}?${params.toString()}`);
        if (! response.ok) {
            throw new Error('request failed');
        }
        if (requestId !== slotsRequest) {
            return;
        }
        const payload = await response.json();
        slots.value = payload.slots ?? [];
    } catch {
        if (requestId === slotsRequest) {
            slotsFailed.value = true;
        }
    } finally {
        if (requestId === slotsRequest) {
            slotsLoading.value = false;
        }
    }
}

watch(() => [form.doctor_id, form.date], loadSlots);

const sexOptions = [
    { value: 'male', label: t('patients.sex.male') },
    { value: 'female', label: t('patients.sex.female') },
    { value: 'other', label: t('patients.sex.other') },
];

const canSubmit = computed(() => form.doctor_id && form.date && form.time && form.name && form.mobile && form.sex && form.age_years !== '');

function submit() {
    form.post(route('public.booking.store'), {
        preserveState: true,
    });
}

function bookAnother() {
    window.location.href = route('public.booking');
}

function formatBookingDate(value) {
    if (! value) {
        return '';
    }
    return new Date(`${value}T00:00:00`).toLocaleDateString(intlLocale.value, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <Head>
        <title>{{ t('public.booking_page_title') }}</title>
        <meta name="description" :content="t('public.booking_lead')" />
    </Head>
    <PublicLayout :center-name="centerName">
        <!-- Confirmation -->
        <template v-if="booked">
            <section class="mx-auto max-w-xl py-8">
                <div class="card border-primary/40 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <AppIcon name="circle-check" size="2xl" />
                    </span>
                    <h1 class="mt-3 font-display text-2xl font-bold tracking-tight">{{ t('public.booking_confirmed') }}</h1>
                    <p class="mt-1 text-[14px] text-muted">{{ t('public.booking_confirmed_text') }}</p>

                    <div class="mt-5 rounded-lg border border-dashed border-primary/50 bg-primary/5 px-4 py-3">
                        <p class="text-xs font-semibold tracking-wide text-muted uppercase">{{ t('appointments.token') }}</p>
                        <p class="mt-0.5 font-display text-4xl font-bold text-primary tabular-nums">#{{ booked.token_no }}</p>
                    </div>

                    <dl class="mt-4 space-y-2 text-left text-[14px]">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted">{{ t('public.field_doctor') }}</dt>
                            <dd class="text-right font-medium">{{ booked.doctor_name }}<template v-if="booked.doctor_specialty"> - {{ booked.doctor_specialty }}</template></dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted">{{ t('public.field_date') }}</dt>
                            <dd class="font-medium">{{ formatBookingDate(booked.date) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted">{{ t('public.field_time') }}</dt>
                            <dd class="font-medium tabular-nums">{{ formatClock(booked.time) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted">{{ t('public.field_patient_name') }}</dt>
                            <dd class="font-medium">{{ booked.patient_name }} ({{ booked.patient_code }})</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted">{{ t('public.booking_fee') }}</dt>
                            <dd class="font-semibold tabular-nums">{{ formatMoney(booked.fee) }}</dd>
                        </div>
                    </dl>

                    <p class="mt-4 rounded-md bg-subtle px-3 py-2 text-left text-[13px] leading-relaxed text-muted">
                        <AppIcon name="circle-info" size="sm" class="mr-1 text-accent" />
                        {{ t('public.booking_note') }}
                    </p>

                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        <button type="button" class="btn-primary-lg" @click="bookAnother">{{ t('public.booking_another') }}</button>
                        <Link :href="route('home')" class="btn-secondary-lg">{{ t('public.back_home') }}</Link>
                    </div>
                </div>
            </section>
        </template>

        <!-- Booking form -->
        <template v-else>
            <section class="py-4">
                <p class="badge-line">{{ t('public.booking_badge') }}</p>
                <h1 class="mt-2 font-display text-2xl font-bold tracking-tight lg:text-3xl">{{ t('public.booking_page_title') }}</h1>
                <p class="mt-2 max-w-2xl text-[15px] leading-relaxed text-muted">{{ t('public.booking_lead') }}</p>
            </section>

            <form class="mt-2 space-y-6" novalidate @submit.prevent="submit">
                <!-- Step 1: doctor -->
                <fieldset>
                    <legend class="section-title-sm">
                        <span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">1</span>
                        {{ t('public.step_doctor') }}
                    </legend>
                    <div v-if="doctors.length" class="mt-3 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        <button
                            v-for="doctor in doctors"
                            :key="doctor.id"
                            type="button"
                            class="rounded-lg border p-3 text-left transition-colors duration-100 focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none"
                            :class="Number(form.doctor_id) === doctor.id ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-border bg-surface hover:border-accent'"
                            :aria-pressed="Number(form.doctor_id) === doctor.id"
                            @click="form.doctor_id = doctor.id"
                        >
                            <p class="truncate text-[14px] font-semibold">{{ doctor.name }}</p>
                            <p class="truncate text-xs text-muted">{{ doctor.specialty }}</p>
                            <p class="mt-1.5 text-xs font-semibold tabular-nums">{{ formatMoney(doctor.consultation_fee) }}</p>
                        </button>
                    </div>
                    <p v-else class="mt-3 card text-[14px] text-muted">{{ t('public.doctors_empty') }}</p>
                    <p v-if="form.errors.doctor_id" class="mt-2 text-xs text-danger">{{ form.errors.doctor_id }}</p>
                </fieldset>

                <!-- Step 2: date -->
                <fieldset>
                    <legend class="section-title-sm">
                        <span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">2</span>
                        {{ t('public.step_date') }}
                    </legend>
                    <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                        <button
                            v-for="day in days"
                            :key="day.value"
                            type="button"
                            class="flex w-[68px] shrink-0 flex-col items-center gap-0.5 rounded-lg border px-2 py-2 transition-colors duration-100 focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none"
                            :class="form.date === day.value ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-border bg-surface hover:border-accent'"
                            :aria-pressed="form.date === day.value"
                            @click="form.date = day.value"
                        >
                            <span class="text-xs font-medium text-muted">{{ day.weekday }}</span>
                            <span class="text-[13px] font-semibold">{{ day.label }}</span>
                        </button>
                    </div>
                    <p v-if="form.errors.date" class="mt-2 text-xs text-danger">{{ form.errors.date }}</p>
                </fieldset>

                <!-- Step 3: time -->
                <fieldset :disabled="! form.doctor_id || ! form.date">
                    <legend class="section-title-sm">
                        <span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">3</span>
                        {{ t('public.step_time') }}
                    </legend>
                    <div class="card mt-3 min-h-16">
                        <p v-if="slotsLoading" class="flex items-center gap-2 text-[14px] text-muted">
                            <AppIcon name="spinner" spin class="text-accent" /> {{ t('public.slots_loading') }}
                        </p>
                        <p v-else-if="slotsFailed" class="text-[14px] text-muted">{{ t('public.slots_error') }}</p>
                        <p v-else-if="! form.doctor_id || ! form.date" class="text-[14px] text-muted">{{ t('public.slots_pick_first') }}</p>
                        <p v-else-if="slots.length === 0" class="text-[14px] text-muted">{{ t('public.slots_empty') }}</p>
                        <div v-else class="flex flex-wrap gap-2">
                            <button
                                v-for="slot in slots"
                                :key="slot.time"
                                type="button"
                                class="h-10 min-w-20 rounded-md border px-3 text-[14px] font-medium tabular-nums transition-colors duration-100 focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none"
                                :class="form.time === slot.time
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : slot.available
                                        ? 'border-border bg-surface hover:border-accent'
                                        : 'cursor-not-allowed border-border bg-subtle text-muted/50 line-through'"
                                :aria-pressed="form.time === slot.time"
                                :disabled="! slot.available"
                                @click="form.time = slot.time"
                            >
                                {{ formatClock(slot.time) }}
                            </button>
                        </div>
                        <p v-if="form.errors.time" class="mt-2 text-xs text-danger">{{ form.errors.time }}</p>
                    </div>
                </fieldset>

                <!-- Step 4: patient -->
                <fieldset>
                    <legend class="section-title-sm">
                        <span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">4</span>
                        {{ t('public.step_patient') }}
                    </legend>
                    <div class="card mt-3 grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label !text-[13px]" for="booking-name">{{ t('public.field_patient_name') }}</label>
                            <input
                                id="booking-name"
                                v-model="form.name"
                                type="text"
                                name="name"
                                autocomplete="name"
                                class="control !h-10 !text-[14px]"
                                :class="form.errors.name && 'control-invalid'"
                                :aria-invalid="!!form.errors.name"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="label !text-[13px]" for="booking-mobile">{{ t('public.field_mobile') }}</label>
                            <input
                                id="booking-mobile"
                                v-model="form.mobile"
                                type="tel"
                                name="mobile"
                                inputmode="tel"
                                autocomplete="tel"
                                placeholder="01XXXXXXXXX"
                                class="control !h-10 !text-[14px] tabular-nums"
                                :class="form.errors.mobile && 'control-invalid'"
                                :aria-invalid="!!form.errors.mobile"
                            />
                            <p v-if="form.errors.mobile" class="mt-1 text-xs text-danger">{{ form.errors.mobile }}</p>
                            <p v-else class="mt-1 text-xs text-muted">{{ t('public.field_mobile_hint') }}</p>
                        </div>
                        <div>
                            <label class="label !text-[13px]" for="booking-age">{{ t('public.field_age') }}</label>
                            <input
                                id="booking-age"
                                v-model="form.age_years"
                                type="number"
                                name="age_years"
                                inputmode="numeric"
                                min="0"
                                max="120"
                                class="control !h-10 !text-[14px] tabular-nums"
                                :class="form.errors.age_years && 'control-invalid'"
                                :aria-invalid="!!form.errors.age_years"
                            />
                            <p v-if="form.errors.age_years" class="mt-1 text-xs text-danger">{{ form.errors.age_years }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="label !text-[13px]">{{ t('public.field_sex') }}</span>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="option in sexOptions"
                                    :key="option.value"
                                    type="button"
                                    class="h-10 rounded-md border px-4 text-[14px] font-medium transition-colors duration-100 focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none"
                                    :class="form.sex === option.value ? 'border-primary bg-primary/5 text-primary ring-1 ring-primary' : 'border-border bg-surface text-muted hover:border-accent'"
                                    :aria-pressed="form.sex === option.value"
                                    @click="form.sex = option.value"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                            <p v-if="form.errors.sex" class="mt-1 text-xs text-danger">{{ form.errors.sex }}</p>
                        </div>
                    </div>
                </fieldset>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn-primary-lg disabled:cursor-not-allowed disabled:opacity-60" :disabled="! canSubmit || form.processing">
                        <AppIcon v-if="form.processing" name="spinner" spin />
                        <AppIcon v-else name="calendar-check" />
                        {{ t('public.booking_confirm') }}
                    </button>
                    <p v-if="selectedDoctor && form.time" class="text-[13px] text-muted">
                        {{ selectedDoctor.name }} - {{ selectedDoctor.specialty }},
                        {{ form.date ? formatBookingDate(form.date) : '' }} {{ formatClock(form.time) }}
                    </p>
                </div>
            </form>
        </template>
    </PublicLayout>
</template>
