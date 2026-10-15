<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';

const props = defineProps({
    collapsed: { type: Boolean, default: false },
    groups: { type: Array, default: null },
    brand: { type: String, default: null },
});

defineEmits(['navigate']);

const page = usePage();
const navigation = computed(() => props.groups ?? page.props.navigation ?? []);

function active(item) {
    try {
        return route().current(item.route) || route().current(`${item.route.replace(/\.index$/, '')}.*`);
    } catch {
        return false;
    }
}
</script>

<template>
    <nav class="flex h-full flex-col bg-surface" :aria-label="$t('nav.main')">
        <div class="flex h-12 shrink-0 items-center gap-2 border-b border-border px-3">
            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-accent text-accent-foreground"><AppIcon name="heart-pulse" /></span>
            <span v-if="!collapsed" class="truncate text-sm font-semibold">{{ brand ?? page.props.tenant?.name ?? page.props.app?.name }}</span>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
            <div v-for="group in navigation" :key="group.group" class="mb-3">
                <p v-if="!collapsed" class="mb-1 px-2 text-[11px] font-semibold tracking-wider text-muted">{{ group.group }}</p>
                <Link
                    v-for="item in group.items"
                    :key="item.key"
                    :href="item.href"
                    class="flex h-8 items-center gap-2 rounded-md px-2 text-[13px] transition-colors"
                    :class="active(item) ? 'bg-accent/10 font-medium text-accent' : 'text-foreground hover:bg-subtle'"
                    :title="collapsed ? item.label : undefined"
                    :aria-current="active(item) ? 'page' : undefined"
                    @click="$emit('navigate')"
                >
                    <AppIcon :name="item.icon" class="shrink-0" />
                    <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                </Link>
            </div>
        </div>
    </nav>
</template>
