<script setup>
import { Head } from '@inertiajs/vue3';
import CtaBand from '@/components/public/CtaBand.vue';
import PageHero from '@/components/public/PageHero.vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const { t } = useI18n();

const prep = [
    { key: 'fasting', icon: 'droplet' },
    { key: 'urine', icon: 'vial' },
    { key: 'imaging', icon: 'x-ray' },
    { key: 'ecg', icon: 'heart-pulse' },
    { key: 'general', icon: 'circle-info' },
];
const bring = ['prescription', 'reports', 'mobile', 'medicines', 'receipt'];
const flags = [
    { key: 'normal', icon: 'circle-check', tone: 'text-success bg-success/10' },
    { key: 'high', icon: 'arrow-up', tone: 'text-warning bg-warning/10' },
    { key: 'low', icon: 'arrow-down', tone: 'text-info bg-info/10' },
    { key: 'critical', icon: 'triangle-exclamation', tone: 'text-danger bg-danger/10' },
];
const after = [
    { key: 'results', icon: 'comment-sms' },
    { key: 'doctor', icon: 'user-doctor' },
    { key: 'followup', icon: 'calendar-check' },
];
</script>

<template>
    <Head>
        <title>{{ t('site.guide.title') }}</title>
        <meta name="description" :content="t('site.guide.lead')" />
    </Head>
    <PublicLayout :contained="false">
        <PageHero :badge="t('site.guide.badge')" :title="t('site.guide.title')" :lead="t('site.guide.lead')" icon="clipboard-check" />

        <section class="site-container site-section">
            <SectionHeading :title="t('site.guide.prep_title')" :lead="t('site.guide.prep_lead')" />
            <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <article v-for="item in prep" :key="item.key" class="site-card">
                    <span class="site-icon"><AppIcon :name="item.icon" size="lg" /></span>
                    <h3 class="mt-3 text-[15px] font-semibold">{{ t(`site.guide.prep.${item.key}.title`) }}</h3>
                    <p class="mt-1.5 text-[14px] leading-relaxed text-muted">{{ t(`site.guide.prep.${item.key}.text`) }}</p>
                </article>
                <article class="rounded-xl bg-primary p-5 text-primary-foreground">
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-white/15"><AppIcon name="notes-medical" size="lg" /></span>
                    <h3 class="mt-3 text-[15px] font-semibold">{{ t('site.guide.bring_title') }}</h3>
                    <ul class="mt-2 space-y-1.5 text-[13px] opacity-95">
                        <li v-for="item in bring" :key="item" class="flex items-start gap-2"><AppIcon name="check" size="sm" class="mt-0.5 shrink-0" /> {{ t(`site.guide.bring.${item}`) }}</li>
                    </ul>
                </article>
            </div>
        </section>

        <section class="border-y border-border bg-subtle/50">
            <div class="site-container site-section">
                <SectionHeading :title="t('site.guide.read_title')" :lead="t('site.guide.read_lead')" />
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="flag in flags" :key="flag.key" class="rounded-xl border border-border bg-surface p-5">
                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[13px] font-semibold" :class="flag.tone"><AppIcon :name="flag.icon" size="sm" /> {{ t(`site.guide.flags.${flag.key}.title`) }}</span>
                        <p class="mt-3 text-[14px] leading-relaxed text-muted">{{ t(`site.guide.flags.${flag.key}.text`) }}</p>
                    </div>
                </div>
                <p class="mt-5 flex items-start gap-2 rounded-xl border border-border bg-surface p-4 text-[13px] leading-relaxed text-muted"><AppIcon name="circle-info" class="mt-0.5 shrink-0 text-accent" /> {{ t('site.guide.read_note') }}</p>
            </div>
        </section>

        <section class="site-container site-section">
            <SectionHeading :title="t('site.guide.after_title')" center />
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <div v-for="item in after" :key="item.key" class="site-card text-center">
                    <span class="site-icon mx-auto"><AppIcon :name="item.icon" size="xl" /></span>
                    <h3 class="mt-3 text-[15px] font-semibold">{{ t(`site.guide.after.${item.key}.title`) }}</h3>
                    <p class="mt-1 text-[13px] leading-relaxed text-muted">{{ t(`site.guide.after.${item.key}.text`) }}</p>
                </div>
            </div>
        </section>

        <CtaBand :title="t('site.home.cta_title')" :text="t('site.home.cta_text')" />
        <div class="h-4" />
    </PublicLayout>
</template>
