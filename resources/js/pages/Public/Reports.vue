<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CtaBand from '@/components/public/CtaBand.vue';
import PageHero from '@/components/public/PageHero.vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const { t } = useI18n();

const steps = [
    { key: 'ready', icon: 'file-medical' },
    { key: 'sms', icon: 'comment-sms' },
    { key: 'verify', icon: 'key' },
    { key: 'download', icon: 'download' },
];
const privacy = [
    { key: 'no_results', icon: 'lock' },
    { key: 'expiry', icon: 'hourglass-half' },
    { key: 'logged', icon: 'clipboard-check' },
];
const tips = ['mobile', 'bring', 'due'];
</script>

<template>
    <Head>
        <title>{{ t('site.reports.title') }}</title>
        <meta name="description" :content="t('site.reports.lead')" />
    </Head>
    <PublicLayout :contained="false">
        <PageHero :badge="t('site.reports.badge')" :title="t('site.reports.title')" :lead="t('site.reports.lead')" icon="file-circle-check">
            <Link v-if="route().has('portal.login')" :href="route('portal.login')" class="btn-primary-lg"><AppIcon name="file-medical" /> {{ t('site.reports.portal_cta') }}</Link>
        </PageHero>

        <section class="site-container site-section">
            <SectionHeading :title="t('site.reports.steps_title')" center />
            <ol class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <li v-for="(step, index) in steps" :key="step.key" class="text-center">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-md"><AppIcon :name="step.icon" size="2xl" /></span>
                    <span class="mt-3 block font-display text-xs font-bold text-accent tabular-nums">0{{ index + 1 }}</span>
                    <h3 class="mt-1 text-[15px] font-semibold">{{ t(`site.reports.steps.${step.key}.title`) }}</h3>
                    <p class="mx-auto mt-1 max-w-[16rem] text-[13px] leading-relaxed text-muted">{{ t(`site.reports.steps.${step.key}.text`) }}</p>
                </li>
            </ol>
        </section>

        <section class="border-y border-border bg-subtle/50">
            <div class="site-container site-section">
                <SectionHeading :title="t('site.reports.privacy_title')" center />
                <div class="mt-8 grid gap-4 md:grid-cols-3">
                    <div v-for="item in privacy" :key="item.key" class="site-card">
                        <span class="site-icon"><AppIcon :name="item.icon" size="lg" /></span>
                        <h3 class="mt-3 text-[15px] font-semibold">{{ t(`site.reports.privacy.${item.key}.title`) }}</h3>
                        <p class="mt-1 text-[13px] leading-relaxed text-muted">{{ t(`site.reports.privacy.${item.key}.text`) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="site-container site-section">
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="site-card">
                    <span class="site-icon"><AppIcon name="qrcode" size="lg" /></span>
                    <h3 class="mt-3 font-display text-lg font-bold">{{ t('site.reports.verify_title') }}</h3>
                    <p class="mt-2 text-[14px] leading-relaxed text-muted">{{ t('site.reports.verify_text') }}</p>
                </div>
                <div class="site-card">
                    <span class="site-icon"><AppIcon name="stamp" size="lg" /></span>
                    <h3 class="mt-3 font-display text-lg font-bold">{{ t('site.reports.amended_title') }}</h3>
                    <p class="mt-2 text-[14px] leading-relaxed text-muted">{{ t('site.reports.amended_text') }}</p>
                </div>
            </div>

            <div class="mt-10">
                <h2 class="font-display text-lg font-bold">{{ t('site.reports.tip_title') }}</h2>
                <ul class="mt-4 space-y-3">
                    <li v-for="tip in tips" :key="tip" class="flex items-start gap-3 text-[14px] leading-relaxed text-muted">
                        <AppIcon name="circle-info" class="mt-0.5 shrink-0 text-accent" /> {{ t(`site.reports.tips.${tip}`) }}
                    </li>
                </ul>
            </div>
        </section>

        <CtaBand :title="t('site.home.cta_title')" :text="t('site.home.cta_text')" />
        <div class="h-4" />
    </PublicLayout>
</template>
