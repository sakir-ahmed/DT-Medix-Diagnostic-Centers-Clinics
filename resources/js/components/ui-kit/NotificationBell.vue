<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui';
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';
import DateText from './DateText.vue';
import EmptyState from './EmptyState.vue';

/**
 * Persistent alerts (critical result, verification waiting, overdue due,
 * refund request) from the "notifications" shared prop.
 */
const page = usePage();
const { t } = useI18n();

const items = computed(() => page.props.notifications?.items ?? []);
const unread = computed(() => page.props.notifications?.unread ?? 0);

const icons = { critical: 'triangle-exclamation', verification: 'stamp', due: 'money-bill-wave', refund: 'rotate-left', discount: 'user-shield', info: 'circle-info' };

function markAllRead() {
    router.post(route('admin.notifications.read'), {}, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <PopoverRoot>
        <PopoverTrigger class="relative inline-flex h-7 w-7 items-center justify-center rounded-md hover:bg-subtle" :aria-label="t('notifications.title')">
            <AppIcon name="bell" />
            <span v-if="unread" class="absolute -top-0.5 -right-0.5 min-w-4 rounded-full bg-danger px-1 text-[10px] leading-4 font-semibold text-white tabular-nums">{{ unread > 9 ? '9+' : unread }}</span>
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent align="end" :side-offset="6" class="z-50 w-80 rounded-lg border border-border bg-surface shadow-lg">
                <div class="flex items-center justify-between border-b border-border px-3 py-2">
                    <span class="text-sm font-semibold">{{ t('notifications.title') }}</span>
                    <button v-if="unread && route().has('admin.notifications.read')" type="button" class="text-xs text-accent hover:underline" @click="markAllRead">{{ t('notifications.mark_all_read') }}</button>
                </div>
                <EmptyState v-if="items.length === 0" icon="bell" :title="t('notifications.empty')" />
                <ul v-else class="max-h-96 divide-y divide-border overflow-y-auto">
                    <li v-for="item in items" :key="item.id">
                        <Link :href="item.href ?? '#'" class="flex gap-2 px-3 py-2 hover:bg-subtle" :class="item.read ? '' : 'bg-accent/5'">
                            <AppIcon :name="icons[item.type] ?? 'circle-info'" :class="item.type === 'critical' ? 'text-danger' : 'text-muted'" class="mt-0.5" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px]">{{ item.title }}</span>
                                <DateText :value="item.created_at" relative class="text-xs text-muted" />
                            </span>
                        </Link>
                    </li>
                </ul>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
