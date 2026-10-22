<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import FlashToasts from '@/components/layout/FlashToasts.vue';
import LanguageSwitch from '@/components/ui-kit/LanguageSwitch.vue';
import ThemeToggle from '@/components/ui-kit/ThemeToggle.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import { Toaster } from 'vue-sonner';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    centerName: { type: String, default: null },
    contained: { type: Boolean, default: true },
});

const { t } = useI18n();
const page = usePage();
const menuOpen = ref(false);

const site = computed(() => page.props.site ?? null);
const name = computed(() => site.value?.name ?? props.centerName);
const isCenter = computed(() => Boolean(name.value));
const year = new Date().getFullYear();

const nav = computed(() => [
    { label: t('site.nav.about'), href: route('public.about'), active: route().current('public.about') },
    { label: t('site.nav.services'), href: route('public.services'), active: route().current('public.services') },
    { label: t('public.nav_tests'), href: route('public.tests'), active: route().current('public.tests') },
    { label: t('public.nav_doctors'), href: route('public.doctors'), active: route().current('public.doctors') },
    { label: t('site.nav.home_collection'), href: route('public.home-collection'), active: route().current('public.home-collection') },
    { label: t('site.nav.reports'), href: route('public.reports'), active: route().current('public.reports') },
    { label: t('site.nav.contact'), href: route('public.contact'), active: route().current('public.contact') },
]);

const footerPatients = computed(() => [
    { label: t('public.book_appointment'), href: route('public.booking') },
    { label: t('public.nav_tests'), href: route('public.tests') },
    { label: t('public.nav_doctors'), href: route('public.doctors') },
    { label: t('site.nav.home_collection'), href: route('public.home-collection') },
    { label: t('site.nav.reports'), href: route('public.reports') },
]);

const footerCenter = computed(() => [
    { label: t('site.nav.about'), href: route('public.about') },
    { label: t('site.nav.services'), href: route('public.services') },
    { label: t('site.nav.contact'), href: route('public.contact') },
    { label: t('site.nav.faq'), href: route('public.faq') },
    { label: t('site.nav.privacy'), href: route('public.privacy') },
]);

watch(() => page.url, () => {
    menuOpen.value = false;
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-base">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:m-2 focus:rounded-md focus:bg-primary focus:px-3 focus:py-2 focus:text-primary-foreground">{{ t('site.common.skip') }}</a>

        <div v-if="isCenter" class="hidden bg-primary text-primary-foreground sm:block">
            <div class="site-container flex h-9 items-center justify-between gap-4 text-xs">
                <span class="inline-flex items-center gap-1.5 opacity-90"><AppIcon name="clock" size="sm" /> {{ t('site.topbar.hours') }}</span>
                <span class="hidden opacity-90 md:inline">{{ t('site.topbar.tagline') }}</span>
                <a v-if="site?.phone" :href="'tel:' + site.phone" class="inline-flex items-center gap-1.5 font-semibold hover:underline">
                    <AppIcon name="phone" size="sm" /> <span class="tabular-nums">{{ site.phone }}</span>
                </a>
            </div>
        </div>

        <header class="sticky top-0 z-30 border-b border-border bg-surface/95 backdrop-blur">
            <div class="site-container flex h-16 items-center gap-3">
                <Link :href="route('home')" class="flex min-w-0 items-center gap-2.5">
                    <img src="/images/brand/logo-primary.png" alt="" class="h-9 w-auto shrink-0" />
                    <span class="truncate font-display font-semibold">{{ name ?? 'DT Medix' }}</span>
                </Link>

                <nav v-if="isCenter" class="ml-4 hidden items-center gap-0.5 xl:flex" :aria-label="t('public.nav_primary')">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-md px-2.5 py-1.5 text-[13px] font-medium whitespace-nowrap transition-colors duration-100"
                        :class="item.active ? 'bg-primary/10 text-primary' : 'text-muted hover:bg-subtle hover:text-foreground'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-1.5">
                    <Link
                        :href="route('login')"
                        class="inline-flex h-9 items-center gap-1.5 rounded-md px-2.5 text-[13px] font-medium text-muted transition-colors duration-100 hover:bg-subtle hover:text-foreground"
                        :title="t('public.staff_sign_in')"
                    >
                        <AppIcon name="right-from-bracket" size="sm" />
                        <span class="hidden xl:inline">{{ t('public.staff_sign_in') }}</span>
                        <span class="sr-only xl:hidden">{{ t('public.staff_sign_in') }}</span>
                    </Link>
                    <LanguageSwitch />
                    <ThemeToggle />
                    <Link v-if="isCenter && !route().current('public.booking')" :href="route('public.booking')" class="btn-primary-lg ml-1 hidden !h-10 !text-[14px] whitespace-nowrap md:inline-flex">
                        <AppIcon name="calendar-check" />
                        {{ t('public.book_appointment') }}
                    </Link>
                    <button
                        v-if="isCenter"
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md text-muted hover:bg-subtle hover:text-foreground xl:hidden"
                        :aria-label="t('public.nav_toggle')"
                        :aria-expanded="menuOpen"
                        @click="menuOpen = !menuOpen"
                    >
                        <AppIcon :name="menuOpen ? 'xmark' : 'bars'" />
                    </button>
                </div>
            </div>

            <nav v-if="menuOpen && isCenter" class="border-t border-border bg-surface xl:hidden" :aria-label="t('public.nav_primary')">
                <ul class="site-container space-y-0.5 py-2">
                    <li v-for="item in nav" :key="item.href">
                        <Link :href="item.href" class="block rounded-md px-3 py-2.5 text-[14px] font-medium" :class="item.active ? 'bg-primary/10 text-primary' : 'text-muted'">{{ item.label }}</Link>
                    </li>
                    <li>
                        <Link :href="route('public.faq')" class="block rounded-md px-3 py-2.5 text-[14px] font-medium text-muted">{{ t('site.nav.faq') }}</Link>
                    </li>
                    <li class="pt-1">
                        <Link :href="route('public.booking')" class="btn-primary-lg w-full justify-center"><AppIcon name="calendar-check" /> {{ t('public.book_appointment') }}</Link>
                    </li>
                </ul>
            </nav>
        </header>

        <main id="main" class="flex-1">
            <div v-if="contained" class="site-container py-8">
                <slot />
            </div>
            <slot v-else />
        </main>

        <footer class="mt-12 border-t border-border bg-surface">
            <div class="site-container grid gap-8 py-10 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2 lg:col-span-1">
                    <p class="flex items-center gap-2 font-display font-semibold">
                        <img src="/images/brand/logo-mark.svg" alt="" class="h-7 w-7" />
                        {{ name ?? 'DT Medix' }}
                    </p>
                    <p class="mt-3 text-[13px] leading-relaxed text-muted">{{ isCenter ? t('site.footer.about') : t('public.footer_credit') }}</p>
                    <ul v-if="isCenter" class="mt-4 space-y-2 text-[13px] text-muted">
                        <li v-if="site?.phone"><a :href="'tel:' + site.phone" class="inline-flex items-center gap-2 hover:text-foreground"><AppIcon name="phone" size="sm" class="text-accent" /> <span class="tabular-nums">{{ site.phone }}</span></a></li>
                        <li v-if="site?.email"><a :href="'mailto:' + site.email" class="inline-flex items-center gap-2 hover:text-foreground"><AppIcon name="envelope" size="sm" class="text-accent" /> {{ site.email }}</a></li>
                        <li v-if="site?.address" class="flex items-start gap-2"><AppIcon name="location-dot" size="sm" class="mt-0.5 text-accent" /> {{ site.address }}</li>
                    </ul>
                </div>

                <nav v-if="isCenter" :aria-label="t('site.nav.patients')">
                    <p class="text-xs font-semibold tracking-wide text-muted uppercase">{{ t('site.nav.patients') }}</p>
                    <ul class="mt-3 space-y-2 text-[13px]">
                        <li v-for="item in footerPatients" :key="item.href"><Link :href="item.href" class="text-muted hover:text-foreground">{{ item.label }}</Link></li>
                    </ul>
                </nav>

                <nav v-if="isCenter" :aria-label="t('site.nav.company')">
                    <p class="text-xs font-semibold tracking-wide text-muted uppercase">{{ t('site.nav.company') }}</p>
                    <ul class="mt-3 space-y-2 text-[13px]">
                        <li v-for="item in footerCenter" :key="item.href"><Link :href="item.href" class="text-muted hover:text-foreground">{{ item.label }}</Link></li>
                        <li><Link :href="route('login')" class="text-muted hover:text-foreground">{{ t('public.staff_sign_in') }}</Link></li>
                    </ul>
                </nav>

                <div v-if="isCenter">
                    <p class="text-xs font-semibold tracking-wide text-muted uppercase">{{ t('site.footer.visit') }}</p>
                    <ul class="mt-3 space-y-3 text-[13px] text-muted">
                        <li v-for="branch in site?.branches ?? []" :key="branch.name">
                            <span class="block font-medium text-foreground">{{ branch.name }}</span>
                            <span v-if="branch.address" class="block">{{ branch.address }}</span>
                        </li>
                        <li class="flex items-center gap-2"><AppIcon name="clock" size="sm" class="text-accent" /> {{ t('site.topbar.hours') }}</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-border">
                <div class="site-container flex flex-col items-center justify-between gap-2 py-4 text-xs text-muted sm:flex-row">
                    <p>&copy; {{ year }} {{ name ?? 'DT Medix' }}. {{ t('site.common.rights') }}</p>
                    <p class="flex flex-wrap items-center justify-center gap-x-2">
                        <span class="inline-flex items-center gap-1.5 font-medium text-foreground"><img src="/images/brand/logo-mark.svg" alt="" class="h-4 w-4" /> {{ t('site.common.powered_by') }}</span>
                        <span aria-hidden="true">-</span>
                        <a href="https://datatechbd.com" target="_blank" rel="noopener" class="hover:text-foreground">{{ t('site.common.built_by') }}</a>
                    </p>
                </div>
            </div>
        </footer>

        <Toaster position="top-center" rich-colors :visible-toasts="3" />
        <FlashToasts />
    </div>
</template>
