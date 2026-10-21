<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatMoney } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const props = defineProps({
    center: { type: Object, default: null },
});

const { t, locale } = useI18n();

const features = ['patients', 'lab', 'reports', 'billing', 'commission', 'messaging'];
const icons = { patients: 'hospital-user', lab: 'flask', reports: 'file-medical', billing: 'cash-register', commission: 'hand-holding-dollar', messaging: 'comment-sms' };
const departmentIcons = { pathology: 'vial', imaging: 'display', cardiology: 'heart-pulse', other: 'stethoscope' };

const name = (item) => (locale.value === 'bn' && item.name_bn ? item.name_bn : item.name);
</script>

<template>
    <Head>
        <title>{{ center?.name ?? 'DT Medix' }}</title>
        <meta name="description" :content="center ? t('public.center_description', { name: center.name }) : t('public.tagline')" />
    </Head>
    <PublicLayout :center-name="center?.name">
        <!-- Center website -->
        <template v-if="center">
            <section class="grid items-center gap-6 py-6 lg:grid-cols-2">
                <div>
                    <p class="badge-line">{{ t('public.welcome_to') }}</p>
                    <h1 class="mt-2 font-display text-3xl font-bold tracking-tight lg:text-4xl">{{ name(center) }}</h1>
                    <p class="mt-3 text-[15px] leading-relaxed text-muted">{{ t('public.center_lead') }}</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <Link v-if="route().has('public.booking')" :href="route('public.booking')" class="btn-primary-lg"><AppIcon name="calendar-check" /> {{ t('public.book_appointment') }}</Link>
                        <a v-if="center.phone" :href="'tel:'.concat(center.phone)" class="btn-secondary-lg"><AppIcon name="phone" /> {{ t('public.call_now') }}</a>
                        <Link v-if="route().has('portal.login')" :href="route('portal.login')" class="btn-secondary-lg"><AppIcon name="file-medical" /> {{ t('public.my_reports') }}</Link>
                    </div>
                </div>
                <img src="/images/public/hero-diagnostics.svg" alt="" width="640" height="480" class="w-full max-w-lg justify-self-center" />
            </section>

            <section class="grid grid-cols-3 gap-3">
                <div v-for="(value, key) in center.stats" :key="key" class="stat-card">
                    <span class="stat-value">{{ value }}</span>
                    <span class="stat-label">{{ t(`public.stat_${key}`) }}</span>
                </div>
            </section>

            <section class="mt-10">
                <div class="flex items-end justify-between gap-3">
                    <h2 class="section-title">{{ t('public.popular_tests') }}</h2>
                    <Link :href="route('login')" class="text-[13px] font-medium text-primary hover:underline">{{ t('public.staff_sign_in') }}</Link>
                </div>
                <div class="mt-3 overflow-hidden rounded-lg border border-border bg-surface">
                    <ul class="divide-y divide-border">
                        <li v-for="test in center.tests" :key="test.name" class="flex items-center gap-3 px-4 py-2.5">
                            <AppIcon :name="'flask'" class="shrink-0 text-accent" />
                            <span class="min-w-0 flex-1 truncate text-[14px] font-medium">{{ test.name }}</span>
                            <span class="hidden items-center gap-1 text-xs text-muted sm:flex"><AppIcon name="clock" size="sm" /> {{ t('public.report_in', { hours: test.tat_hours }) }}</span>
                            <span class="w-24 text-right text-[14px] font-semibold tabular-nums">{{ formatMoney(test.price) }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <section v-if="center.departments?.length" class="mt-10">
                <h2 class="section-title">{{ t('public.departments_title') }}</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="department in center.departments" :key="department.name" class="card flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                            <AppIcon :name="departmentIcons[department.category] ?? 'stethoscope'" size="lg" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-[14px] font-semibold">{{ department.name }}</p>
                            <p class="text-xs text-muted">{{ t('public.tests_count', { count: department.tests_count }) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="center.doctors?.length" class="mt-10">
                <h2 class="section-title">{{ t('public.our_doctors') }}</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="doctor in center.doctors" :key="doctor.name" class="card">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-accent/15 text-accent">
                                <AppIcon name="user-doctor" size="lg" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-[14px] font-semibold">{{ doctor.name }}</p>
                                <p class="truncate text-xs text-muted">{{ doctor.specialty }}</p>
                            </div>
                        </div>
                        <p v-if="doctor.qualifications" class="mt-2 truncate text-xs text-muted">{{ doctor.qualifications }}</p>
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span v-if="doctor.days?.length" class="inline-flex items-center gap-1 text-muted"><AppIcon name="calendar-check" size="sm" /> {{ doctor.days.join(', ') }}</span>
                            <span v-if="Number(doctor.consultation_fee) > 0" class="font-semibold tabular-nums">{{ formatMoney(doctor.consultation_fee) }}</span>
                        </div>
                    </article>
                </div>
            </section>

            <section id="contact" class="mt-10 grid gap-3 sm:grid-cols-2">
                <div class="card">
                    <h2 class="section-title-sm">{{ t('public.contact_title') }}</h2>
                    <ul class="mt-2 space-y-2 text-[14px]">
                        <li v-if="center.address" class="flex items-start gap-2"><AppIcon name="location-dot" class="mt-0.5 shrink-0 text-accent" /> {{ center.address }}</li>
                        <li v-if="center.phone" class="flex items-center gap-2"><AppIcon name="phone" class="shrink-0 text-accent" /> <span class="tabular-nums">{{ center.phone }}</span></li>
                        <li class="flex items-center gap-2"><AppIcon name="clock" class="shrink-0 text-accent" /> {{ t('public.opening_hours') }}</li>
                    </ul>
                </div>
                <div class="card bg-primary text-primary-foreground">
                    <h2 class="section-title-sm">{{ t('public.report_delivery_title') }}</h2>
                    <p class="mt-2 text-[14px] leading-relaxed opacity-90">{{ t('public.report_delivery_text') }}</p>
                    <Link v-if="route().has('portal.login')" :href="route('portal.login')" class="mt-4 inline-flex h-11 items-center gap-2 rounded-md bg-white/15 px-4 font-medium hover:bg-white/25">
                        <AppIcon name="file-circle-check" /> {{ t('public.my_reports') }}
                    </Link>
                </div>
            </section>
        </template>

        <!-- Product page (central host) -->
        <template v-else>
            <section class="grid items-center gap-6 py-6 lg:grid-cols-2">
                <div>
                    <p class="badge-line">{{ t('public.product_of') }}</p>
                    <h1 class="mt-2 font-display text-3xl font-bold tracking-tight lg:text-4xl">DT Medix</h1>
                    <p class="mt-3 text-[15px] leading-relaxed text-muted">{{ t('public.tagline') }}</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <Link :href="route('login')" class="btn-primary-lg"><AppIcon name="right-from-bracket" /> {{ t('auth.sign_in') }}</Link>
                    </div>
                </div>
                <img src="/images/public/hero-diagnostics.svg" alt="" width="640" height="480" class="w-full max-w-lg justify-self-center" />
            </section>

            <section class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="feature in features" :key="feature" class="card flex items-start gap-3">
                    <AppIcon :name="icons[feature]" size="lg" class="mt-0.5 text-accent" />
                    <div>
                        <h2 class="text-sm font-semibold">{{ t(`public.features.${feature}.title`) }}</h2>
                        <p class="text-[13px] text-muted">{{ t(`public.features.${feature}.text`) }}</p>
                    </div>
                </div>
            </section>
        </template>
    </PublicLayout>
</template>
