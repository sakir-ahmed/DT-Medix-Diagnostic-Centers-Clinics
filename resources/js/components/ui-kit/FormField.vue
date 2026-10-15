<script setup>
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, default: null },
    error: { type: [String, Array], default: null },
    helper: { type: String, default: null },
    required: { type: Boolean, default: false },
    optional: { type: Boolean, default: false },
    locked: { type: Boolean, default: false },
    lockedReason: { type: String, default: null },
    span: { type: Boolean, default: false },
});

const { t } = useI18n();
const message = computed(() => (Array.isArray(props.error) ? props.error[0] : props.error));
const describedBy = computed(() => (message.value ? `${props.id}-error` : props.helper ? `${props.id}-helper` : undefined));
</script>

<template>
    <div :class="span ? 'col-span-full' : ''">
        <label v-if="label" :for="id" class="label">
            {{ label }}
            <span v-if="required" class="text-danger" aria-hidden="true">*</span>
            <span v-else-if="optional" class="font-normal text-muted">({{ t('common.optional') }})</span>
            <AppIcon v-if="locked" name="lock" size="xs" class="ml-1 text-muted" :label="lockedReason ?? t('common.locked')" />
        </label>
        <slot :id="id" :invalid="!!message" :described-by="describedBy" />
        <p v-if="message" :id="`${id}-error`" class="mt-1 flex items-center gap-1 text-xs text-danger" role="alert">
            <AppIcon name="circle-exclamation" size="sm" />
            <span>{{ message }}</span>
        </p>
        <p v-else-if="helper" :id="`${id}-helper`" class="mt-1 text-xs text-muted">{{ helper }}</p>
    </div>
</template>
