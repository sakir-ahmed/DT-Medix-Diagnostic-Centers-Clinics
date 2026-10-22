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

const values = [
    { key: 'accuracy', icon: 'microscope' },
    { key: 'respect', icon: 'user-check' },
    { key: 'honesty', icon: 'tag' },
    { key: 'care', icon: 'hand-holding-heart' },
];
const quality = ['barcode', 'ranges', 'signed', 'amend', 'critical'];
const facilities = [
    { key: 'pathology', icon: 'vial' },
    { key: 'imaging', icon: 'x-ray' },
    { key: 'cardiology', icon: 'heart-pulse' },
    { key: 'consultation', icon: 'user-doctor' },
    { key: 'collection', icon: 'truck-medical' },
    { key: 'reception', icon: 'receipt' },
];
</script>

<template>
    <Head>
        <title>{{ t('site.about.title') }}</title>
        <meta name="description" :content="t('site.about.lead')" />
    </Head>
    <PublicLayout :contained="false">
        <PageHero :badge="t('site.about.badge')" :title="t('site.about.title')" :lead="t('site.about.lead')" icon="award" />

        <section class="site-container site-section">
            <div class="grid gap-8 lg:grid-cols-2">
                <div>
                    <SectionHeading :title="t('site.about.story_title')" />
                    <p class="mt-4 text-[15px] leading-relaxed text-muted">{{ t('site.about.story_p1') }}</p>
                    <p class="mt-3 text-[15px] leading-relaxed text-muted">{{ t('site.about.story_p2') }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="site-card">
                        <span class="site-icon"><AppIcon name="rocket" size="lg" /></span>
                        <h3 class="mt-3 text-[15px] font-semibold">{{ t('site.about.mission_title') }}</h3>
                        <p class="mt-1 text-[14px] leading-relaxed text-muted">{{ t('site.about.mission_text') }}</p>
                    </div>
                    <div class="site-card">
                        <span class="site-icon"><AppIcon name="eye" size="lg" /></span>
                        <h3 class="mt-3 text-[15px] font-semibold">{{ t('site.about.vision_title') }}</h3>
                        <p class="mt-1 text-[14px] leading-relaxed text-muted">{{ t('site.about.vision_text') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-border bg-subtle/50">
            <div class="site-container site-section">
                <SectionHeading :title="t('site.about.values_title')" center />
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="item in values" :key="item.key" class="site-card text-center">
                        <span class="site-icon mx-auto"><AppIcon :name="item.icon" size="xl" /></span>
                        <h3 class="mt-3 text-[15px] font-semibold">{{ t(`site.about.values.${item.key}.title`) }}</h3>
                        <p class="mt-1 text-[13px] leading-relaxed text-muted">{{ t(`site.about.values.${item.key}.text`) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="site-container site-section">
            <div class="grid gap-10 lg:grid-cols-2">
                <div>
                    <SectionHeading :title="t('site.about.quality_title')" />
                    <ul class="mt-5 space-y-3">
                        <li v-for="item in quality" :key="item" class="flex items-start gap-3 text-[14px] leading-relaxed text-muted">
                            <AppIcon name="circle-check" class="mt-0.5 shrink-0 text-success" /> {{ t(`site.about.quality.${item}`) }}
                        </li>
                    </ul>
                </div>
                <div>
                    <SectionHeading :title="t('site.about.facilities_title')" />
                    <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                        <li v-for="item in facilities" :key="item.key" class="flex items-center gap-3 rounded-xl border border-border bg-surface p-3 text-[13px]">
                            <span class="site-icon h-10 w-10"><AppIcon :name="item.icon" /></span>
                            {{ t(`site.about.facilities.${item.key}`) }}
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <CtaBand :title="t('site.home.cta_title')" :text="t('site.home.cta_text')" />
        <div class="h-4" />
    </PublicLayout>
</template>
