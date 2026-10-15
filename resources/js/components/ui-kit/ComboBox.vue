<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';

/**
 * Searchable select. Static `options` or an async `search(query)` returning
 * [{ value, label, description?, meta? }]. Debounced 300ms, keyboard first,
 * "No results" with an optional "Create new".
 */
const model = defineModel({ type: [String, Number, Object, null], default: null });

const props = defineProps({
    id: { type: String, default: null },
    options: { type: Array, default: null },
    search: { type: Function, default: null },
    placeholder: { type: String, default: null },
    invalid: { type: Boolean, default: false },
    allowCreate: { type: Boolean, default: false },
    clearOnSelect: { type: Boolean, default: false },
    returnObject: { type: Boolean, default: false },
    selectedLabel: { type: String, default: null },
    minChars: { type: Number, default: 0 },
    disabled: { type: Boolean, default: false },
    touch: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'create']);
const { t } = useI18n();

const query = ref('');
const open = ref(false);
const loading = ref(false);
const results = ref([]);
const highlighted = ref(0);
const input = ref(null);
let timer = null;
let requestId = 0;

const currentLabel = computed(() => {
    if (props.selectedLabel) {
        return props.selectedLabel;
    }
    if (model.value === null || model.value === undefined) {
        return '';
    }
    if (typeof model.value === 'object') {
        return model.value.label ?? '';
    }
    return (props.options ?? results.value).find((o) => o.value === model.value)?.label ?? '';
});

async function runSearch() {
    if (props.options) {
        const q = query.value.trim().toLowerCase();
        results.value = props.options.filter((o) => !q || `${o.label} ${o.description ?? ''} ${o.keywords ?? ''}`.toLowerCase().includes(q)).slice(0, 50);
        return;
    }
    if (!props.search || query.value.trim().length < props.minChars) {
        results.value = [];
        return;
    }
    const current = ++requestId;
    loading.value = true;
    try {
        const found = await props.search(query.value.trim());
        if (current === requestId) {
            results.value = found ?? [];
        }
    } catch {
        if (current === requestId) {
            results.value = [];
        }
    } finally {
        if (current === requestId) {
            loading.value = false;
        }
    }
}

watch(query, () => {
    highlighted.value = 0;
    clearTimeout(timer);
    timer = setTimeout(runSearch, props.options ? 0 : 300);
});

function onFocus() {
    open.value = true;
    runSearch();
}

function choose(option) {
    model.value = props.returnObject ? option : option.value;
    emit('select', option);
    query.value = '';
    open.value = !props.clearOnSelect ? false : open.value;
    if (props.clearOnSelect) {
        input.value?.focus();
    } else {
        open.value = false;
    }
}

function onKey(event) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        open.value = true;
        highlighted.value = Math.min(highlighted.value + 1, results.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, 0);
    } else if (event.key === 'Enter') {
        if (open.value && results.value[highlighted.value]) {
            event.preventDefault();
            choose(results.value[highlighted.value]);
        }
    } else if (event.key === 'Escape') {
        if (open.value) {
            event.stopPropagation();
            open.value = false;
        }
    }
}

function onBlur() {
    setTimeout(() => {
        open.value = false;
    }, 150);
}

function clear() {
    model.value = null;
    emit('select', null);
    input.value?.focus();
}

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div class="relative">
        <div
            class="flex w-full items-center overflow-hidden rounded-md border bg-surface focus-within:ring-2"
            :class="[invalid ? 'border-danger focus-within:ring-danger/30' : 'border-border focus-within:border-accent focus-within:ring-accent/30', touch ? 'h-11' : 'h-7', disabled ? 'opacity-60' : '']"
        >
            <span class="pl-2 text-muted"><AppIcon name="magnifying-glass" size="sm" /></span>
            <input
                :id="id ?? undefined"
                ref="input"
                v-model="query"
                type="text"
                role="combobox"
                autocomplete="off"
                :aria-expanded="open ? 'true' : 'false'"
                :aria-controls="id ? `${id}-list` : undefined"
                :aria-invalid="invalid ? 'true' : undefined"
                :disabled="disabled"
                :placeholder="currentLabel || placeholder || t('common.search')"
                class="h-full w-full min-w-0 border-0 bg-transparent px-2 text-[13px] focus:outline-none"
                :class="currentLabel && !query ? 'placeholder:text-foreground' : 'placeholder:text-muted/60'"
                @focus="onFocus"
                @blur="onBlur"
                @keydown="onKey"
            />
            <AppIcon v-if="loading" name="spinner" spin size="sm" class="mr-2 text-muted" />
            <button v-else-if="model !== null && model !== undefined && !clearOnSelect" type="button" class="px-2 text-muted hover:text-foreground" :aria-label="t('common.clear')" @mousedown.prevent="clear">
                <AppIcon name="xmark" size="sm" />
            </button>
        </div>
        <ul
            v-if="open && (results.length || query.length >= minChars)"
            :id="id ? `${id}-list` : undefined"
            role="listbox"
            class="absolute z-50 mt-1 max-h-72 w-full overflow-y-auto rounded-md border border-border bg-surface p-1 text-[13px] shadow-lg"
        >
            <li
                v-for="(option, index) in results"
                :key="option.value"
                role="option"
                :aria-selected="index === highlighted ? 'true' : 'false'"
                class="cursor-pointer rounded px-2 py-1"
                :class="index === highlighted ? 'bg-accent/10' : 'hover:bg-subtle'"
                @mousedown.prevent="choose(option)"
                @mouseenter="highlighted = index"
            >
                <slot name="option" :option="option">
                    <div class="flex items-center justify-between gap-2">
                        <span class="truncate font-medium">{{ option.label }}</span>
                        <span v-if="option.meta" class="shrink-0 text-xs text-muted tabular-nums">{{ option.meta }}</span>
                    </div>
                    <div v-if="option.description" class="truncate text-xs text-muted">{{ option.description }}</div>
                </slot>
            </li>
            <li v-if="!loading && results.length === 0" class="px-2 py-1.5 text-muted">
                {{ t('common.no_results') }}
                <button v-if="allowCreate" type="button" class="ml-1 font-medium text-accent hover:underline" @mousedown.prevent="emit('create', query)">
                    {{ t('common.create_new') }}
                </button>
            </li>
        </ul>
    </div>
</template>
