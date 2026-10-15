<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { Toaster } from 'vue-sonner';
import AppSidebar from '@/components/layout/AppSidebar.vue';
import BranchSwitcher from '@/components/layout/BranchSwitcher.vue';
import FlashToasts from '@/components/layout/FlashToasts.vue';
import OfflineBanner from '@/components/layout/OfflineBanner.vue';
import SessionTimeout from '@/components/layout/SessionTimeout.vue';
import TenantBanners from '@/components/layout/TenantBanners.vue';
import UserMenu from '@/components/layout/UserMenu.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import CommandPalette from '@/components/ui-kit/CommandPalette.vue';
import ConfirmHost from '@/components/ui-kit/ConfirmHost.vue';
import LanguageSwitch from '@/components/ui-kit/LanguageSwitch.vue';
import NotificationBell from '@/components/ui-kit/NotificationBell.vue';
import ThemeToggle from '@/components/ui-kit/ThemeToggle.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';

defineProps({
    platform: { type: Boolean, default: false },
    navigation: { type: Array, default: null },
});

const page = usePage();
const collapsed = ref(false);
const mobileOpen = ref(false);
const palette = ref(null);
const locale = computed(() => page.props.app?.locale ?? 'en');

onMounted(() => {
    try {
        collapsed.value = localStorage.getItem('sidebar-collapsed') === '1';
    } catch {
        collapsed.value = false;
    }
});

function toggleSidebar() {
    collapsed.value = !collapsed.value;
    try {
        localStorage.setItem('sidebar-collapsed', collapsed.value ? '1' : '0');
    } catch {
        // Storage unavailable.
    }
}
</script>

<template>
    <div class="flex min-h-screen bg-background" :lang="locale">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:m-2 focus:rounded focus:bg-surface focus:p-2">{{ $t('nav.skip_to_content') }}</a>

        <aside class="no-print sticky top-0 hidden h-screen shrink-0 border-r border-border transition-[width] md:block" :class="collapsed ? 'w-14' : 'w-[220px]'">
            <AppSidebar :collapsed="collapsed" :groups="navigation" :brand="platform ? 'DT Medix Platform' : null" />
        </aside>

        <div v-if="mobileOpen" class="fixed inset-0 z-40 md:hidden">
            <div class="absolute inset-0 bg-black/40" @click="mobileOpen = false" />
            <aside class="absolute inset-y-0 left-0 w-[260px] border-r border-border">
                <AppSidebar :groups="navigation" :brand="platform ? 'DT Medix Platform' : null" @navigate="mobileOpen = false" />
            </aside>
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            <OfflineBanner />
            <TenantBanners v-if="!platform" />
            <header class="no-print sticky top-0 z-30 flex h-12 items-center gap-2 border-b border-border bg-surface px-3">
                <UiButton variant="ghost" icon-only icon="bars" class="md:hidden" :label="$t('nav.open_menu')" @click="mobileOpen = true" />
                <UiButton variant="ghost" icon-only icon="bars" class="hidden md:inline-flex" :label="$t('nav.toggle_sidebar')" @click="toggleSidebar" />
                <BranchSwitcher v-if="!platform" />
                <button
                    v-if="!platform"
                    type="button"
                    class="ml-1 hidden h-7 w-56 items-center gap-2 rounded-md border border-border px-2 text-[13px] text-muted hover:bg-subtle sm:flex"
                    @click="palette?.show()"
                >
                    <AppIcon name="magnifying-glass" size="sm" />
                    <span class="flex-1 text-left">{{ $t('command.placeholder_short') }}</span>
                    <kbd class="rounded border border-border px-1 text-[10px]">Ctrl K</kbd>
                </button>
                <div class="ml-auto flex items-center gap-1.5">
                    <NotificationBell v-if="!platform" />
                    <LanguageSwitch />
                    <ThemeToggle />
                    <UserMenu />
                </div>
            </header>
            <main id="main" class="min-w-0 flex-1 p-3 md:p-4">
                <slot />
            </main>
        </div>

        <Toaster position="top-right" rich-colors close-button :visible-toasts="3" />
        <ConfirmHost />
        <CommandPalette ref="palette" />
        <SessionTimeout />
        <FlashToasts />
    </div>
</template>
