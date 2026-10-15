<script setup>
import { DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { useI18n } from '@/lib/i18n';
import AppIcon from './AppIcon.vue';
import FormField from './FormField.vue';
import SelectInput from './SelectInput.vue';
import TextArea from './TextArea.vue';
import TextInput from './TextInput.vue';
import UiButton from './UiButton.vue';

/**
 * Confirmation dialog with four levels (design.md section 12):
 * simple, reason (reason code + note), approval (manager login + PIN),
 * typed (type a word to enable the button).
 */
const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    variant: { type: String, default: 'danger' },
    level: { type: String, default: 'simple' },
    title: { type: String, required: true },
    message: { type: String, default: null },
    confirmText: { type: String, required: true },
    cancelText: { type: String, default: null },
    reasons: { type: Array, default: () => [] },
    facts: { type: Array, default: () => [] },
    typedWord: { type: String, default: 'CONFIRM' },
    noteRequired: { type: Boolean, default: false },
    processing: { type: Boolean, default: false },
    error: { type: String, default: null },
});

const emit = defineEmits(['confirm', 'cancel']);
const { t } = useI18n();

const form = reactive({ reason: null, note: '', login: '', pin: '', typed: '' });
const errors = reactive({});
const cancelButton = ref(null);

const circle = {
    danger: 'bg-danger/10 text-danger',
    warning: 'bg-warning/15 text-warning',
    info: 'bg-subtle text-foreground',
    success: 'bg-success/10 text-success',
};
const icons = { danger: 'triangle-exclamation', warning: 'triangle-exclamation', info: 'circle-info', success: 'circle-check' };

const needsReason = computed(() => ['reason', 'approval'].includes(props.level));
const canConfirm = computed(() => props.level !== 'typed' || form.typed.trim() === props.typedWord);

watch(open, async (value) => {
    if (value) {
        Object.assign(form, { reason: null, note: '', login: '', pin: '', typed: '' });
        Object.keys(errors).forEach((key) => delete errors[key]);
        await nextTick();
        if (props.variant === 'danger' && props.level === 'simple') {
            cancelButton.value?.$el?.focus();
        }
    }
});

function validate() {
    Object.keys(errors).forEach((key) => delete errors[key]);
    if (needsReason.value && props.reasons.length > 0 && !form.reason) {
        errors.reason = t('dialogs.reason_required');
    }
    if (needsReason.value && props.reasons.length === 0 && !form.note.trim()) {
        errors.note = t('dialogs.reason_required');
    }
    if (props.noteRequired && !form.note.trim()) {
        errors.note = t('dialogs.note_required');
    }
    if (props.level === 'approval') {
        if (!form.login.trim()) {
            errors.login = t('dialogs.approver_required');
        }
        if (!/^\d{4,6}$/.test(form.pin)) {
            errors.pin = t('dialogs.pin_invalid');
        }
    }

    return Object.keys(errors).length === 0;
}

function confirm() {
    if (!canConfirm.value || props.processing || !validate()) {
        return;
    }
    emit('confirm', {
        confirmed: true,
        reason: form.reason,
        note: form.note.trim() || null,
        login: form.login.trim() || null,
        pin: form.pin || null,
    });
}

function cancel() {
    if (props.processing) {
        return;
    }
    emit('cancel');
    open.value = false;
}

function guard(event) {
    if (props.processing) {
        event.preventDefault();
    }
}
</script>

<template>
    <DialogRoot :open="open" @update:open="(value) => (!value ? cancel() : null)">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-[2px]" />
            <DialogContent
                role="alertdialog"
                class="fixed inset-x-0 bottom-0 z-[60] w-full rounded-t-xl border border-border bg-surface p-4 shadow-lg focus:outline-none sm:inset-auto sm:top-1/2 sm:left-1/2 sm:max-w-md sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-xl"
                @escape-key-down="guard"
                @pointer-down-outside="guard"
                @interact-outside="guard"
            >
                <form class="space-y-3" @submit.prevent="confirm">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="circle[variant] ?? circle.info">
                            <AppIcon :name="level === 'approval' ? 'user-shield' : icons[variant] ?? 'circle-info'" size="xl" />
                        </span>
                        <div class="min-w-0">
                            <DialogTitle class="text-base font-semibold">{{ title }}</DialogTitle>
                            <DialogDescription v-if="message" class="mt-1 text-[13px] text-muted">{{ message }}</DialogDescription>
                        </div>
                    </div>

                    <dl v-if="facts.length" class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 rounded-md bg-subtle p-2 text-[13px]">
                        <template v-for="fact in facts" :key="fact.label">
                            <dt class="text-muted">{{ fact.label }}</dt>
                            <dd class="truncate font-medium tabular-nums">{{ fact.value }}</dd>
                        </template>
                    </dl>

                    <FormField v-if="needsReason && reasons.length" id="confirm-reason" :label="t('dialogs.reason')" :error="errors.reason" required>
                        <SelectInput id="confirm-reason" v-model="form.reason" :options="reasons" :placeholder="t('dialogs.choose_reason')" :invalid="!!errors.reason" />
                    </FormField>

                    <FormField
                        v-if="needsReason || noteRequired"
                        id="confirm-note"
                        :label="reasons.length ? t('dialogs.note') : t('dialogs.reason')"
                        :error="errors.note"
                        :required="!reasons.length || noteRequired"
                        :optional="reasons.length > 0 && !noteRequired"
                    >
                        <TextArea id="confirm-note" v-model="form.note" :rows="2" :invalid="!!errors.note" maxlength="500" />
                    </FormField>

                    <div v-if="level === 'approval'" class="grid grid-cols-2 gap-x-3 gap-y-3">
                        <FormField id="confirm-login" :label="t('dialogs.approver_login')" :error="errors.login" required>
                            <TextInput id="confirm-login" v-model="form.login" autocomplete="off" :invalid="!!errors.login" />
                        </FormField>
                        <FormField id="confirm-pin" :label="t('dialogs.approver_pin')" :error="errors.pin" required>
                            <TextInput id="confirm-pin" v-model="form.pin" type="password" inputmode="numeric" maxlength="6" autocomplete="off" :invalid="!!errors.pin" />
                        </FormField>
                    </div>

                    <FormField v-if="level === 'typed'" id="confirm-typed" :label="t('dialogs.type_to_confirm', { word: typedWord })" required>
                        <TextInput id="confirm-typed" v-model="form.typed" autocomplete="off" />
                    </FormField>

                    <p v-if="error" class="flex items-center gap-1.5 rounded-md bg-danger/10 px-2 py-1.5 text-[13px] text-danger" role="alert">
                        <AppIcon name="circle-xmark" size="sm" />
                        {{ error }}
                    </p>

                    <div class="flex justify-end gap-2 pt-1">
                        <UiButton ref="cancelButton" :disabled="processing" @click="cancel">{{ cancelText ?? t('common.cancel') }}</UiButton>
                        <UiButton
                            type="submit"
                            :variant="variant === 'danger' ? 'destructive' : 'primary'"
                            :loading="processing"
                            :disabled="!canConfirm"
                            :autofocus="variant !== 'danger'"
                        >
                            {{ confirmText }}
                        </UiButton>
                    </div>
                </form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
