<script setup>
import FileUpload from '@/components/ui-kit/FileUpload.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import MobileInput from '@/components/ui-kit/MobileInput.vue';
import MoneyInput from '@/components/ui-kit/MoneyInput.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import { useI18n } from '@/lib/i18n';

defineProps({
    id: { type: String, required: true },
    form: { type: Object, required: true },
    doctorUsers: { type: Array, default: () => [] },
    signatureUrl: { type: String, default: null },
});

defineEmits(['submit']);
const { t } = useI18n();
</script>

<template>
    <form :id="id" class="grid grid-cols-2 gap-x-4 gap-y-3" novalidate @submit.prevent="$emit('submit')">
        <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required span>
            <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
        </FormField>
        <FormField id="name_bn" :label="t('fields.name_bn')" :error="form.errors.name_bn" optional span>
            <TextInput id="name_bn" v-model="form.name_bn" lang="bn" />
        </FormField>
        <FormField id="specialty" :label="t('doctors.specialty')" :error="form.errors.specialty" optional>
            <TextInput id="specialty" v-model="form.specialty" />
        </FormField>
        <FormField id="bmdc_no" :label="t('doctors.bmdc')" :error="form.errors.bmdc_no" optional>
            <TextInput id="bmdc_no" v-model="form.bmdc_no" :invalid="!!form.errors.bmdc_no" />
        </FormField>
        <FormField id="qualifications" :label="t('doctors.qualifications')" :error="form.errors.qualifications" optional span>
            <TextInput id="qualifications" v-model="form.qualifications" :placeholder="t('doctors.qualifications_placeholder')" />
        </FormField>
        <FormField id="designation" :label="t('staff.designation')" :error="form.errors.designation" optional span>
            <TextInput id="designation" v-model="form.designation" />
        </FormField>
        <FormField id="mobile" :label="t('fields.mobile')" :error="form.errors.mobile" optional>
            <MobileInput id="mobile" v-model="form.mobile" :invalid="!!form.errors.mobile" />
        </FormField>
        <FormField id="email" :label="t('fields.email')" :error="form.errors.email" optional>
            <TextInput id="email" v-model="form.email" type="email" />
        </FormField>
        <FormField id="consultation_fee" :label="t('doctors.consultation_fee')" :error="form.errors.consultation_fee" required>
            <MoneyInput id="consultation_fee" v-model="form.consultation_fee" :invalid="!!form.errors.consultation_fee" />
        </FormField>
        <FormField id="follow_up_fee" :label="t('doctors.follow_up_fee')" :error="form.errors.follow_up_fee" required>
            <MoneyInput id="follow_up_fee" v-model="form.follow_up_fee" :invalid="!!form.errors.follow_up_fee" />
        </FormField>
        <FormField id="follow_up_days" :label="t('doctors.follow_up_days')" :error="form.errors.follow_up_days" :helper="t('doctors.follow_up_days_helper')" optional>
            <TextInput id="follow_up_days" v-model="form.follow_up_days" inputmode="numeric" align="right" :suffix="t('settings.days')" />
        </FormField>
        <FormField id="slot_minutes" :label="t('doctors.slot_minutes')" :error="form.errors.slot_minutes" required>
            <TextInput id="slot_minutes" v-model="form.slot_minutes" inputmode="numeric" align="right" :suffix="t('settings.minutes')" :invalid="!!form.errors.slot_minutes" />
        </FormField>
        <FormField id="user_id" :label="t('doctors.login_account')" :error="form.errors.user_id" :helper="t('doctors.login_account_helper')" optional span>
            <SelectInput id="user_id" v-model="form.user_id" :placeholder="t('common.none')" :options="doctorUsers" />
        </FormField>
        <FormField id="signature" :label="t('doctors.signature')" :error="form.errors.signature" optional span>
            <img v-if="signatureUrl" :src="signatureUrl" alt="" class="mb-2 h-14 rounded border border-border bg-white p-1" />
            <FileUpload id="signature" v-model="form.signature" accept="image/png,image/jpeg" :max-size-mb="1" />
        </FormField>
        <div class="col-span-full flex flex-wrap gap-4">
            <SwitchField v-model="form.is_visiting" :label="t('doctors.visiting')" />
            <SwitchField v-model="form.is_active" :label="t('common.active')" />
        </div>
    </form>
</template>
