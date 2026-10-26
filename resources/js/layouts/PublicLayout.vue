<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import DemoBanner from '@/components/layout/DemoBanner.vue';
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
const mobileOpen = ref(false);
const openMenu = ref(null);
const mobileGroup = ref(null);
const showTop = ref(false);
const header = ref(null);
let closeTimer = null;

const user = computed(() => page.props.auth?.user ?? null);
const site = computed(() => page.props.site ?? null);
const name = computed(() => site.value?.name ?? props.centerName);
const isCenter = computed(() => Boolean(name.value));
const year = new Date().getFullYear();
const phoneHref = computed(() => (site.value?.phone ? 'tel:' + site.value.phone : null));

const item = (key, icon, routeName, label) => ({
    key,
    icon,
    label,
    desc: t(`site.menu.desc.${key}`),
    href: route(routeName),
    active: route().current(routeName),
});

const menu = computed(() => [
    { key: 'home', label: t('site.nav.home'), href: route('home'), active: route().current('home') },
    {
        key: 'about',
        label: t('site.nav.about'),
        children: [
            item('about', 'award', 'public.about', t('site.nav.about')),
            item('locations', 'location-dot', 'public.locations', t('site.nav.locations')),
            item('guide', 'clipboard-check', 'public.guide', t('site.guide.nav')),
            item('contact', 'headset', 'public.contact', t('site.nav.contact')),
        ],
    },
    {
        key: 'services',
        label: t('site.nav.services'),
        children: [
            item('services', 'microscope', 'public.services', t('site.nav.services')),
            item('tests', 'flask', 'public.tests', t('site.nav.tests')),
            item('packages', 'boxes-stacked', 'public.packages', t('site.nav.packages')),
            item('home_collection', 'house-chimney-medical', 'public.home-collection', t('site.nav.home_collection')),
        ],
    },
    { key: 'doctors', label: t('public.nav_doctors'), href: route('public.doctors'), active: route().current('public.doctors') },
    { key: 'tests', label: t('site.nav.tests'), href: route('public.tests'), active: route().current('public.tests') },
    {
        key: 'info',
        label: t('site.nav.patient_info'),
        children: [
            item('reports', 'file-circle-check', 'public.reports', t('site.nav.reports')),
            item('faq', 'circle-question', 'public.faq', t('site.nav.faq')),
            item('privacy', 'shield-halved', 'public.privacy', t('site.nav.privacy')),
            item('terms', 'file-lines', 'public.terms', t('site.nav.terms')),
        ],
    },
    { key: 'contact', label: t('site.nav.contact'), href: route('public.contact'), active: route().current('public.contact') },
]);

const groupActive = (entry) => entry.children?.some((child) => child.active) ?? false;

const footerPatients = computed(() => [
    { label: t('public.book_appointment'), href: route('public.booking') },
    { label: t('public.nav_doctors'), href: route('public.doctors') },
    { label: t('site.nav.tests'), href: route('public.tests') },
    { label: t('site.nav.packages'), href: route('public.packages') },
    { label: t('site.nav.home_collection'), href: route('public.home-collection') },
    { label: t('site.nav.reports'), href: route('public.reports') },
]);

const footerCenter = computed(() => [
    { label: t('site.nav.about'), href: route('public.about') },
    { label: t('site.nav.services'), href: route('public.services') },
    { label: t('site.nav.locations'), href: route('public.locations') },
    { label: t('site.nav.contact'), href: route('public.contact') },
    { label: t('site.guide.nav'), href: route('public.guide') },
    { label: t('site.nav.faq'), href: route('public.faq') },
]);

const legal = computed(() => [
    { label: t('site.nav.privacy'), href: route('public.privacy') },
    { label: t('site.nav.terms'), href: route('public.terms') },
    { label: t('site.nav.faq'), href: route('public.faq') },
]);

function openGroup(key) {
    clearTimeout(closeTimer);
    openMenu.value = key;
}

function closeGroupSoon() {
    clearTimeout(closeTimer);
    closeTimer = setTimeout(() => (openMenu.value = null), 120);
}

function onDocumentClick(event) {
    if (header.value && !header.value.contains(event.target)) {
        openMenu.value = null;
    }
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        openMenu.value = null;
        mobileOpen.value = false;
    }
}

function onScroll() {
    showTop.value = window.scrollY > 600;
    openMenu.value = null;
}

function scrollTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
    window.removeEventListener('scroll', onScroll);
    clearTimeout(closeTimer);
});

watch(() => page.url, () => {
    mobileOpen.value = false;
    openMenu.value = null;
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-base">
        <DemoBanner />
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:m-2 focus:rounded-md focus:bg-primary focus:px-3 focus:py-2 focus:text-primary-foreground">{{ t('site.common.skip') }}</a>

        <div v-if="isCenter" class="hidden bg-primary text-primary-foreground md:block">
            <div class="site-container flex h-9 items-center justify-between gap-4 text-xs">
                <div class="flex items-center gap-5">
                    <span class="inline-flex items-center gap-1.5"><AppIcon name="clock" size="sm" /> {{ t('site.topbar.hours') }}</span>
                    <a v-if="site?.phone" :href="phoneHref" class="inline-flex items-center gap-1.5 font-semibold hover:underline"><AppIcon name="phone" size="sm" /> <span class="tabular-nums">{{ site.phone }}</span></a>
                    <a v-if="site?.email" :href="'mailto:' + site.email" class="hidden items-center gap-1.5 hover:underline lg:inline-flex"><AppIcon name="envelope" size="sm" /> {{ site.email }}</a>
                </div>
                <div class="flex items-center gap-4">
                    <span class="hidden opacity-90 lg:inline">{{ t('site.topbar.tagline') }}</span>
                    <Link :href="route('public.locations')" class="inline-flex items-center gap-1.5 hover:underline"><AppIcon name="location-dot" size="sm" /> {{ t('site.nav.locations') }}</Link>
                    <Link :href="user ? route('admin.dashboard') : route('login')" class="inline-flex items-center gap-1.5 font-semibold hover:underline">
                        <AppIcon :name="user ? 'gauge-high' : 'right-from-bracket'" size="sm" />
                        {{ user ? t('public.dashboard') : t('site.footer.staff') }}
                    </Link>
                </div>
            </div>
        </div>

        <header ref="header" class="sticky top-0 z-30 border-b border-border bg-surface/95 shadow-sm backdrop-blur">
            <div class="site-container flex h-16 items-center gap-3">
                <Link :href="route('home')" class="flex min-w-0 shrink-0 items-center gap-2.5 xl:max-w-[14rem]">
                    <img v-if="isCenter" src="/images/brand/logo-mark.svg" alt="" class="h-10 w-10 shrink-0" />
                    <img v-else src="/images/brand/logo-primary.png" alt="DT Medix" class="h-9 w-auto shrink-0" />
                    <span v-if="isCenter" class="min-w-0 font-display text-[15px] leading-tight font-semibold">
                        <span class="line-clamp-2">{{ name }}</span>
                    </span>
                </Link>

                <nav v-if="isCenter" class="ml-3 hidden min-w-0 flex-1 items-center justify-center gap-0.5 xl:flex" :aria-label="t('public.nav_primary')">
                    <template v-for="entry in menu" :key="entry.key">
                        <Link
                            v-if="!entry.children"
                            :href="entry.href"
                            class="rounded-md px-3 py-2 text-[14px] font-medium whitespace-nowrap transition-colors duration-100"
                            :class="entry.active ? 'bg-primary/10 text-primary' : 'text-foreground/80 hover:bg-subtle hover:text-foreground'"
                        >
                            {{ entry.label }}
                        </Link>
                        <div v-else class="relative" @mouseenter="openGroup(entry.key)" @mouseleave="closeGroupSoon">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-[14px] font-medium whitespace-nowrap transition-colors duration-100"
                                :class="groupActive(entry) || openMenu === entry.key ? 'bg-primary/10 text-primary' : 'text-foreground/80 hover:bg-subtle hover:text-foreground'"
                                aria-haspopup="true"
                                :aria-expanded="openMenu === entry.key"
                                @click="openGroup(entry.key)"
                            >
                                {{ entry.label }}
                                <AppIcon name="chevron-down" size="xs" :class="openMenu === entry.key ? 'rotate-180' : ''" class="transition-transform duration-150" />
                            </button>
                            <div v-show="openMenu === entry.key" class="absolute top-full left-1/2 z-40 w-80 -translate-x-1/2 pt-2">
                                <ul class="rounded-xl border border-border bg-surface p-2 shadow-lg">
                                    <li v-for="child in entry.children" :key="child.key">
                                        <Link :href="child.href" class="flex items-start gap-3 rounded-lg px-3 py-2.5 transition-colors duration-100 hover:bg-subtle" :class="child.active ? 'bg-primary/5' : ''">
                                            <span class="site-icon !h-9 !w-9"><AppIcon :name="child.icon" /></span>
                                            <span class="min-w-0">
                                                <span class="block text-[14px] font-semibold" :class="child.active ? 'text-primary' : ''">{{ child.label }}</span>
                                                <span class="block text-xs text-muted">{{ child.desc }}</span>
                                            </span>
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </template>
                </nav>

                <div class="ml-auto flex shrink-0 items-center gap-1.5">
                    <LanguageSwitch />
                    <ThemeToggle />
                    <a v-if="isCenter && phoneHref" :href="phoneHref" class="hidden h-10 items-center gap-2 rounded-md border border-border px-3 text-[13px] font-semibold whitespace-nowrap tabular-nums transition-colors hover:bg-subtle 2xl:inline-flex">
                        <AppIcon name="phone" size="sm" class="text-accent" /> {{ site.phone }}
                    </a>
                    <Link v-if="isCenter && !route().current('public.booking')" :href="route('public.booking')" class="btn-primary-lg ml-1 hidden !h-10 !text-[14px] whitespace-nowrap md:inline-flex">
                        <AppIcon name="calendar-check" />
                        {{ t('site.common.book_short') }}
                    </Link>
                    <Link v-if="!isCenter" :href="user ? route('admin.dashboard') : route('login')" class="inline-flex h-9 items-center gap-1.5 rounded-md border border-border px-3 text-[13px] font-semibold whitespace-nowrap text-primary hover:bg-subtle">
                        <AppIcon :name="user ? 'gauge-high' : 'right-from-bracket'" size="sm" />
                        {{ user ? t('public.dashboard') : t('public.staff_sign_in') }}
                    </Link>
                    <button
                        v-if="isCenter"
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-md text-foreground hover:bg-subtle xl:hidden"
                        :aria-label="t('public.nav_toggle')"
                        :aria-expanded="mobileOpen"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <AppIcon :name="mobileOpen ? 'xmark' : 'bars'" size="lg" />
                    </button>
                </div>
            </div>

            <nav v-if="mobileOpen && isCenter" class="max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-border bg-surface xl:hidden" :aria-label="t('public.nav_primary')">
                <ul class="site-container space-y-0.5 py-3">
                    <li v-for="entry in menu" :key="entry.key">
                        <Link v-if="!entry.children" :href="entry.href" class="block rounded-md px-3 py-2.5 text-[15px] font-medium" :class="entry.active ? 'bg-primary/10 text-primary' : ''">{{ entry.label }}</Link>
                        <template v-else>
                            <button type="button" class="flex w-full items-center justify-between rounded-md px-3 py-2.5 text-left text-[15px] font-medium" :class="groupActive(entry) ? 'text-primary' : ''" :aria-expanded="mobileGroup === entry.key" @click="mobileGroup = mobileGroup === entry.key ? null : entry.key">
                                {{ entry.label }}
                                <AppIcon name="chevron-down" size="sm" :class="mobileGroup === entry.key ? 'rotate-180' : ''" class="transition-transform duration-150" />
                            </button>
                            <ul v-show="mobileGroup === entry.key" class="mb-1 ml-3 space-y-0.5 border-l border-border pl-2">
                                <li v-for="child in entry.children" :key="child.key">
                                    <Link :href="child.href" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-[14px]" :class="child.active ? 'bg-primary/10 font-semibold text-primary' : 'text-muted'">
                                        <AppIcon :name="child.icon" size="sm" class="text-accent" /> {{ child.label }}
                                    </Link>
                                </li>
                            </ul>
                        </template>
                    </li>
                    <li class="pt-2">
                        <Link :href="route('public.booking')" class="btn-primary-lg w-full justify-center"><AppIcon name="calendar-check" /> {{ t('public.book_appointment') }}</Link>
                    </li>
                    <li>
                        <Link :href="user ? route('admin.dashboard') : route('login')" class="block rounded-md px-3 py-2.5 text-[14px] font-semibold text-primary">{{ user ? t('public.dashboard') : t('site.footer.staff') }}</Link>
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
            <div class="site-container grid gap-8 py-12 sm:grid-cols-2 lg:grid-cols-12">
                <div class="sm:col-span-2 lg:col-span-4">
                    <p class="flex items-center gap-2 font-display text-lg font-semibold">
                        <img src="/images/brand/logo-mark.svg" alt="" class="h-8 w-8" />
                        {{ name ?? 'DT Medix' }}
                    </p>
                    <p class="mt-3 max-w-sm text-[13px] leading-relaxed text-muted">{{ isCenter ? t('site.footer.about') : t('public.footer_credit') }}</p>
                    <ul v-if="isCenter" class="mt-4 space-y-2.5 text-[13px] text-muted">
                        <li v-if="site?.address" class="flex items-start gap-2"><AppIcon name="location-dot" size="sm" class="mt-0.5 text-accent" /> {{ site.address }}</li>
                        <li v-if="site?.phone"><a :href="phoneHref" class="inline-flex items-center gap-2 hover:text-foreground"><AppIcon name="phone" size="sm" class="text-accent" /> <span class="tabular-nums">{{ site.phone }}</span></a></li>
                        <li v-if="site?.email"><a :href="'mailto:' + site.email" class="inline-flex items-center gap-2 hover:text-foreground"><AppIcon name="envelope" size="sm" class="text-accent" /> {{ site.email }}</a></li>
                        <li class="flex items-center gap-2"><AppIcon name="clock" size="sm" class="text-accent" /> {{ t('site.topbar.hours') }}</li>
                    </ul>
                </div>

                <nav v-if="isCenter" class="lg:col-span-2" :aria-label="t('site.nav.patients')">
                    <p class="text-xs font-semibold tracking-wide text-foreground uppercase">{{ t('site.nav.patients') }}</p>
                    <ul class="mt-3 space-y-2 text-[13px]">
                        <li v-for="link in footerPatients" :key="link.href"><Link :href="link.href" class="text-muted hover:text-foreground hover:underline">{{ link.label }}</Link></li>
                    </ul>
                </nav>

                <nav v-if="isCenter" class="lg:col-span-3" :aria-label="t('site.nav.company')">
                    <p class="text-xs font-semibold tracking-wide text-foreground uppercase">{{ t('site.nav.company') }}</p>
                    <ul class="mt-3 space-y-2 text-[13px]">
                        <li v-for="link in footerCenter" :key="link.href"><Link :href="link.href" class="text-muted hover:text-foreground hover:underline">{{ link.label }}</Link></li>
                    </ul>
                </nav>

                <div v-if="isCenter" class="lg:col-span-3">
                    <p class="text-xs font-semibold tracking-wide text-foreground uppercase">{{ t('site.footer.visit') }}</p>
                    <ul class="mt-3 space-y-3 text-[13px] text-muted">
                        <li v-for="branch in site?.branches ?? []" :key="branch.name">
                            <span class="block font-medium text-foreground">{{ branch.name }}</span>
                            <span v-if="branch.address" class="block">{{ branch.address }}</span>
                            <a v-if="branch.phone" :href="'tel:' + branch.phone" class="block tabular-nums hover:text-foreground">{{ branch.phone }}</a>
                        </li>
                    </ul>
                    <Link :href="route('public.locations')" class="mt-3 inline-flex items-center gap-1 text-[13px] font-semibold text-primary hover:underline">{{ t('site.menu.view_all') }} <AppIcon name="arrow-right" size="sm" /></Link>
                </div>
            </div>

            <div class="border-t border-border bg-subtle/50">
                <div class="site-container flex flex-col items-center justify-between gap-3 py-4 text-xs text-muted lg:flex-row">
                    <p>&copy; {{ year }} {{ name ?? 'DT Medix' }}. {{ t('site.common.rights') }}</p>
                    <ul v-if="isCenter" class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
                        <li v-for="link in legal" :key="link.href"><Link :href="link.href" class="hover:text-foreground hover:underline">{{ link.label }}</Link></li>
                    </ul>
                    <p class="flex flex-wrap items-center justify-center gap-x-2">
                        <span class="inline-flex items-center gap-1.5 font-medium text-foreground"><img src="/images/brand/logo-mark.svg" alt="" class="h-4 w-4" /> {{ t('site.common.powered_by') }}</span>
                        <span aria-hidden="true">-</span>
                        <a href="https://datatechbd.com" target="_blank" rel="noopener" class="hover:text-foreground">{{ t('site.common.built_by') }}</a>
                    </p>
                </div>
            </div>
        </footer>

        <div v-if="isCenter" class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-3 border-t border-border bg-surface shadow-lg md:hidden">
            <a v-if="phoneHref" :href="phoneHref" class="flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium"><AppIcon name="phone" class="text-accent" /> {{ t('site.common.call_us') }}</a>
            <Link :href="route('public.locations')" class="flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium"><AppIcon name="location-dot" class="text-accent" /> {{ t('site.nav.locations') }}</Link>
            <Link :href="route('public.booking')" class="flex flex-col items-center gap-0.5 bg-primary py-2 text-[11px] font-semibold text-primary-foreground"><AppIcon name="calendar-check" /> {{ t('site.common.book_short') }}</Link>
        </div>
        <div v-if="isCenter" class="h-12 md:hidden" aria-hidden="true" />

        <button
            v-show="showTop"
            type="button"
            class="fixed right-4 bottom-16 z-30 inline-flex h-10 w-10 items-center justify-center rounded-full border border-border bg-surface text-primary shadow-md hover:bg-subtle md:bottom-5"
            :aria-label="t('site.footer.back_to_top')"
            @click="scrollTop"
        >
            <AppIcon name="arrow-up" />
        </button>

        <Toaster position="top-center" rich-colors :visible-toasts="3" />
        <FlashToasts />
    </div>
</template>
