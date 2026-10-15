<script setup>
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';
import TextInput from './TextInput.vue';
import UiButton from './UiButton.vue';

/**
 * Search (300ms debounce) plus filter controls. Filters live in the URL so
 * refresh and Back work. Pass `filters` (current values) and render the
 * controls in the default slot bound to `form`.
 */
const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    searchKey: { type: String, default: 'q' },
    searchPlaceholder: { type: String, default: null },
    applyButton: { type: Boolean, default: false },
    chips: { type: Array, default: () => [] },
    only: { type: Array, default: null },
});

const { t } = useI18n();
const form = ref({ ...props.filters });
let timer = null;

watch(
    () => props.filters,
    (value) => {
        form.value = { ...value };
    },
);

function apply() {
    const query = Object.fromEntries(Object.entries(form.value).filter(([, v]) => v !== null && v !== undefined && v !== ''));
    router.get(window.location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: props.only ?? undefined,
    });
}

watch(
    () => form.value[props.searchKey],
    (value, old) => {
        if (value === old) {
            return;
        }
        clearTimeout(timer);
        timer = setTimeout(apply, 300);
    },
);

watch(
    () => Object.entries(form.value).filter(([key]) => key !== props.searchKey).map(([, v]) => v).join('|'),
    () => {
        if (!props.applyButton) {
            apply();
        }
    },
);

const active = computed(() => Object.entries(props.filters).filter(([, v]) => v !== null && v !== undefined && v !== ''));

function reset() {
    form.value = {};
    apply();
}

function removeChip(key) {
    form.value = { ...form.value, [key]: null };
    apply();
}

defineExpose({ apply, reset, form });
</script>

<template>
    <div class="mb-3 space-y-2">
        <div class="flex flex-wrap items-center gap-2">
            <div class="w-full sm:w-64">
                <TextInput
                    v-model="form[searchKey]"
                    icon="magnifying-glass"
                    clearable
                    :placeholder="searchPlaceholder ?? t('common.search')"
                    :aria-label="searchPlaceholder ?? t('common.search')"
                    data-search
                />
            </div>
            <slot :form="form" :apply="apply" />
            <UiButton v-if="applyButton" variant="primary" @click="apply">{{ t('common.apply') }}</UiButton>
            <button v-if="active.length" type="button" class="text-xs text-accent hover:underline" @click="reset">{{ t('common.reset') }}</button>
        </div>
        <div v-if="chips.length" class="flex flex-wrap gap-1.5">
            <span v-for="chip in chips" :key="chip.key" class="inline-flex h-6 items-center gap-1 rounded-md bg-subtle px-2 text-xs">
                {{ chip.label }}
                <button type="button" class="text-muted hover:text-foreground" :aria-label="t('common.remove')" @click="removeChip(chip.key)">
                    <AppIcon name="xmark" size="xs" />
                </button>
            </span>
        </div>
    </div>
</template>
