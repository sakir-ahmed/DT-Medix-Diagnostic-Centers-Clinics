<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CtaBand from '@/components/public/CtaBand.vue';
import FaqList from '@/components/public/FaqList.vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatMoney } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

defineProps({
    center: { type: Object, default: null },
});

const { t, locale } = useI18n();

const trust = [
    { key: 'verified', icon: 'user-check' },
    { key: 'on_time', icon: 'hourglass-half' },
    { key: 'home', icon: 'house-chimney-medical' },
    { key: 'secure', icon: 'shield-halved' },
];
const steps = [
    { key: 'book', icon: 'calendar-check' },
    { key: 'visit', icon: 'location-dot' },
    { key: 'test', icon: 'microscope' },
    { key: 'report', icon: 'file-circle-check' },
];
const why = [
    { key: 'transparent', icon: 'tag' },
    { key: 'verified', icon: 'clipboard-check' },
    { key: 'critical', icon: 'triangle-exclamation' },
    { key: 'privacy', icon: 'lock' },
    { key: 'language', icon: 'language' },
    { key: 'branches', icon: 'map-location-dot' },
];
const departmentIcons = { pathology: 'vial', imaging: 'x-ray', cardiology: 'heart-pulse', other: 'stethoscope' };

const name = (item) => (locale.value === 'bn' && item.name_bn ? item.name_bn : item.name);

const modules = ['patients', 'appointments', 'tests', 'billing', 'lab', 'reports', 'payments', 'commission', 'history', 'messaging', 'dashboard', 'import'];
const moduleIcons = {
    patients: 'hospital-user', appointments: 'calendar-check', tests: 'flask', billing: 'cash-register', lab: 'microscope', reports: 'file-medical',
    payments: 'wallet', commission: 'hand-holding-dollar', history: 'chart-line', messaging: 'comment-sms', dashboard: 'chart-pie', import: 'file-import',
};
const roles = ['owner', 'branch_manager', 'receptionist', 'sample_collector', 'lab_technician', 'pathologist', 'doctor', 'accountant'];
const security = [
    { key: 'isolation', icon: 'building-shield' },
    { key: 'audit', icon: 'clipboard-check' },
    { key: 'privacy', icon: 'lock' },
    { key: 'backup', icon: 'database' },
];
const flow = ['patient', 'appointment', 'order', 'lab', 'report', 'message', 'payment'];
</script>

<template>
    <Head>
        <title>{{ center?.name ?? 'DT Medix' }}</title>
        <meta name="description" :content="center ? t('public.center_description', { name: center.name }) : t('public.tagline')" />
    </Head>
    <PublicLayout :center-name="center?.name" :contained="false">
        <!-- Center website -->
        <template v-if="center">
            <section class="site-hero border-b border-border">
                <div class="site-container grid items-center gap-8 py-10 lg:grid-cols-2 lg:py-16">
                    <div>
                        <p class="badge-line"><AppIcon name="heart-pulse" size="sm" /> {{ t('site.home.badge') }}</p>
                        <h1 class="mt-4 font-display text-4xl leading-tight font-bold tracking-tight lg:text-5xl">{{ t('site.home.title') }}</h1>
                        <p class="mt-4 max-w-xl text-base leading-relaxed text-muted lg:text-lg">{{ t('site.home.lead') }}</p>
                        <div class="mt-6 flex flex-wrap gap-2.5">
                            <Link :href="route('public.booking')" class="btn-primary-xl"><AppIcon name="calendar-check" /> {{ t('public.book_appointment') }}</Link>
                            <Link :href="route('public.tests')" class="btn-secondary-xl"><AppIcon name="flask" /> {{ t('site.common.view_tests') }}</Link>
                        </div>
                        <p class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-1 text-[13px] text-muted">
                            <a v-if="center.phone" :href="'tel:' + center.phone" class="inline-flex items-center gap-1.5 font-medium text-foreground hover:text-primary"><AppIcon name="phone" size="sm" class="text-accent" /> <span class="tabular-nums">{{ center.phone }}</span></a>
                            <span class="inline-flex items-center gap-1.5"><AppIcon name="clock" size="sm" class="text-accent" /> {{ t('site.topbar.hours') }}</span>
                        </p>
                    </div>
                    <img src="/images/public/hero-diagnostics.svg" alt="" width="640" height="480" class="w-full max-w-lg justify-self-center" />
                </div>
            </section>

            <!-- Stats -->
            <section class="site-container -mt-6 sm:-mt-8">
                <div class="grid grid-cols-2 gap-3 rounded-2xl border border-border bg-surface p-3 shadow-sm sm:grid-cols-4">
                    <div v-for="(value, key) in center.stats" :key="key" class="stat-card border-0 bg-transparent">
                        <span class="stat-value">{{ value }}</span>
                        <span class="stat-label">{{ t(`public.stat_${key}`) }}</span>
                    </div>
                </div>
            </section>

            <!-- Trust -->
            <section class="site-container site-section">
                <SectionHeading :title="t('site.home.trust_title')" center />
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="item in trust" :key="item.key" class="site-card text-center">
                        <span class="site-icon mx-auto"><AppIcon :name="item.icon" size="xl" /></span>
                        <h3 class="mt-3 text-[15px] font-semibold">{{ t(`site.home.trust.${item.key}.title`) }}</h3>
                        <p class="mt-1 text-[13px] leading-relaxed text-muted">{{ t(`site.home.trust.${item.key}.text`) }}</p>
                    </div>
                </div>
            </section>

            <!-- Services -->
            <section v-if="center.departments?.length" class="border-y border-border bg-subtle/50">
                <div class="site-container site-section">
                    <SectionHeading :eyebrow="t('site.home.services_badge')" :title="t('site.home.services_title')" :lead="t('site.home.services_lead')">
                        <Link :href="route('public.services')" class="inline-flex items-center gap-1 text-[13px] font-semibold text-primary hover:underline">{{ t('site.common.view_services') }} <AppIcon name="arrow-right" size="sm" /></Link>
                    </SectionHeading>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link v-for="department in center.departments" :key="department.name" :href="route('public.services')" class="site-card flex items-center gap-4">
                            <span class="site-icon h-12 w-12"><AppIcon :name="departmentIcons[department.category] ?? 'stethoscope'" size="xl" /></span>
                            <span class="min-w-0">
                                <span class="block truncate text-[15px] font-semibold">{{ department.name }}</span>
                                <span class="block text-xs text-muted">{{ t('public.tests_count', { count: department.tests_count }) }}</span>
                            </span>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- How it works -->
            <section class="site-container site-section">
                <SectionHeading :eyebrow="t('site.home.how_badge')" :title="t('site.home.how_title')" center />
                <ol class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <li v-for="(step, index) in steps" :key="step.key" class="relative text-center">
                        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-md"><AppIcon :name="step.icon" size="2xl" /></span>
                        <span class="mt-3 block font-display text-xs font-bold text-accent tabular-nums">0{{ index + 1 }}</span>
                        <h3 class="mt-1 text-[15px] font-semibold">{{ t(`site.home.how.${step.key}.title`) }}</h3>
                        <p class="mx-auto mt-1 max-w-[16rem] text-[13px] leading-relaxed text-muted">{{ t(`site.home.how.${step.key}.text`) }}</p>
                    </li>
                </ol>
            </section>

            <!-- Packages -->
            <section v-if="center.packages?.length" class="border-y border-border bg-subtle/50">
                <div class="site-container site-section">
                    <SectionHeading :eyebrow="t('site.home.packages_badge')" :title="t('site.home.packages_title')" :lead="t('site.home.packages_lead')">
                        <Link :href="route('public.tests')" class="inline-flex items-center gap-1 text-[13px] font-semibold text-primary hover:underline">{{ t('public.view_all') }} <AppIcon name="arrow-right" size="sm" /></Link>
                    </SectionHeading>
                    <div class="mt-8 grid gap-4 md:grid-cols-3">
                        <article v-for="pkg in center.packages" :key="pkg.id" class="site-card flex flex-col">
                            <p class="text-xs font-semibold tracking-wide text-accent uppercase">{{ t('site.common.tests_included', { count: pkg.tests.length }) }}</p>
                            <h3 class="mt-1 font-display text-lg font-bold">{{ pkg.name }}</h3>
                            <p class="mt-2 font-display text-3xl font-bold text-primary tabular-nums">{{ formatMoney(pkg.price) }}</p>
                            <ul class="mt-4 space-y-1.5 text-[13px] text-muted">
                                <li v-for="test in pkg.tests" :key="test" class="flex items-center gap-2"><AppIcon name="circle-check" size="sm" class="shrink-0 text-success" /> {{ test }}</li>
                            </ul>
                            <Link :href="route('public.booking')" class="btn-secondary-lg mt-auto w-full justify-center !h-10 !text-[14px]">{{ t('public.book_appointment') }}</Link>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Popular tests -->
            <section class="site-container site-section">
                <SectionHeading :eyebrow="t('site.home.tests_badge')" :title="t('public.popular_tests')">
                    <Link :href="route('public.tests')" class="inline-flex items-center gap-1 text-[13px] font-semibold text-primary hover:underline">{{ t('public.view_all') }} <AppIcon name="arrow-right" size="sm" /></Link>
                </SectionHeading>
                <div class="mt-6 overflow-hidden rounded-xl border border-border bg-surface">
                    <ul class="divide-y divide-border">
                        <li v-for="test in center.tests" :key="test.name" class="flex items-center gap-3 px-4 py-3">
                            <AppIcon name="flask" class="shrink-0 text-accent" />
                            <span class="min-w-0 flex-1 truncate text-[14px] font-medium">{{ test.name }}</span>
                            <span class="hidden items-center gap-1 text-xs text-muted sm:flex"><AppIcon name="clock" size="sm" /> {{ t('public.report_in', { hours: test.tat_hours }) }}</span>
                            <span class="w-24 text-right text-[14px] font-semibold tabular-nums">{{ formatMoney(test.price) }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Doctors -->
            <section v-if="center.doctors?.length" class="border-y border-border bg-subtle/50">
                <div class="site-container site-section">
                    <SectionHeading :eyebrow="t('site.home.doctors_badge')" :title="t('public.our_doctors')" :lead="t('site.home.doctors_lead')">
                        <Link :href="route('public.doctors')" class="inline-flex items-center gap-1 text-[13px] font-semibold text-primary hover:underline">{{ t('public.view_all') }} <AppIcon name="arrow-right" size="sm" /></Link>
                    </SectionHeading>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link v-for="doctor in center.doctors" :key="doctor.id" :href="route('public.booking', { doctor: doctor.id })" class="site-card">
                            <div class="flex items-center gap-3">
                                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-accent/15 text-accent"><AppIcon name="user-doctor" size="2xl" /></span>
                                <div class="min-w-0">
                                    <p class="truncate text-[15px] font-semibold">{{ doctor.name }}</p>
                                    <p class="truncate text-[13px] text-muted">{{ doctor.specialty }}</p>
                                </div>
                            </div>
                            <p v-if="doctor.qualifications" class="mt-3 truncate text-xs text-muted">{{ doctor.qualifications }}</p>
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-border pt-3 text-xs">
                                <span v-if="doctor.days?.length" class="inline-flex items-center gap-1 text-muted"><AppIcon name="calendar-days" size="sm" /> {{ doctor.days.join(', ') }}</span>
                                <span v-if="Number(doctor.consultation_fee) > 0" class="font-semibold tabular-nums">{{ formatMoney(doctor.consultation_fee) }}</span>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Home collection + reports -->
            <section class="site-container site-section">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="site-card flex flex-col">
                        <span class="site-icon"><AppIcon name="house-chimney-medical" size="xl" /></span>
                        <h3 class="mt-4 font-display text-xl font-bold">{{ t('site.home_collection.title') }}</h3>
                        <p class="mt-2 flex-1 text-[14px] leading-relaxed text-muted">{{ t('site.home_collection.lead') }}</p>
                        <Link :href="route('public.home-collection')" class="mt-4 inline-flex items-center gap-1 text-[14px] font-semibold text-primary hover:underline">{{ t('site.common.learn_more') }} <AppIcon name="arrow-right" size="sm" /></Link>
                    </div>
                    <div class="flex flex-col rounded-xl bg-primary p-5 text-primary-foreground">
                        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-white/15"><AppIcon name="file-circle-check" size="xl" /></span>
                        <h3 class="mt-4 font-display text-xl font-bold">{{ t('public.report_delivery_title') }}</h3>
                        <p class="mt-2 flex-1 text-[14px] leading-relaxed opacity-90">{{ t('public.report_delivery_text') }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link :href="route('public.reports')" class="inline-flex h-10 items-center gap-2 rounded-md bg-white/15 px-4 text-[14px] font-medium hover:bg-white/25">{{ t('site.common.learn_more') }}</Link>
                            <Link v-if="route().has('portal.login')" :href="route('portal.login')" class="inline-flex h-10 items-center gap-2 rounded-md bg-white/15 px-4 text-[14px] font-medium hover:bg-white/25"><AppIcon name="file-medical" size="sm" /> {{ t('public.my_reports') }}</Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Why us -->
            <section class="border-y border-border bg-subtle/50">
                <div class="site-container site-section">
                    <SectionHeading :eyebrow="t('site.home.why_badge')" :title="t('site.home.why_title')" center />
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="item in why" :key="item.key" class="flex gap-4 rounded-xl border border-border bg-surface p-5">
                            <span class="site-icon"><AppIcon :name="item.icon" size="lg" /></span>
                            <div>
                                <h3 class="text-[15px] font-semibold">{{ t(`site.home.why.${item.key}.title`) }}</h3>
                                <p class="mt-1 text-[13px] leading-relaxed text-muted">{{ t(`site.home.why.${item.key}.text`) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section class="site-container site-section">
                <div class="grid gap-8 lg:grid-cols-3">
                    <div>
                        <SectionHeading :eyebrow="t('site.home.faq_badge')" :title="t('site.home.faq_title')" />
                        <Link :href="route('public.faq')" class="mt-4 inline-flex items-center gap-1 text-[14px] font-semibold text-primary hover:underline">{{ t('public.view_all') }} <AppIcon name="arrow-right" size="sm" /></Link>
                    </div>
                    <div class="lg:col-span-2"><FaqList :limit="5" /></div>
                </div>
            </section>

            <!-- Contact -->
            <section id="contact" class="site-container pb-2">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="site-card flex items-start gap-3">
                        <span class="site-icon"><AppIcon name="location-dot" size="lg" /></span>
                        <div class="min-w-0 text-[14px]"><p class="font-semibold">{{ t('site.contact.address') }}</p><p class="mt-0.5 text-muted">{{ center.address ?? t('site.contact.not_set') }}</p></div>
                    </div>
                    <div class="site-card flex items-start gap-3">
                        <span class="site-icon"><AppIcon name="phone" size="lg" /></span>
                        <div class="min-w-0 text-[14px]"><p class="font-semibold">{{ t('site.contact.phone') }}</p><p class="mt-0.5 text-muted tabular-nums">{{ center.phone ?? t('site.contact.not_set') }}</p></div>
                    </div>
                    <div class="site-card flex items-start gap-3">
                        <span class="site-icon"><AppIcon name="clock" size="lg" /></span>
                        <div class="min-w-0 text-[14px]"><p class="font-semibold">{{ t('site.contact.hours') }}</p><p class="mt-0.5 text-muted">{{ t('site.contact.hours_value') }}</p></div>
                    </div>
                </div>
            </section>

            <CtaBand :title="t('site.home.cta_title')" :text="t('site.home.cta_text')" />
            <div class="h-4" />
        </template>

        <!-- Product page (before the center profile exists) -->
        <template v-else>
            <section class="site-hero border-b border-border">
                <div class="site-container grid items-center gap-8 py-12 lg:grid-cols-2 lg:py-20">
                    <div>
                        <p class="badge-line">{{ t('public.product_of') }}</p>
                        <h1 class="mt-4 font-display text-4xl leading-tight font-bold tracking-tight lg:text-5xl">DT Medix</h1>
                        <p class="mt-4 max-w-xl text-base leading-relaxed text-muted lg:text-lg">{{ t('product.lead') }}</p>
                        <div class="mt-6 flex flex-wrap gap-2.5">
                            <Link :href="route('login')" class="btn-primary-xl"><AppIcon name="right-from-bracket" /> {{ t('auth.sign_in') }}</Link>
                            <a href="https://datatechbd.com" target="_blank" rel="noopener" class="btn-secondary-xl"><AppIcon name="globe" /> datatechbd.com</a>
                        </div>
                    </div>
                    <img src="/images/public/hero-diagnostics.svg" alt="" width="640" height="480" class="w-full max-w-lg justify-self-center" />
                </div>
            </section>

            <section class="site-container site-section">
                <SectionHeading :eyebrow="t('product.flow_badge')" :title="t('product.flow_title')" center />
                <ol class="mt-8 flex flex-wrap items-center justify-center gap-2">
                    <li v-for="(item, index) in flow" :key="item" class="flex items-center gap-2">
                        <span class="rounded-full border border-border bg-surface px-4 py-2 text-[13px] font-semibold">{{ t(`product.flow.${item}`) }}</span>
                        <AppIcon v-if="index < flow.length - 1" name="arrow-right" size="sm" class="text-accent" />
                    </li>
                </ol>
            </section>

            <section class="border-y border-border bg-subtle/50">
                <div class="site-container site-section">
                    <SectionHeading :eyebrow="t('product.modules_badge')" :title="t('product.modules_title')" :lead="t('product.modules_lead')" />
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="module in modules" :key="module" class="site-card flex items-start gap-3">
                            <span class="site-icon"><AppIcon :name="moduleIcons[module]" size="lg" /></span>
                            <div>
                                <h3 class="text-[15px] font-semibold">{{ t(`product.modules.${module}.title`) }}</h3>
                                <p class="mt-1 text-[13px] leading-relaxed text-muted">{{ t(`product.modules.${module}.text`) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="site-container site-section">
                <SectionHeading :eyebrow="t('product.roles_badge')" :title="t('product.roles_title')" :lead="t('product.roles_lead')" />
                <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="role in roles" :key="role" class="rounded-xl border border-border bg-surface p-4">
                        <h3 class="text-[14px] font-semibold">{{ t(`product.roles.${role}.title`) }}</h3>
                        <p class="mt-1 text-[13px] leading-relaxed text-muted">{{ t(`product.roles.${role}.text`) }}</p>
                    </div>
                </div>
            </section>

            <section class="border-y border-border bg-subtle/50">
                <div class="site-container site-section">
                    <SectionHeading :eyebrow="t('product.security_badge')" :title="t('product.security_title')" center />
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div v-for="item in security" :key="item.key" class="site-card text-center">
                            <span class="site-icon mx-auto"><AppIcon :name="item.icon" size="xl" /></span>
                            <h3 class="mt-3 text-[15px] font-semibold">{{ t(`product.security.${item.key}.title`) }}</h3>
                            <p class="mt-1 text-[13px] leading-relaxed text-muted">{{ t(`product.security.${item.key}.text`) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="site-container site-section">
                <div class="site-hero rounded-2xl border border-border bg-primary p-8 text-primary-foreground lg:p-12">
                    <h2 class="font-display text-2xl font-bold tracking-tight lg:text-3xl">{{ t('product.cta_title') }}</h2>
                    <p class="mt-2 max-w-xl text-[15px] leading-relaxed opacity-90">{{ t('product.cta_text') }}</p>
                    <a href="https://datatechbd.com" target="_blank" rel="noopener" class="mt-5 inline-flex h-12 items-center gap-2 rounded-lg bg-surface px-5 text-[15px] font-semibold text-primary hover:opacity-90"><AppIcon name="globe" /> datatechbd.com</a>
                </div>
            </section>
        </template>
    </PublicLayout>
</template>
