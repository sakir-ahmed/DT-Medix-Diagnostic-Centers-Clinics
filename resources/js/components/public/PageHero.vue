<script setup>
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import { useI18n } from '@/lib/i18n';

defineProps({
    badge: { type: String, default: null },
    title: { type: String, required: true },
    lead: { type: String, default: null },
    icon: { type: String, default: null },
});

const { t } = useI18n();
</script>

<template>
    <section class="site-hero border-b border-border">
        <div class="site-container py-10 lg:py-14">
            <nav :aria-label="t('site.common.home')" class="mb-4 flex items-center gap-1.5 text-xs text-muted">
                <Link :href="route('home')" class="hover:text-foreground">{{ t('site.common.home') }}</Link>
                <AppIcon name="chevron-right" size="xs" />
                <span class="text-foreground">{{ title }}</span>
            </nav>
            <div class="flex items-start gap-4">
                <span v-if="icon" class="site-icon hidden h-14 w-14 sm:flex"><AppIcon :name="icon" size="2xl" /></span>
                <div>
                    <p v-if="badge" class="badge-line">{{ badge }}</p>
                    <h1 class="mt-3 font-display text-3xl font-bold tracking-tight lg:text-4xl">{{ title }}</h1>
                    <p v-if="lead" class="mt-3 max-w-2xl text-base leading-relaxed text-muted">{{ lead }}</p>
                    <div class="mt-5 flex flex-wrap gap-2"><slot /></div>
                </div>
            </div>
        </div>
    </section>
</template>
