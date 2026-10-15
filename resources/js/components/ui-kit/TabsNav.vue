<script setup>

/**
 * Tabs synced with the "tab" query parameter.
 */
const model = defineModel({ type: String, required: true });

const props = defineProps({
    tabs: { type: Array, required: true },
    syncUrl: { type: Boolean, default: true },
    param: { type: String, default: 'tab' },
});

function select(key) {
    model.value = key;
    if (props.syncUrl) {
        const url = new URL(window.location.href);
        url.searchParams.set(props.param, key);
        window.history.replaceState(window.history.state, '', url.toString());
    }
}

function onKey(event, index) {
    const offset = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
    if (offset) {
        const next = props.tabs[(index + offset + props.tabs.length) % props.tabs.length];
        select(next.key);
        event.currentTarget.parentElement.children[(index + offset + props.tabs.length) % props.tabs.length]?.focus();
    }
}
</script>

<template>
    <div role="tablist" class="mb-3 flex gap-1 overflow-x-auto border-b border-border">
        <button
            v-for="(tab, index) in tabs"
            :key="tab.key"
            type="button"
            role="tab"
            :aria-selected="model === tab.key ? 'true' : 'false'"
            :tabindex="model === tab.key ? 0 : -1"
            class="-mb-px h-8 border-b-2 px-3 text-[13px] whitespace-nowrap transition-colors"
            :class="model === tab.key ? 'border-accent font-medium text-foreground' : 'border-transparent text-muted hover:text-foreground'"
            @click="select(tab.key)"
            @keydown="onKey($event, index)"
        >
            {{ tab.label }}
            <span v-if="tab.count !== undefined" class="ml-1 rounded bg-subtle px-1 text-xs tabular-nums">{{ tab.count }}</span>
        </button>
    </div>
</template>
