<script setup>
import { computed, ref } from 'vue';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';

/**
 * Drag and drop upload with preview and size/type errors.
 * v-model holds the File (or array of Files when multiple).
 */
const model = defineModel({ type: [Object, Array, null], default: null });

const props = defineProps({
    id: { type: String, required: true },
    accept: { type: String, default: 'image/*,application/pdf' },
    maxSizeMb: { type: Number, default: 10 },
    multiple: { type: Boolean, default: false },
    progress: { type: Number, default: null },
    invalid: { type: Boolean, default: false },
});

const { t } = useI18n();
const dragging = ref(false);
const error = ref(null);
const input = ref(null);

const files = computed(() => (Array.isArray(model.value) ? model.value : model.value ? [model.value] : []));

function accepted(file) {
    const rules = props.accept.split(',').map((r) => r.trim());
    return rules.some((rule) => (rule.endsWith('/*') ? file.type.startsWith(rule.slice(0, -1)) : rule.startsWith('.') ? file.name.toLowerCase().endsWith(rule) : file.type === rule));
}

function take(list) {
    error.value = null;
    const chosen = Array.from(list ?? []);
    for (const file of chosen) {
        if (!accepted(file)) {
            error.value = t('upload.type_error', { name: file.name });
            return;
        }
        if (file.size > props.maxSizeMb * 1024 * 1024) {
            error.value = t('upload.size_error', { name: file.name, size: props.maxSizeMb });
            return;
        }
    }
    model.value = props.multiple ? [...files.value, ...chosen] : (chosen[0] ?? null);
}

function remove(index) {
    if (props.multiple) {
        model.value = files.value.filter((_, i) => i !== index);
    } else {
        model.value = null;
    }
}

const preview = (file) => (file.type?.startsWith('image/') ? URL.createObjectURL(file) : null);
</script>

<template>
    <div>
        <div
            class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-md border border-dashed px-3 py-4 text-center text-[13px] transition-colors"
            :class="[dragging ? 'border-accent bg-accent/5' : invalid || error ? 'border-danger' : 'border-border hover:bg-subtle']"
            role="button"
            tabindex="0"
            @click="input?.click()"
            @keydown.enter.prevent="input?.click()"
            @dragover.prevent="dragging = true"
            @dragleave="dragging = false"
            @drop.prevent="dragging = false; take($event.dataTransfer.files)"
        >
            <AppIcon name="upload" size="lg" class="text-muted" />
            <span>{{ t('upload.drop_or_click') }}</span>
            <span class="text-xs text-muted">{{ t('upload.limit', { size: maxSizeMb }) }}</span>
            <input :id="id" ref="input" type="file" class="sr-only" :accept="accept" :multiple="multiple" @change="take($event.target.files)" />
        </div>
        <p v-if="error" class="mt-1 text-xs text-danger" role="alert">{{ error }}</p>
        <ul v-if="files.length" class="mt-2 space-y-1">
            <li v-for="(file, index) in files" :key="index" class="flex items-center gap-2 rounded-md border border-border px-2 py-1 text-[13px]">
                <img v-if="preview(file)" :src="preview(file)" alt="" class="h-8 w-8 rounded object-cover" />
                <AppIcon v-else :name="file.type === 'application/pdf' ? 'file-pdf' : 'file-lines'" class="text-muted" />
                <span class="min-w-0 flex-1 truncate">{{ file.name }}</span>
                <span class="text-xs text-muted tabular-nums">{{ Math.ceil(file.size / 1024) }} KB</span>
                <button type="button" class="text-muted hover:text-danger" :aria-label="t('common.remove')" @click="remove(index)">
                    <AppIcon name="xmark" size="sm" />
                </button>
            </li>
        </ul>
        <div v-if="progress !== null" class="mt-2 h-1.5 overflow-hidden rounded bg-subtle">
            <div class="h-full bg-accent transition-all" :style="{ width: `${progress}%` }" />
        </div>
    </div>
</template>
