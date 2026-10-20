<script setup>
import { ref, watch } from 'vue';
import FormField from '@/components/ui-kit/FormField.vue';
import ModalDialog from '@/components/ui-kit/ModalDialog.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { useI18n } from '@/lib/i18n';

/**
 * Records who was informed about a critical value and when, before the
 * result can move on (design.md section 9).
 */
const open = defineModel('open', { type: Boolean, default: false });

const emit = defineEmits(['confirm']);

const { t } = useI18n();
const informedName = ref('');
const informedAt = ref('');

watch(open, (value) => {
    if (value) {
        informedName.value = '';
        informedAt.value = '';
    }
});

function confirm() {
    if (informedName.value.trim() === '') {
        return;
    }

    emit('confirm', { informed_name: informedName.value.trim(), informed_at: informedAt.value || null });
    open.value = false;
}
</script>

<template>
    <ModalDialog v-model:open="open" :title="t('lab.critical_acknowledge')" size="md">
        <p class="text-[13px] text-muted">{{ t('lab.critical_banner') }}</p>
        <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-3">
            <FormField id="critical-informed-name" :label="t('lab.informed_name')" :helper="t('lab.informed_name_hint')" required span>
                <TextInput
                    :id="'critical-informed-name'"
                    v-model="informedName"
                    autocomplete="off"
                    class="w-full"
                    @keydown.enter.prevent="confirm"
                />
            </FormField>
            <FormField id="critical-informed-at" :label="t('lab.informed_at')" optional span>
                <TextInput :id="'critical-informed-at'" v-model="informedAt" type="datetime-local" class="w-full" />
            </FormField>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
                <UiButton variant="primary" icon="circle-check" :disabled="informedName.trim() === ''" @click="confirm">
                    {{ t('lab.critical_acknowledge') }}
                </UiButton>
            </div>
        </template>
    </ModalDialog>
</template>
