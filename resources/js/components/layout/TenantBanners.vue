<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import { localizeDigits } from '@/lib/format';

const page = usePage();
const tenant = computed(() => page.props.tenant);
</script>

<template>
    <div class="no-print">
        <div v-if="page.props.impersonating" class="flex items-center justify-center gap-2 bg-warning px-3 py-1 text-xs font-medium text-black" role="status">
            <AppIcon name="user-shield" size="sm" />
            {{ $t('platform.impersonation_banner', { tenant: tenant?.name ?? '' }) }}
            <button type="button" class="ml-2 rounded bg-black/10 px-2 py-0.5 hover:bg-black/20" @click="router.post(route('impersonation.leave'))">{{ $t('platform.leave_impersonation') }}</button>
        </div>
        <div v-if="tenant?.status === 'trial' && tenant.trial_days_left !== null" class="flex items-center justify-center gap-2 bg-accent/10 px-3 py-1 text-xs text-foreground" role="status">
            <AppIcon name="clock" size="sm" class="text-accent" />
            {{ $t('platform.trial_banner', { days: localizeDigits(tenant.trial_days_left) }) }}
            <span class="font-medium text-accent">{{ $t('platform.upgrade_prompt') }}</span>
        </div>
        <div v-if="tenant?.read_only" class="flex items-center justify-center gap-2 bg-danger/10 px-3 py-1 text-xs text-danger" role="alert">
            <AppIcon name="lock" size="sm" />
            {{ $t('platform.read_only_banner') }}
        </div>
    </div>
</template>
