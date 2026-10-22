<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import { useI18n } from '@/lib/i18n';

defineProps({
    title: { type: String, required: true },
    text: { type: String, default: null },
});

const { t } = useI18n();
const phone = computed(() => usePage().props.site?.phone ?? null);
</script>

<template>
    <section class="site-container site-section !pb-0">
        <div class="site-hero relative overflow-hidden rounded-2xl border border-border bg-primary p-8 text-primary-foreground lg:p-12">
            <div class="relative flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-center">
                <div class="max-w-xl">
                    <h2 class="font-display text-2xl font-bold tracking-tight lg:text-3xl">{{ title }}</h2>
                    <p v-if="text" class="mt-2 text-[15px] leading-relaxed opacity-90">{{ text }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('public.booking')" class="inline-flex h-12 items-center gap-2 rounded-lg bg-surface px-5 text-[15px] font-semibold text-primary shadow-sm transition-opacity hover:opacity-90">
                        <AppIcon name="calendar-check" /> {{ t('public.book_appointment') }}
                    </Link>
                    <a v-if="phone" :href="'tel:' + phone" class="inline-flex h-12 items-center gap-2 rounded-lg border border-white/40 px-5 text-[15px] font-semibold transition-colors hover:bg-white/10">
                        <AppIcon name="phone" /> <span class="tabular-nums">{{ phone }}</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
