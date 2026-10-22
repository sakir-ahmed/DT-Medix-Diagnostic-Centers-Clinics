<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import CtaBand from '@/components/public/CtaBand.vue';
import PageHero from '@/components/public/PageHero.vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatMoney } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const props = defineProps({
    tests: { type: Array, default: () => [] },
});

const { t } = useI18n();

const steps = [
    { key: 'request', icon: 'phone' },
    { key: 'confirm', icon: 'clipboard-check' },
    { key: 'collect', icon: 'truck-medical' },
    { key: 'report', icon: 'file-circle-check' },
];
const good = ['fasting', 'ready', 'pay', 'privacy', 'time'];
const visibleTests = computed(() => props.tests.slice(0, 24));
</script>

<template>
    <Head>
        <title>{{ t('site.home_collection.title') }}</title>
        <meta name="description" :content="t('site.home_collection.lead')" />
    </Head>
    <PublicLayout :contained="false">
        <PageHero :badge="t('site.home_collection.badge')" :title="t('site.home_collection.title')" :lead="t('site.home_collection.lead')" icon="house-chimney-medical" />

        <section class="site-container site-section">
            <SectionHeading :title="t('site.home_collection.steps_title')" center />
            <ol class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <li v-for="(step, index) in steps" :key="step.key" class="text-center">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-md"><AppIcon :name="step.icon" size="2xl" /></span>
                    <span class="mt-3 block font-display text-xs font-bold text-accent tabular-nums">0{{ index + 1 }}</span>
                    <h3 class="mt-1 text-[15px] font-semibold">{{ t(`site.home_collection.steps.${step.key}.title`) }}</h3>
                    <p class="mx-auto mt-1 max-w-[16rem] text-[13px] leading-relaxed text-muted">{{ t(`site.home_collection.steps.${step.key}.text`) }}</p>
                </li>
            </ol>
        </section>

        <section class="border-y border-border bg-subtle/50">
            <div class="site-container site-section">
                <div class="grid gap-10 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <SectionHeading :title="t('site.home_collection.tests_title')" :lead="t('site.home_collection.tests_lead')" />
                        <div v-if="visibleTests.length" class="mt-6 overflow-hidden rounded-xl border border-border bg-surface">
                            <ul class="divide-y divide-border">
                                <li v-for="test in visibleTests" :key="test.name" class="flex items-center gap-3 px-4 py-2.5">
                                    <AppIcon name="vial" class="shrink-0 text-accent" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[14px] font-medium">{{ test.name }}</p>
                                        <p v-if="test.preparation" class="truncate text-xs text-muted">{{ t('site.home_collection.fasting') }}: {{ test.preparation }}</p>
                                    </div>
                                    <span class="text-[14px] font-semibold tabular-nums">{{ formatMoney(test.price) }}</span>
                                </li>
                            </ul>
                        </div>
                        <div v-else class="card mt-6 text-center text-[14px] text-muted">{{ t('site.home_collection.tests_empty') }}</div>
                    </div>
                    <aside>
                        <h2 class="font-display text-lg font-bold">{{ t('site.home_collection.good_title') }}</h2>
                        <ul class="mt-4 space-y-3">
                            <li v-for="item in good" :key="item" class="flex items-start gap-3 text-[14px] leading-relaxed text-muted">
                                <AppIcon name="circle-info" class="mt-0.5 shrink-0 text-accent" /> {{ t(`site.home_collection.good.${item}`) }}
                            </li>
                        </ul>
                    </aside>
                </div>
            </div>
        </section>

        <CtaBand :title="t('site.home_collection.cta_title')" :text="t('site.home_collection.cta_text')" />
        <div class="h-4" />
    </PublicLayout>
</template>
