<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';
import ComboBox from './ComboBox.vue';

const model = defineModel({ type: Array, default: () => [] });

const props = defineProps({
    id: { type: String, default: null },
    options: { type: Array, required: true },
    placeholder: { type: String, default: null },
    invalid: { type: Boolean, default: false },
});

const available = computed(() => props.options.filter((o) => !model.value.includes(o.value)));
const chosen = computed(() => props.options.filter((o) => model.value.includes(o.value)));

function add(option) {
    if (option && !model.value.includes(option.value)) {
        model.value = [...model.value, option.value];
    }
}

function remove(value) {
    model.value = model.value.filter((v) => v !== value);
}
</script>

<template>
    <div class="space-y-1.5">
        <ComboBox :id="id" :model-value="null" :options="available" :placeholder="placeholder" :invalid="invalid" clear-on-select @select="add" />
        <div v-if="chosen.length" class="flex flex-wrap gap-1">
            <span v-for="option in chosen" :key="option.value" class="inline-flex h-6 items-center gap-1 rounded-md bg-accent/10 px-2 text-xs">
                {{ option.label }}
                <button type="button" class="text-muted hover:text-foreground" :aria-label="$t('common.remove')" @click="remove(option.value)">
                    <AppIcon name="xmark" size="xs" />
                </button>
            </span>
        </div>
    </div>
</template>
