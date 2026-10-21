<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FlashToasts from '@/components/ui-kit/FlashToasts.vue';
import LanguageSwitch from '@/components/ui-kit/LanguageSwitch.vue';
import ThemeToggle from '@/components/ui-kit/ThemeToggle.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import { Toaster } from 'vue-sonner';
import { useI18n } from '@/lib/i18n';

defineProps({ centerName: { type: String, default: null } });

const { t } = useI18n();
const menuOpen = ref(false);

const nav = computed(() => [
    { label: t('public.nav_home'), href: route('home'), active: route().current('home') },
    { label: t('public.nav_doctors'), href: route('public.doctors'), active: route().current('public.doctors') },
    { label: t('public.nav_tests'), href: route('public.tests'), active: route().current('public.tests') },
]);
</script>

<template>
    <div class="min-h-screen bg-background text-base">
        <header class="sticky top-0 z-20 border-b border-border bg-surface/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4">
                <Link :href="route('home')" class="flex min-w-0 items-center gap-2.5">
                    <img src="/images/brand/logo-primary.png" alt="" class="h-9 w-auto" />
                    <span class="truncate font-display font-semibold">{{ centerName ?? 'DT Medix' }}</span>
                </Link>

                <nav v-if="centerName" class="ml-6 hidden items-center gap-1 md:flex" :aria-label="t('public.nav_primary')">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-md px-3 py-1.5 text-[14px] font-medium transition-colors duration-100"
                        :class="item.active ? 'bg-subtle text-foreground' : 'text-muted hover:bg-subtle hover:text-foreground'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-1.5">
                    <Link
                        :href="route('login')"
                        class="hidden h-9 items-center gap-1.5 rounded-md px-2.5 text-[13px] font-medium text-muted transition-colors duration-100 hover:bg-subtle hover:text-foreground sm:inline-flex"
                    >
                        <AppIcon name="right-from-bracket" size="sm" />
                        {{ t('public.staff_sign_in') }}
                    </Link>
                    <LanguageSwitch />
                    <ThemeToggle />
                    <Link v-if="centerName && !route().current('public.booking')" :href="route('public.booking')" class="btn-primary-lg ml-1 hidden !h-10 !text-[14px] sm:inline-flex">
                        <AppIcon name="calendar-check" />
                        {{ t('public.book_appointment') }}
                    </Link>
                    <button
                        v-if="centerName"
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md text-muted hover:bg-subtle hover:text-foreground md:hidden"
                        :aria-label="t('public.nav_toggle')"
                        :aria-expanded="menuOpen"
                        @click="menuOpen = !menuOpen"
                    >
                        <AppIcon :name="menuOpen ? 'xmark' : 'bars'" />
                    </button>
                </div>
            </div>

            <nav v-if="menuOpen" class="border-t border-border bg-surface px-4 py-2 md:hidden">
                <ul class="space-y-0.5">
                    <li v-for="item in nav" :key="item.href">
                        <Link :href="item.href" class="block rounded-md px-3 py-2 text-[14px] font-medium" :class="item.active ? 'bg-subtle' : 'text-muted'" @click="menuOpen = false">{{ item.label }}</Link>
                    </li>
                    <li>
                        <Link :href="route('public.booking')" class="block rounded-md px-3 py-2 text-[14px] font-medium text-primary" @click="menuOpen = false">{{ t('public.book_appointment') }}</Link>
                    </li>
                    <li>
                        <Link :href="route('login')" class="block rounded-md px-3 py-2 text-[14px] font-medium text-muted" @click="menuOpen = false">{{ t('public.staff_sign_in') }}</Link>
                    </li>
                </ul>
            </nav>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6">
            <slot />
        </main>

        <footer class="mt-10 border-t border-border bg-surface">
            <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="flex items-center gap-2 font-display font-semibold">
                        <img src="/images/brand/logo-mark.svg" alt="" class="h-6 w-6" />
                        {{ centerName ?? 'DT Medix' }}
                    </p>
                    <p class="mt-2 text-[13px] leading-relaxed text-muted">{{ t('public.footer_credit') }}</p>
                </div>
                <nav v-if="centerName" :aria-label="t('public.nav_footer')">
                    <p class="text-xs font-semibold tracking-wide text-muted uppercase">{{ t('public.nav_quick_links') }}</p>
                    <ul class="mt-2 space-y-1.5 text-[13px]">
                        <li><Link :href="route('home')" class="text-muted hover:text-foreground">{{ t('public.nav_home') }}</Link></li>
                        <li><Link :href="route('public.doctors')" class="text-muted hover:text-foreground">{{ t('public.nav_doctors') }}</Link></li>
                        <li><Link :href="route('public.tests')" class="text-muted hover:text-foreground">{{ t('public.nav_tests') }}</Link></li>
                        <li><Link :href="route('public.booking')" class="text-muted hover:text-foreground">{{ t('public.book_appointment') }}</Link></li>
                    </ul>
                </nav>
                <div>
                    <p class="text-xs font-semibold tracking-wide text-muted uppercase">Datatech BD Ltd.</p>
                    <a href="https://datatechbd.com" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1.5 text-[13px] text-muted hover:text-foreground">
                        <AppIcon name="globe" size="sm" />
                        datatechbd.com
                    </a>
                </div>
            </div>
        </footer>

        <Toaster position="top-center" rich-colors :visible-toasts="3" />
        <FlashToasts />
    </div>
</template>
