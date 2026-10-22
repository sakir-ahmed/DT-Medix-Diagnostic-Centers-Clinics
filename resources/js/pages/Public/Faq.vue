<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CtaBand from '@/components/public/CtaBand.vue';
import FaqList from '@/components/public/FaqList.vue';
import PageHero from '@/components/public/PageHero.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const { t } = useI18n();
</script>

<template>
    <Head>
        <title>{{ t('site.faq.title') }}</title>
        <meta name="description" :content="t('site.faq.lead')" />
    </Head>
    <PublicLayout :contained="false">
        <PageHero :badge="t('site.faq.badge')" :title="t('site.faq.title')" :lead="t('site.faq.lead')" icon="circle-question" />

        <section class="site-container site-section">
            <div class="mx-auto max-w-3xl">
                <FaqList :limit="12" />
                <div class="site-card mt-8 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-[15px] font-semibold">{{ t('site.common.ask_us') }}</h2>
                        <p class="mt-1 text-[13px] text-muted">{{ t('site.common.ask_us_text') }}</p>
                    </div>
                    <Link :href="route('public.contact')" class="btn-primary-lg"><AppIcon name="headset" /> {{ t('site.nav.contact') }}</Link>
                </div>
            </div>
        </section>

        <CtaBand :title="t('site.home.cta_title')" :text="t('site.home.cta_text')" />
        <div class="h-4" />
    </PublicLayout>
</template>
