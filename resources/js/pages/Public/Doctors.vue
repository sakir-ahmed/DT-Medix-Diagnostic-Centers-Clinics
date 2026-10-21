<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatMoney } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

defineProps({
    centerName: { type: String, default: null },
    doctors: { type: Array, default: () => [] },
});

const { t } = useI18n();
</script>

<template>
    <Head>
        <title>{{ t('public.doctors_page_title') }}</title>
        <meta name="description" :content="t('public.doctors_lead')" />
    </Head>
    <PublicLayout :center-name="centerName">
        <section class="py-4">
            <p class="badge-line">{{ t('public.our_doctors') }}</p>
            <h1 class="mt-2 font-display text-2xl font-bold tracking-tight lg:text-3xl">{{ t('public.doctors_page_title') }}</h1>
            <p class="mt-2 max-w-2xl text-[15px] leading-relaxed text-muted">{{ t('public.doctors_lead') }}</p>
        </section>

        <div v-if="doctors.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="doctor in doctors" :key="doctor.id" class="card flex flex-col">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <AppIcon name="user-doctor" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="truncate text-[15px] font-semibold">{{ doctor.name }}</h2>
                        <p class="truncate text-[13px] text-muted">{{ doctor.specialty }}<template v-if="doctor.designation"> - {{ doctor.designation }}</template></p>
                    </div>
                </div>

                <p v-if="doctor.qualifications" class="mt-2.5 text-[13px] text-muted">{{ doctor.qualifications }}</p>
                <p v-if="doctor.is_visiting" class="mt-1.5 inline-flex items-center gap-1 text-xs text-accent">
                    <AppIcon name="clock-rotate-left" size="sm" /> {{ t('doctors.visiting') }}
                </p>

                <div class="mt-auto pt-3">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                        <span v-if="doctor.days?.length" class="inline-flex items-center gap-1">
                            <AppIcon name="calendar-check" size="sm" /> {{ doctor.days.join(', ') }}
                        </span>
                        <span class="font-semibold text-foreground tabular-nums">{{ formatMoney(doctor.consultation_fee) }}</span>
                    </div>
                    <Link :href="route('public.booking', { doctor: doctor.id })" class="btn-primary-lg mt-3 w-full justify-center !h-10 !text-[14px]">
                        <AppIcon name="calendar-check" size="sm" /> {{ t('public.book_with', { name: doctor.name }) }}
                    </Link>
                </div>
            </article>
        </div>

        <div v-else class="card mt-2 text-center text-[14px] text-muted">
            <AppIcon name="user-doctor" size="lg" class="text-muted/50" />
            <p class="mt-2">{{ t('public.doctors_empty') }}</p>
        </div>
    </PublicLayout>
</template>
