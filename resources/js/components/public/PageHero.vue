<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import { useI18n } from '@/lib/i18n';

defineProps({
    badge: { type: String, default: null },
    title: { type: String, required: true },
    lead: { type: String, default: null },
    icon: { type: String, default: null },
});

const { t } = useI18n();
const site = computed(() => usePage().props.site ?? null);
</script>

<template>
    <section class="site-hero relative overflow-hidden border-b border-border">
        <div class="site-container grid items-center gap-8 py-10 lg:grid-cols-[1fr_20rem] lg:py-14">
            <div>
                <nav :aria-label="t('site.common.home')" class="mb-5 flex items-center gap-1.5 text-xs text-muted">
                    <Link :href="route('home')" class="hover:text-foreground">{{ t('site.common.home') }}</Link>
                    <AppIcon name="chevron-right" size="xs" />
                    <span class="font-medium text-foreground">{{ title }}</span>
                </nav>
                <p v-if="badge" class="badge-line"><AppIcon v-if="icon" :name="icon" size="sm" /> {{ badge }}</p>
                <h1 class="mt-3 font-display text-3xl font-bold tracking-tight lg:text-5xl">{{ title }}</h1>
                <p v-if="lead" class="mt-4 max-w-2xl text-base leading-relaxed text-muted lg:text-lg">{{ lead }}</p>
                <div class="mt-6 flex flex-wrap gap-2.5"><slot /></div>
            </div>

            <aside v-if="site" class="hidden rounded-2xl border border-border bg-surface/90 p-5 shadow-sm backdrop-blur lg:block">
                <p class="text-xs font-semibold tracking-wide text-accent uppercase">{{ t('site.hero.help_title') }}</p>
                <ul class="mt-3 space-y-3 text-[14px]">
                    <li v-if="site.phone" class="flex items-center gap-3">
                        <span class="site-icon h-10 w-10"><AppIcon name="phone" /></span>
                        <a :href="'tel:' + site.phone" class="font-semibold tabular-nums hover:text-primary">{{ site.phone }}</a>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="site-icon h-10 w-10"><AppIcon name="clock" /></span>
                        <span class="text-muted">{{ t('site.topbar.hours') }}</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="site-icon h-10 w-10"><AppIcon name="shield-halved" /></span>
                        <span class="text-muted">{{ t('site.hero.private') }}</span>
                    </li>
                </ul>
                <Link :href="route('public.booking')" class="btn-primary-lg mt-4 w-full justify-center !h-10 !text-[14px]"><AppIcon name="calendar-check" size="sm" /> {{ t('public.book_appointment') }}</Link>
            </aside>
        </div>
    </section>
</template>
