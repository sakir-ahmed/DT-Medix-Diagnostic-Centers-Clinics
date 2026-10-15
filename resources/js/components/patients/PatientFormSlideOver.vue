<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import FileUpload from '@/components/ui-kit/FileUpload.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import MobileInput from '@/components/ui-kit/MobileInput.vue';
import RadioCards from '@/components/ui-kit/RadioCards.vue';
import SelectInput from '@/components/ui-kit/SelectInput.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TextArea from '@/components/ui-kit/TextArea.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

/**
 * Create or edit a patient in a slide-over. On a suspected duplicate the
 * server returns candidates: "Use existing" or "Create new anyway".
 */
const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    patient: { type: Object, default: null },
    bloodGroups: { type: Array, default: () => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] },
    stay: { type: Boolean, default: false },
    initialQuery: { type: String, default: '' },
});

const emit = defineEmits(['saved', 'use-existing']);
const { t } = useI18n();
const page = usePage();

function blank() {
    const digits = /^[\d+\s-]+$/.test(props.initialQuery) ? props.initialQuery.replace(/\D/g, '') : '';
    return {
        name: digits ? '' : props.initialQuery,
        name_bn: '',
        sex: null,
        date_of_birth: '',
        age_years: '',
        age_months: '',
        mobile: digits,
        relation_to_mobile_owner: '',
        address: '',
        blood_group: null,
        nid: '',
        emergency_contact_name: '',
        emergency_mobile: '',
        allergies: '',
        notes: '',
        preferred_locale: 'en',
        messages_opt_out: false,
        confirm_new: false,
        photo: null,
        return_to: props.stay ? 'stay' : null,
    };
}

const form = useForm(blank());
const editing = computed(() => !!props.patient);
const duplicates = computed(() => page.props.flash?.duplicates ?? []);

watch(open, (value) => {
    if (!value) {
        return;
    }
    form.clearErrors();
    if (props.patient) {
        Object.assign(form, {
            ...blank(),
            ...props.patient,
            mobile: props.patient.mobile ?? '',
            emergency_mobile: props.patient.emergency_mobile ?? '',
            date_of_birth: props.patient.dob_estimated ? '' : (props.patient.date_of_birth ?? ''),
            age_years: props.patient.dob_estimated ? props.patient.age_years : '',
            nid: '',
            photo: null,
        });
    } else {
        Object.assign(form, blank());
    }
});

const sexOptions = computed(() => ['male', 'female', 'other'].map((value) => ({ value, label: t(`patients.sex.${value}`) })));

function submit(confirmNew = false) {
    form.confirm_new = confirmNew;
    const url = editing.value ? route('admin.patients.update', props.patient.id) : route('admin.patients.store');
    form.transform((data) => {
        const payload = { ...data };
        if (editing.value && !payload.nid) {
            delete payload.nid;
        }
        if (!payload.photo) {
            delete payload.photo;
        }
        return payload;
    }).post(url, {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => {
            if (!errors.duplicate) {
                focusFirstError(errors);
            }
        },
        onSuccess: (resultPage) => {
            if (!resultPage.props.flash?.duplicates?.length && !Object.keys(form.errors).length) {
                open.value = false;
                emit('saved', resultPage.props.flash?.created_patient_id ?? props.patient?.id);
            }
        },
    });
}
</script>

<template>
    <SlideOver v-model:open="open" :title="editing ? t('patients.edit_patient') : t('patients.new_patient')" :description="editing ? patient.patient_code : t('patients.new_patient_hint')" width="md">
        <div v-if="form.errors.duplicate && duplicates.length" class="mb-3 rounded-md border border-warning bg-warning/10 p-3" role="alert">
            <p class="flex items-start gap-2 text-[13px] font-medium">
                <AppIcon name="triangle-exclamation" class="mt-0.5 text-warning" />
                {{ form.errors.duplicate }}
            </p>
            <ul class="mt-2 space-y-1">
                <li v-for="candidate in duplicates" :key="candidate.id" class="flex items-center justify-between gap-2 rounded bg-surface px-2 py-1 text-[13px]">
                    <span class="min-w-0 truncate">
                        <strong>{{ candidate.name }}</strong>
                        <span class="ml-1 text-muted">{{ candidate.patient_code }} | {{ candidate.age_text }} {{ t(`patients.sex_short.${candidate.sex}`) }}</span>
                    </span>
                    <UiButton v-if="stay" size="xs" variant="primary" @click="emit('use-existing', candidate.id); open = false">{{ t('patients.use_existing') }}</UiButton>
                    <UiButton v-else size="xs" variant="primary" :href="route('admin.patients.show', candidate.id)">{{ t('patients.use_existing') }}</UiButton>
                </li>
            </ul>
            <UiButton class="mt-2" size="xs" @click="submit(true)">{{ t('patients.create_anyway') }}</UiButton>
        </div>

        <form id="patient-form" class="grid grid-cols-2 gap-x-4 gap-y-3" novalidate @submit.prevent="submit(false)">
            <FormField id="name" :label="t('fields.name')" :error="form.errors.name" required span>
                <TextInput id="name" v-model="form.name" autocomplete="off" :invalid="!!form.errors.name" />
            </FormField>
            <FormField id="name_bn" :label="t('fields.name_bn')" :error="form.errors.name_bn" optional span>
                <TextInput id="name_bn" v-model="form.name_bn" lang="bn" autocomplete="off" />
            </FormField>
            <FormField id="sex" :label="t('patients.sex_label')" :error="form.errors.sex" required span>
                <RadioCards id="sex" v-model="form.sex" name="sex" :options="sexOptions" />
            </FormField>
            <FormField id="age_years" :label="t('patients.age')" :error="form.errors.age_years ?? form.errors.age_months" :helper="t('patients.age_helper')">
                <div class="flex gap-2">
                    <TextInput id="age_years" v-model="form.age_years" inputmode="numeric" suffix="Y" align="right" :invalid="!!form.errors.age_years" :aria-label="t('patients.age_years')" />
                    <TextInput id="age_months" v-model="form.age_months" inputmode="numeric" suffix="M" align="right" :aria-label="t('patients.age_months_label')" />
                </div>
            </FormField>
            <FormField id="date_of_birth" :label="t('patients.date_of_birth')" :error="form.errors.date_of_birth" optional>
                <input id="date_of_birth" v-model="form.date_of_birth" type="date" class="control" :max="new Date().toISOString().slice(0, 10)" />
            </FormField>
            <FormField id="mobile" :label="t('fields.mobile')" :error="form.errors.mobile" :helper="t('patients.mobile_helper')">
                <MobileInput id="mobile" v-model="form.mobile" :invalid="!!form.errors.mobile" />
            </FormField>
            <FormField id="relation_to_mobile_owner" :label="t('patients.relation')" :error="form.errors.relation_to_mobile_owner" optional>
                <TextInput id="relation_to_mobile_owner" v-model="form.relation_to_mobile_owner" :placeholder="t('patients.relation_placeholder')" />
            </FormField>
            <FormField id="blood_group" :label="t('patients.blood_group')" :error="form.errors.blood_group" optional>
                <SelectInput id="blood_group" v-model="form.blood_group" :placeholder="t('patients.unknown')" :options="bloodGroups.map((g) => ({ value: g, label: g }))" />
            </FormField>
            <FormField id="nid" :label="t('patients.nid')" :error="form.errors.nid" :helper="editing && patient.nid_last4 ? t('patients.nid_saved', { last4: patient.nid_last4 }) : null" optional>
                <TextInput id="nid" v-model="form.nid" inputmode="numeric" autocomplete="off" :invalid="!!form.errors.nid" />
            </FormField>
            <FormField id="allergies" :label="t('patients.allergies')" :error="form.errors.allergies" optional span>
                <TextInput id="allergies" v-model="form.allergies" :placeholder="t('patients.allergies_placeholder')" />
            </FormField>
            <FormField id="address" :label="t('fields.address')" :error="form.errors.address" optional span>
                <TextArea id="address" v-model="form.address" :rows="2" />
            </FormField>
            <FormField id="emergency_contact_name" :label="t('patients.emergency_contact')" :error="form.errors.emergency_contact_name" optional>
                <TextInput id="emergency_contact_name" v-model="form.emergency_contact_name" />
            </FormField>
            <FormField id="emergency_mobile" :label="t('patients.emergency_mobile')" :error="form.errors.emergency_mobile" optional>
                <MobileInput id="emergency_mobile" v-model="form.emergency_mobile" :invalid="!!form.errors.emergency_mobile" />
            </FormField>
            <FormField id="preferred_locale" :label="t('patients.preferred_language')" optional>
                <SelectInput id="preferred_locale" v-model="form.preferred_locale" :options="[{ value: 'en', label: 'English' }, { value: 'bn', label: 'বাংলা' }]" />
            </FormField>
            <div class="flex items-end pb-1">
                <SwitchField v-model="form.messages_opt_out" :label="t('patients.opt_out')" />
            </div>
            <FormField id="notes" :label="t('fields.notes')" :error="form.errors.notes" optional span>
                <TextArea id="notes" v-model="form.notes" :rows="2" />
            </FormField>
            <FormField id="photo" :label="t('patients.photo')" :error="form.errors.photo" optional span>
                <FileUpload id="photo" v-model="form.photo" accept="image/*" :max-size-mb="3" />
            </FormField>
        </form>
        <p class="mt-3 text-xs text-muted">* {{ t('common.required') }}</p>

        <template #footer>
            <UiButton @click="open = false">{{ t('common.cancel') }}</UiButton>
            <UiButton type="submit" form="patient-form" variant="primary" icon="floppy-disk" :loading="form.processing">
                {{ editing ? t('common.save_changes') : t('patients.save_patient') }}
            </UiButton>
        </template>
    </SlideOver>
</template>
