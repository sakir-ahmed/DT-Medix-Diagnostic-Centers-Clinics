<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CtaBand from '@/components/public/CtaBand.vue';
import PageHero from '@/components/public/PageHero.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const { t } = useI18n();
const site = computed(() => usePage().props.site ?? {});
const branches = computed(() => site.value.branches ?? []);
const bring = ['id', 'rx', 'old'];

const directions = (address) => 'https://www.google.com/maps/search/?api=1&query='.concat(encodeURIComponent(address));
</script>

<template>
    <Head>
        <title>{{ t('site.locations.title') }}</title>
        <meta name="description" :content="t('site.locations.lead')" />
    </Head>
    <PublicLayout :contained="false">
        <PageHero :badge="t('site.locations.badge')" :title="t('site.locations.title')" :lead="t('site.locations.lead')" icon="location-dot">
            <Link :href="route('public.contact')" class="btn-secondary-lg"><AppIcon name="headset" /> {{ t('site.nav.contact') }}</Link>
        </PageHero>

        <section class="site-container site-section">
            <div v-if="branches.length" class="grid gap-4 md:grid-cols-2">
                <article v-for="branch in branches" :key="branch.name" class="site-card">
                    <div class="flex items-center gap-3">
                        <span class="site-icon"><AppIcon name="building" size="lg" /></span>
                        <h2 class="text-[16px] font-semibold">{{ branch.name }}</h2>
                    </div>
                    <dl class="mt-4 space-y-2.5 text-[14px]">
                        <div v-if="branch.address" class="flex items-start gap-2">
                            <dt class="sr-only">{{ t('site.locations.address') }}</dt>
                            <AppIcon name="location-dot" size="sm" class="mt-0.5 shrink-0 text-accent" />
                            <dd class="text-muted">{{ branch.address }}</dd>
                        </div>
                        <div v-if="branch.phone" class="flex items-center gap-2">
                            <dt class="sr-only">{{ t('site.locations.phone') }}</dt>
                            <AppIcon name="phone" size="sm" class="shrink-0 text-accent" />
                            <dd><a :href="'tel:' + branch.phone" class="font-medium tabular-nums hover:text-primary">{{ branch.phone }}</a></dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <AppIcon name="clock" size="sm" class="shrink-0 text-accent" />
                            <dd class="text-muted">{{ t('site.locations.hours') }}</dd>
                        </div>
                    </dl>
                    <a v-if="branch.address" :href="directions(branch.address)" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-1 text-[13px] font-semibold text-primary hover:underline">{{ t('site.common.get_directions') }} <AppIcon name="arrow-right" size="sm" /></a>
                </article>
            </div>
            <div v-else class="card text-center text-[14px] text-muted">
                <AppIcon name="location-dot" size="lg" class="text-muted/50" />
                <p class="mt-2">{{ t('site.locations.empty') }}</p>
            </div>
        </section>

        <section class="site-container site-section !pt-0">
            <h2 class="site-h2">{{ t('site.locations.bring_title') }}</h2>
            <ul class="mt-5 grid gap-3 sm:grid-cols-3">
                <li v-for="item in bring" :key="item" class="site-card flex items-start gap-2.5 text-[14px] text-muted">
                    <AppIcon name="circle-check" class="mt-0.5 shrink-0 text-success" /> {{ t(`site.locations.bring.${item}`) }}
                </li>
            </ul>
        </section>

        <CtaBand :title="t('site.common.ask_us')" :text="t('site.common.ask_us_text')" />
    </PublicLayout>
</template>
