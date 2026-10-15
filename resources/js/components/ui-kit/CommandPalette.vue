<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { computed, nextTick, ref, watch } from 'vue';
import { useShortcuts } from '@/composables/useShortcuts';
import { getJson } from '@/lib/http';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';

/**
 * Ctrl+K: pages, actions, patients (by name, mobile or ID) and invoices.
 */
const page = usePage();
const { t } = useI18n();
const open = ref(false);
const query = ref('');
const remote = ref([]);
const highlighted = ref(0);
const input = ref(null);
let timer = null;

useShortcuts({ 'ctrl+k': () => (open.value = true) });

defineExpose({ show: () => (open.value = true) });

const pages = computed(() =>
    (page.props.navigation ?? []).flatMap((group) => group.items.map((item) => ({ id: `nav-${item.key}`, label: item.label, group: group.group, icon: item.icon, href: item.href }))),
);

const results = computed(() => {
    const q = query.value.trim().toLowerCase();
    const local = pages.value.filter((p) => !q || p.label.toLowerCase().includes(q)).slice(0, 8);
    return [...local, ...remote.value];
});

watch(query, (value) => {
    highlighted.value = 0;
    clearTimeout(timer);
    remote.value = [];
    if (value.trim().length < 2 || !route().has('admin.search')) {
        return;
    }
    timer = setTimeout(async () => {
        try {
            const data = await getJson(route('admin.search'), { q: value.trim() });
            remote.value = data.results ?? [];
        } catch {
            remote.value = [];
        }
    }, 300);
});

watch(open, async (value) => {
    if (value) {
        query.value = '';
        await nextTick();
        input.value?.focus();
    }
});

function go(item) {
    open.value = false;
    if (item?.href) {
        router.visit(item.href);
    }
}

function onKey(event) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlighted.value = Math.min(highlighted.value + 1, results.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, 0);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        go(results.value[highlighted.value]);
    }
}
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-black/40" />
            <DialogContent class="fixed top-[12vh] left-1/2 z-50 w-[92vw] max-w-lg -translate-x-1/2 overflow-hidden rounded-xl border border-border bg-surface shadow-xl focus:outline-none">
                <DialogTitle class="sr-only">{{ t('command.title') }}</DialogTitle>
                <div class="flex items-center gap-2 border-b border-border px-3">
                    <AppIcon name="magnifying-glass" class="text-muted" />
                    <input ref="input" v-model="query" type="text" class="h-10 w-full bg-transparent text-sm focus:outline-none" :placeholder="t('command.placeholder')" @keydown="onKey" />
                </div>
                <ul class="max-h-80 overflow-y-auto p-1" role="listbox">
                    <li
                        v-for="(item, index) in results"
                        :key="item.id"
                        role="option"
                        :aria-selected="index === highlighted ? 'true' : 'false'"
                        class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-[13px]"
                        :class="index === highlighted ? 'bg-accent/10' : ''"
                        @mouseenter="highlighted = index"
                        @click="go(item)"
                    >
                        <AppIcon :name="item.icon ?? 'arrow-right'" size="sm" class="text-muted" />
                        <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                        <span class="shrink-0 text-xs text-muted">{{ item.group }}</span>
                    </li>
                    <li v-if="results.length === 0" class="px-2 py-4 text-center text-[13px] text-muted">{{ t('common.no_results') }}</li>
                </ul>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
