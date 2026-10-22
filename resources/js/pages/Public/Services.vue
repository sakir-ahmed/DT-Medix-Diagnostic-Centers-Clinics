<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CtaBand from '@/components/public/CtaBand.vue';
import PageHero from '@/components/public/PageHero.vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

defineProps({
    departments: { type: Array, default: () => [] },
});

const { t } = useI18n();

const departmentIcons = { pathology: 'vial', imaging: 'x-ray', cardiology: 'heart-pulse', other: 'stethoscope' };
const extras = [
    { key: 'consult', icon: 'user-doctor', href: 'public.doctors' },
    { key: 'home', icon: 'house-chimney-medical', href: 'public.home-collection' },
    { key: 'packages', icon: 'boxes-stacked', href: 'public.tests' },
    { key: 'delivery', icon: 'mobile-screen', href: 'public.reports' },
];
const descriptionKey = (category) => (['pathology', 'imaging', 'cardiology'].includes(category) ? category : 'other');
</script>

<template>
    <Head>
        <title>{{ t('site.services.title') }}</title>
        <meta name="description" :content="t('site.services.lead')" />
    </Head>
    <PublicLayout :contained="false">
        <PageHero :badge="t('site.services.badge')" :title="t('site.services.title')" :lead="t('site.services.lead')" icon="microscope">
            <Link :href="route('public.tests')" class="btn-primary-lg"><AppIcon name="flask" /> {{ t('site.common.view_tests') }}</Link>
        </PageHero>

        <section class="site-container site-section">
            <div class="grid gap-5 md:grid-cols-2">
                <article v-for="department in departments" :key="department.id" class="site-card flex flex-col">
                    <div class="flex items-center gap-4">
                        <span class="site-icon h-14 w-14"><AppIcon :name="departmentIcons[department.category] ?? 'stethoscope'" size="2xl" /></span>
                        <div class="min-w-0">
                            <h2 class="font-display text-lg font-bold">{{ department.name }}</h2>
                            <p class="text-xs font-medium text-accent">{{ t('site.services.tests_available', { count: department.tests_count }) }}</p>
                        </div>
                    </div>
                    <p class="mt-4 text-[14px] leading-relaxed text-muted">{{ t(`site.services.dept_desc.${descriptionKey(department.category)}`) }}</p>
                    <p class="mt-4 text-xs font-semibold tracking-wide text-muted uppercase">{{ t('site.services.includes') }}</p>
                    <ul class="mt-2 space-y-1.5 text-[13px]">
                        <li v-for="test in department.sample_tests" :key="test" class="flex items-center gap-2"><AppIcon name="check" size="sm" class="shrink-0 text-accent" /> {{ test }}</li>
                    </ul>
                    <Link :href="route('public.tests')" class="mt-auto inline-flex items-center gap-1 pt-4 text-[14px] font-semibold text-primary hover:underline">
                        {{ t('site.services.see_all') }} <AppIcon name="arrow-right" size="sm" />
                    </Link>
                </article>
            </div>
        </section>

        <section class="border-y border-border bg-subtle/50">
            <div class="site-container site-section">
                <SectionHeading :title="t('site.services.extra_title')" center />
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link v-for="item in extras" :key="item.key" :href="route(item.href)" class="site-card">
                        <span class="site-icon"><AppIcon :name="item.icon" size="lg" /></span>
                        <h3 class="mt-3 text-[15px] font-semibold">{{ t(`site.services.extra.${item.key}.title`) }}</h3>
                        <p class="mt-1 text-[13px] leading-relaxed text-muted">{{ t(`site.services.extra.${item.key}.text`) }}</p>
                    </Link>
                </div>
            </div>
        </section>

        <CtaBand :title="t('site.home.cta_title')" :text="t('site.home.cta_text')" />
        <div class="h-4" />
    </PublicLayout>
</template>
