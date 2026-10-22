<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import { useI18n } from '@/lib/i18n';

const { t } = useI18n();
const demo = computed(() => usePage().props.demo ?? null);
</script>

<template>
    <div v-if="demo" class="no-print w-full bg-primary px-3 py-1.5 text-center text-xs leading-snug text-primary-foreground sm:text-[13px]" role="note">
        <AppIcon name="bolt" size="sm" class="text-accent" />
        <strong class="font-semibold">{{ t('demo.title') }}</strong>
        {{ t('demo.text') }}
        {{ t('demo.cta') }}
        <a :href="'tel:' + demo.phone.replace(/[^+\d]/g, '')" class="font-bold whitespace-nowrap underline tabular-nums">{{ demo.phone }}</a>
    </div>
</template>
