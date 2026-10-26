<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CtaBand from '@/components/public/CtaBand.vue';
import PageHero from '@/components/public/PageHero.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatMoney } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

defineProps({
    packages: { type: Array, default: () => [] },
});

const { t } = useI18n();
</script>

<template>
    <Head>
        <title>{{ t('site.packages.title') }}</title>
        <meta name="description" :content="t('site.packages.lead')" />
    </Head>
    <PublicLayout :contained="false">
        <PageHero :badge="t('site.packages.badge')" :title="t('site.packages.title')" :lead="t('site.packages.lead')" icon="boxes-stacked">
            <Link :href="route('public.booking')" class="btn-primary-lg"><AppIcon name="calendar-check" /> {{ t('public.book_appointment') }}</Link>
            <Link :href="route('public.tests')" class="btn-secondary-lg"><AppIcon name="flask" /> {{ t('site.common.view_tests') }}</Link>
        </PageHero>

        <section class="site-container site-section">
            <div v-if="packages.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="pkg in packages" :key="pkg.id" class="site-card flex flex-col">
                    <h2 class="text-[16px] font-semibold">{{ pkg.name }}</h2>
                    <p v-if="pkg.description" class="mt-1 text-[13px] text-muted">{{ pkg.description }}</p>
                    <p class="mt-4 text-xs font-semibold tracking-wide text-muted uppercase">{{ t('site.common.per_package') }}</p>
                    <p class="font-display text-3xl font-bold text-primary tabular-nums">{{ formatMoney(pkg.price) }}</p>
                    <p class="mt-4 text-xs font-semibold tracking-wide text-muted uppercase">{{ t('site.common.includes') }}</p>
                    <ul class="mt-2 flex-1 space-y-1.5 text-[13px] text-muted">
                        <li v-for="test in pkg.tests" :key="test" class="flex items-center gap-1.5">
                            <AppIcon name="circle-check" size="sm" class="shrink-0 text-success" /> {{ test }}
                        </li>
                    </ul>
                    <p class="mt-4 flex items-start gap-1.5 text-xs text-muted"><AppIcon name="circle-info" size="sm" class="mt-0.5 shrink-0 text-accent" /> {{ t('site.packages.save_hint') }}</p>
                    <Link :href="route('public.booking')" class="btn-primary-lg mt-4 w-full justify-center !h-10 !text-[14px]"><AppIcon name="calendar-check" size="sm" /> {{ t('public.book_appointment') }}</Link>
                </article>
            </div>
            <div v-else class="card text-center text-[14px] text-muted">
                <AppIcon name="boxes-stacked" size="lg" class="text-muted/50" />
                <p class="mt-2">{{ t('site.packages.empty') }}</p>
            </div>
        </section>

        <CtaBand :title="t('site.packages.cta_title')" :text="t('site.packages.cta_text')" />
    </PublicLayout>
</template>
