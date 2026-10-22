<script setup>
import { Head } from '@inertiajs/vue3';
import PageHero from '@/components/public/PageHero.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const { t } = useI18n();
const sections = ['collect', 'use', 'messages', 'access', 'retention', 'contact'];
</script>

<template>
    <Head>
        <title>{{ t('site.privacy.title') }}</title>
        <meta name="description" :content="t('site.privacy.lead')" />
    </Head>
    <PublicLayout :contained="false">
        <PageHero :badge="t('site.privacy.badge')" :title="t('site.privacy.title')" :lead="t('site.privacy.lead')" icon="shield-halved" />

        <section class="site-container site-section">
            <div class="mx-auto max-w-3xl space-y-8">
                <article v-for="(section, index) in sections" :key="section" class="flex gap-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 font-display text-sm font-bold text-primary tabular-nums">{{ index + 1 }}</span>
                    <div>
                        <h2 class="font-display text-lg font-bold">{{ t(`site.privacy.sections.${section}.title`) }}</h2>
                        <p class="mt-1.5 text-[15px] leading-relaxed text-muted">{{ t(`site.privacy.sections.${section}.text`) }}</p>
                    </div>
                </article>
            </div>
        </section>
    </PublicLayout>
</template>
