<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CtaBand from '@/components/public/CtaBand.vue';
import FaqList from '@/components/public/FaqList.vue';
import PageHero from '@/components/public/PageHero.vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const { t } = useI18n();
const page = usePage();
const site = computed(() => page.props.site ?? {});
const branches = computed(() => site.value.branches ?? []);
const before = ['id', 'fasting', 'early', 'booking'];
const quick = [
    { key: 'book', icon: 'calendar-check', href: 'public.booking' },
    { key: 'home', icon: 'house-chimney-medical', href: 'public.home-collection' },
    { key: 'prices', icon: 'tag', href: 'public.tests' },
    { key: 'reports', icon: 'file-circle-check', href: 'public.reports' },
];
const hours = ['lab', 'reception', 'reports'];

const directions = (address) => 'https://www.google.com/maps/search/?api=1&query='.concat(encodeURIComponent(address));
</script>

<template>
    <Head>
        <title>{{ t('site.contact.title') }}</title>
        <meta name="description" :content="t('site.contact.lead')" />
    </Head>
    <PublicLayout :contained="false">
        <PageHero :badge="t('site.contact.badge')" :title="t('site.contact.title')" :lead="t('site.contact.lead')" icon="headset">
            <Link :href="route('public.booking')" class="btn-primary-lg"><AppIcon name="calendar-check" /> {{ t('public.book_appointment') }}</Link>
            <a v-if="site.phone" :href="'tel:' + site.phone" class="btn-secondary-lg"><AppIcon name="phone" /> {{ t('site.common.call_us') }}</a>
        </PageHero>

        <section class="site-container site-section">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="site-card">
                    <span class="site-icon"><AppIcon name="location-dot" size="lg" /></span>
                    <h3 class="mt-3 text-[15px] font-semibold">{{ t('site.contact.address') }}</h3>
                    <p class="mt-1 text-[14px] leading-relaxed text-muted">{{ site.address ?? t('site.contact.not_set') }}</p>
                    <a v-if="site.address" :href="directions(site.address)" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-[13px] font-semibold text-primary hover:underline">{{ t('site.common.get_directions') }} <AppIcon name="arrow-right" size="sm" /></a>
                </div>
                <div class="site-card">
                    <span class="site-icon"><AppIcon name="phone" size="lg" /></span>
                    <h3 class="mt-3 text-[15px] font-semibold">{{ t('site.contact.phone') }}</h3>
                    <p class="mt-1 text-[14px] text-muted tabular-nums">{{ site.phone ?? t('site.contact.not_set') }}</p>
                    <a v-if="site.phone" :href="'tel:' + site.phone" class="mt-3 inline-flex items-center gap-1 text-[13px] font-semibold text-primary hover:underline">{{ t('site.common.call_us') }} <AppIcon name="arrow-right" size="sm" /></a>
                </div>
                <div class="site-card">
                    <span class="site-icon"><AppIcon name="envelope" size="lg" /></span>
                    <h3 class="mt-3 text-[15px] font-semibold">{{ t('site.contact.email') }}</h3>
                    <p class="mt-1 text-[14px] break-all text-muted">{{ site.email ?? t('site.contact.not_set') }}</p>
                    <a v-if="site.email" :href="'mailto:' + site.email" class="mt-3 inline-flex items-center gap-1 text-[13px] font-semibold text-primary hover:underline">{{ t('site.common.email_us') }} <AppIcon name="arrow-right" size="sm" /></a>
                </div>
                <div class="site-card">
                    <span class="site-icon"><AppIcon name="clock" size="lg" /></span>
                    <h3 class="mt-3 text-[15px] font-semibold">{{ t('site.contact.hours') }}</h3>
                    <p class="mt-1 text-[14px] leading-relaxed text-muted">{{ t('site.contact.hours_value') }}</p>
                </div>
            </div>
        </section>

        <section class="site-container pb-2">
            <h2 class="site-h2">{{ t('site.quick.title') }}</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link v-for="item in quick" :key="item.key" :href="route(item.href)" class="site-card flex items-center gap-3">
                    <span class="site-icon"><AppIcon :name="item.icon" size="lg" /></span>
                    <span class="min-w-0">
                        <span class="block text-[14px] font-semibold">{{ t(`site.quick.${item.key}.title`) }}</span>
                        <span class="block text-xs text-muted">{{ t(`site.quick.${item.key}.text`) }}</span>
                    </span>
                </Link>
            </div>
        </section>

        <section class="site-container site-section">
            <div class="grid gap-8 lg:grid-cols-5">
                <div class="lg:col-span-3">
                    <SectionHeading :title="t('site.hours_table.title')" :lead="t('site.hours_table.lead')" />
                    <div class="mt-6 overflow-hidden rounded-xl border border-border bg-surface">
                        <table class="w-full text-left text-[14px]">
                            <thead class="bg-subtle text-xs font-semibold tracking-wide text-muted uppercase">
                                <tr><th class="px-4 py-2.5">{{ t('site.hours_table.service') }}</th><th class="px-4 py-2.5">{{ t('site.hours_table.time') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="row in hours" :key="row"><td class="px-4 py-3 font-medium">{{ t(`site.hours_table.${row}`) }}</td><td class="px-4 py-3 text-muted">{{ t('site.hours_table.daily') }}</td></tr>
                                <tr><td class="px-4 py-3 font-medium">{{ t('site.hours_table.consult') }}</td><td class="px-4 py-3 text-muted">{{ t('site.hours_table.consult_value') }}</td></tr>
                                <tr><td class="px-4 py-3 font-medium">{{ t('site.hours_table.home') }}</td><td class="px-4 py-3 text-muted">{{ t('site.hours_table.home_value') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <div class="site-hero flex h-full flex-col justify-between rounded-xl border border-border bg-surface p-6">
                        <div>
                            <span class="site-icon h-12 w-12"><AppIcon name="map-location-dot" size="xl" /></span>
                            <h3 class="mt-4 font-display text-lg font-bold">{{ t('site.map.title') }}</h3>
                            <p class="mt-2 text-[14px] leading-relaxed text-muted">{{ site.address ?? t('site.contact.not_set') }}</p>
                            <p class="mt-2 text-[13px] leading-relaxed text-muted">{{ t('site.map.text') }}</p>
                        </div>
                        <a v-if="site.address" :href="directions(site.address)" target="_blank" rel="noopener" class="btn-primary-lg mt-5 w-full justify-center"><AppIcon name="location-dot" /> {{ t('site.map.open') }}</a>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="branches.length" class="border-y border-border bg-subtle/50">
            <div class="site-container site-section">
                <SectionHeading :title="t('site.contact.branches_title')" :lead="t('site.contact.branches_lead')" />
                <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <article v-for="branch in branches" :key="branch.name" class="site-card flex flex-col">
                        <span class="site-icon"><AppIcon name="building" size="lg" /></span>
                        <h3 class="mt-3 text-[15px] font-semibold">{{ branch.name }}</h3>
                        <p v-if="branch.address" class="mt-1 text-[14px] leading-relaxed text-muted">{{ branch.address }}</p>
                        <p v-if="branch.phone" class="mt-1 text-[14px] text-muted tabular-nums">{{ branch.phone }}</p>
                        <div class="mt-auto flex flex-wrap gap-4 pt-3">
                            <a v-if="branch.address" :href="directions(branch.address)" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[13px] font-semibold text-primary hover:underline">{{ t('site.common.get_directions') }} <AppIcon name="arrow-right" size="sm" /></a>
                            <a v-if="branch.phone" :href="'tel:' + branch.phone" class="inline-flex items-center gap-1 text-[13px] font-semibold text-primary hover:underline">{{ t('site.common.call_us') }}</a>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="site-container site-section">
            <h2 class="font-display text-lg font-bold">{{ t('site.contact.before_visit_title') }}</h2>
            <ul class="mt-4 grid gap-3 md:grid-cols-2">
                <li v-for="item in before" :key="item" class="flex items-start gap-3 rounded-xl border border-border bg-surface p-4 text-[14px] leading-relaxed text-muted">
                    <AppIcon name="circle-check" class="mt-0.5 shrink-0 text-success" /> {{ t(`site.contact.before_visit.${item}`) }}
                </li>
            </ul>
        </section>

        <section class="border-t border-border bg-subtle/50">
            <div class="site-container site-section">
                <div class="mx-auto max-w-3xl">
                    <SectionHeading :eyebrow="t('site.home.faq_badge')" :title="t('site.home.faq_title')" />
                    <div class="mt-6"><FaqList :limit="5" /></div>
                    <Link :href="route('public.faq')" class="mt-4 inline-flex items-center gap-1 text-[14px] font-semibold text-primary hover:underline">{{ t('public.view_all') }} <AppIcon name="arrow-right" size="sm" /></Link>
                </div>
            </div>
        </section>

        <CtaBand :title="t('site.home.cta_title')" :text="t('site.home.cta_text')" />
        <div class="h-4" />
    </PublicLayout>
</template>
