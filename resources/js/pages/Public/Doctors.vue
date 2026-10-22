<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageHero from '@/components/public/PageHero.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatMoney } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const props = defineProps({
    centerName: { type: String, default: null },
    doctors: { type: Array, default: () => [] },
});

const { t } = useI18n();
const search = ref('');
const specialty = ref(null);

const specialties = computed(() => [...new Set(props.doctors.map((doctor) => doctor.specialty).filter(Boolean))].sort());
const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.doctors.filter((doctor) => {
        if (specialty.value && doctor.specialty !== specialty.value) {
            return false;
        }

        return term === '' || [doctor.name, doctor.specialty, doctor.qualifications].some((value) => (value ?? '').toLowerCase().includes(term));
    });
});
</script>

<template>
    <Head>
        <title>{{ t('public.doctors_page_title') }}</title>
        <meta name="description" :content="t('public.doctors_lead')" />
    </Head>
    <PublicLayout :center-name="centerName" :contained="false">
        <PageHero :badge="t('public.our_doctors')" :title="t('public.doctors_page_title')" :lead="t('public.doctors_lead')" icon="user-doctor" />

        <section class="site-container site-section !pt-8">
            <div v-if="doctors.length" class="mb-6 space-y-3">
                <div class="relative max-w-md">
                    <AppIcon name="magnifying-glass" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted" />
                    <input v-model="search" type="search" class="control !h-10 !pl-9 !text-[14px]" :placeholder="t('site.common.search_doctors')" :aria-label="t('site.common.search_doctors')" />
                </div>
                <div v-if="specialties.length > 1" class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-full border px-3.5 py-1.5 text-[13px] font-medium transition-colors" :class="specialty === null ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-surface text-muted hover:bg-subtle'" @click="specialty = null">{{ t('site.common.all') }}</button>
                    <button v-for="item in specialties" :key="item" type="button" class="rounded-full border px-3.5 py-1.5 text-[13px] font-medium transition-colors" :class="specialty === item ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-surface text-muted hover:bg-subtle'" @click="specialty = specialty === item ? null : item">{{ item }}</button>
                </div>
            </div>

            <div v-if="filtered.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="doctor in filtered" :key="doctor.id" class="site-card flex flex-col">
                    <div class="flex items-center gap-3">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <AppIcon name="user-doctor" size="2xl" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate text-[15px] font-semibold">{{ doctor.name }}</h2>
                            <p class="truncate text-[13px] text-muted">{{ doctor.specialty }}<template v-if="doctor.designation"> - {{ doctor.designation }}</template></p>
                        </div>
                    </div>

                    <p v-if="doctor.qualifications" class="mt-3 text-[13px] text-muted">{{ doctor.qualifications }}</p>
                    <p v-if="doctor.is_visiting" class="mt-1.5 inline-flex items-center gap-1 text-xs text-accent">
                        <AppIcon name="clock-rotate-left" size="sm" /> {{ t('doctors.visiting') }}
                    </p>

                    <div class="mt-auto pt-4">
                        <dl class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-t border-border pt-3 text-xs text-muted">
                            <div v-if="doctor.days?.length" class="inline-flex items-center gap-1">
                                <dt class="sr-only">{{ t('site.common.available_on') }}</dt>
                                <dd class="inline-flex items-center gap-1"><AppIcon name="calendar-days" size="sm" /> {{ doctor.days.join(', ') }}</dd>
                            </div>
                            <div>
                                <dt class="sr-only">{{ t('site.common.consultation_fee') }}</dt>
                                <dd class="text-[14px] font-semibold text-foreground tabular-nums">{{ formatMoney(doctor.consultation_fee) }}</dd>
                            </div>
                        </dl>
                        <Link :href="route('public.booking', { doctor: doctor.id })" class="btn-primary-lg mt-3 w-full justify-center !h-10 !text-[14px]">
                            <AppIcon name="calendar-check" size="sm" /> {{ t('public.book_with', { name: doctor.name }) }}
                        </Link>
                    </div>
                </article>
            </div>

            <div v-else-if="doctors.length" class="card text-center text-[14px] text-muted">
                <AppIcon name="magnifying-glass" size="lg" class="text-muted/50" />
                <p class="mt-2">{{ t('site.common.no_results') }}</p>
            </div>

            <div v-else class="card text-center text-[14px] text-muted">
                <AppIcon name="user-doctor" size="lg" class="text-muted/50" />
                <p class="mt-2">{{ t('public.doctors_empty') }}</p>
            </div>
        </section>
    </PublicLayout>
</template>
