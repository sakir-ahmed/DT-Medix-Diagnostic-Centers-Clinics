<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import FormField from '@/components/ui-kit/FormField.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import SlideOver from '@/components/ui-kit/SlideOver.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import SwitchField from '@/components/ui-kit/SwitchField.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { formatDate } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

defineProps({
    shifts: { type: Array, default: () => [] },
    devices: { type: Array, default: () => [] },
    holidays: { type: Array, default: () => [] },
    pushUrl: { type: String, required: true },
});

const { t } = useI18n();
const weekdays = [0, 1, 2, 3, 4, 5, 6];
const weekdayName = (day) => new Date(2024, 0, 7 + day).toLocaleDateString(undefined, { weekday: 'short' });

// Shift
const shiftOpen = ref(false);
const editingShift = ref(null);
const shiftForm = useForm({ name: '', start_time: '09:00', end_time: '18:00', grace_minutes: 10, weekly_off: [5], is_active: true });
function editShift(shift = null) {
    editingShift.value = shift;
    shiftForm.clearErrors();
    Object.assign(shiftForm, shift ? { name: shift.name, start_time: shift.start_time.slice(0, 5), end_time: shift.end_time.slice(0, 5), grace_minutes: shift.grace_minutes, weekly_off: [...(shift.weekly_off ?? [])], is_active: shift.is_active } : { name: '', start_time: '09:00', end_time: '18:00', grace_minutes: 10, weekly_off: [5], is_active: true });
    shiftOpen.value = true;
}
function saveShift() {
    const options = { preserveScroll: true, onError: focusFirstError, onSuccess: () => (shiftOpen.value = false) };
    editingShift.value ? shiftForm.put(route('admin.hr-setup.shifts.update', editingShift.value.id), options) : shiftForm.post(route('admin.hr-setup.shifts.store'), options);
}
function toggleOff(day) {
    shiftForm.weekly_off = shiftForm.weekly_off.includes(day) ? shiftForm.weekly_off.filter((value) => value !== day) : [...shiftForm.weekly_off, day];
}

// Device
const deviceOpen = ref(false);
const editingDevice = ref(null);
const deviceForm = useForm({ name: '', serial_number: '', location: '', is_active: true });
function editDevice(device = null) {
    editingDevice.value = device;
    deviceForm.clearErrors();
    Object.assign(deviceForm, device ? { name: device.name, serial_number: device.serial_number, location: device.location ?? '', is_active: device.is_active } : { name: '', serial_number: '', location: '', is_active: true });
    deviceOpen.value = true;
}
function saveDevice() {
    const options = { preserveScroll: true, onError: focusFirstError, onSuccess: () => (deviceOpen.value = false) };
    editingDevice.value ? deviceForm.put(route('admin.hr-setup.devices.update', editingDevice.value.id), options) : deviceForm.post(route('admin.hr-setup.devices.store'), options);
}

// Holiday
const holidayForm = useForm({ holiday_on: '', name: '' });
function addHoliday() {
    holidayForm.post(route('admin.hr-setup.holidays.store'), { preserveScroll: true, onError: focusFirstError, onSuccess: () => holidayForm.reset() });
}
</script>

<template>
    <div class="space-y-6">
        <Head :title="t('nav.hr_setup')" />
        <PageHeader :title="t('nav.hr_setup')" :description="t('hr.setup_description')" />

        <section class="card">
            <div class="flex items-center justify-between">
                <h2 class="section-title-sm">{{ t('hr.shifts') }}</h2>
                <UiButton variant="primary" icon="plus" @click="editShift()">{{ t('hr.new_shift') }}</UiButton>
            </div>
            <ul class="mt-2 divide-y divide-border text-[13px]">
                <li v-for="shift in shifts" :key="shift.id" class="flex flex-wrap items-center gap-3 py-2">
                    <span class="font-medium">{{ shift.name }}</span>
                    <span class="tabular-nums text-muted">{{ shift.start_time.slice(0, 5) }} - {{ shift.end_time.slice(0, 5) }}</span>
                    <span class="text-muted">{{ t('hr.grace_minutes_value', { minutes: shift.grace_minutes }) }}</span>
                    <span class="text-muted">{{ t('hr.weekly_off') }}: {{ (shift.weekly_off ?? []).map(weekdayName).join(', ') || t('common.none') }}</span>
                    <StatusBadge class="ml-auto" :status="shift.is_active ? 'active' : 'suspended'" :label="shift.is_active ? t('common.active') : t('common.inactive')" />
                    <UiButton size="xs" @click="editShift(shift)">{{ t('common.edit') }}</UiButton>
                </li>
                <li v-if="!shifts.length" class="py-3 text-muted">{{ t('hr.no_shifts') }}</li>
            </ul>
        </section>

        <section class="card">
            <div class="flex items-center justify-between">
                <h2 class="section-title-sm">{{ t('hr.devices') }}</h2>
                <UiButton variant="primary" icon="plus" @click="editDevice()">{{ t('hr.new_device') }}</UiButton>
            </div>
            <div class="mt-2 rounded-md border border-border bg-subtle p-3 text-[13px] leading-relaxed">
                <p class="font-medium"><AppIcon name="circle-info" size="sm" class="text-accent" /> {{ t('hr.device_howto_title') }}</p>
                <ol class="mt-1 list-decimal space-y-0.5 ps-5 text-muted">
                    <li>{{ t('hr.device_howto_1') }}</li>
                    <li>{{ t('hr.device_howto_2', { url: pushUrl }) }}</li>
                    <li>{{ t('hr.device_howto_3') }}</li>
                </ol>
            </div>
            <ul class="mt-2 divide-y divide-border text-[13px]">
                <li v-for="device in devices" :key="device.id" class="flex flex-wrap items-center gap-3 py-2">
                    <span class="font-medium">{{ device.name }}</span>
                    <span class="tabular-nums text-muted">{{ device.serial_number }}</span>
                    <span v-if="device.location" class="text-muted">{{ device.location }}</span>
                    <span class="text-muted">{{ device.last_seen_at ? t('hr.last_seen', { time: formatDate(device.last_seen_at, { time: true }) }) : t('hr.never_seen') }}</span>
                    <StatusBadge class="ml-auto" :status="device.is_active ? 'active' : 'suspended'" :label="device.is_active ? t('common.active') : t('common.inactive')" />
                    <UiButton size="xs" @click="editDevice(device)">{{ t('common.edit') }}</UiButton>
                </li>
                <li v-if="!devices.length" class="py-3 text-muted">{{ t('hr.no_devices') }}</li>
            </ul>
        </section>

        <section class="card">
            <h2 class="section-title-sm">{{ t('hr.holidays') }}</h2>
            <form class="mt-2 flex flex-wrap items-end gap-2" novalidate @submit.prevent="addHoliday">
                <FormField id="holiday_on" :label="t('fields.date')" :error="holidayForm.errors.holiday_on"><input id="holiday_on" v-model="holidayForm.holiday_on" type="date" class="control" /></FormField>
                <FormField id="holiday_name" :label="t('fields.name')" :error="holidayForm.errors.name"><TextInput id="holiday_name" v-model="holidayForm.name" /></FormField>
                <UiButton type="submit" variant="primary" icon="plus" :loading="holidayForm.processing">{{ t('common.add') }}</UiButton>
            </form>
            <ul class="mt-2 divide-y divide-border text-[13px]">
                <li v-for="holiday in holidays" :key="holiday.id" class="flex items-center gap-3 py-2">
                    <span class="w-28 tabular-nums">{{ formatDate(holiday.holiday_on) }}</span>
                    <span class="font-medium">{{ holiday.name }}</span>
                    <UiButton class="ml-auto" size="xs" variant="ghost-danger" icon="trash" @click="router.delete(route('admin.hr-setup.holidays.destroy', holiday.id), { preserveScroll: true })">{{ t('common.delete') }}</UiButton>
                </li>
                <li v-if="!holidays.length" class="py-3 text-muted">{{ t('hr.no_holidays') }}</li>
            </ul>
        </section>

        <SlideOver v-model:open="shiftOpen" :title="editingShift ? t('hr.edit_shift') : t('hr.new_shift')">
            <form id="shift-form" class="grid grid-cols-2 gap-x-4 gap-y-3" novalidate @submit.prevent="saveShift">
                <FormField id="shift_name" :label="t('fields.name')" :error="shiftForm.errors.name" required span><TextInput id="shift_name" v-model="shiftForm.name" /></FormField>
                <FormField id="start_time" :label="t('hr.start_time')" :error="shiftForm.errors.start_time" required><input id="start_time" v-model="shiftForm.start_time" type="time" class="control" /></FormField>
                <FormField id="end_time" :label="t('hr.end_time')" :error="shiftForm.errors.end_time" required><input id="end_time" v-model="shiftForm.end_time" type="time" class="control" /></FormField>
                <FormField id="grace_minutes" :label="t('hr.grace_minutes')" :error="shiftForm.errors.grace_minutes" required><input id="grace_minutes" v-model.number="shiftForm.grace_minutes" type="number" min="0" max="120" class="control" /></FormField>
                <fieldset class="col-span-full">
                    <legend class="label">{{ t('hr.weekly_off') }}</legend>
                    <div class="flex flex-wrap gap-1.5">
                        <button v-for="day in weekdays" :key="day" type="button" class="h-7 rounded-md border px-2.5 text-[13px]" :class="shiftForm.weekly_off.includes(day) ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-surface'" :aria-pressed="shiftForm.weekly_off.includes(day)" @click="toggleOff(day)">{{ weekdayName(day) }}</button>
                    </div>
                </fieldset>
                <div class="col-span-full"><SwitchField v-model="shiftForm.is_active" :label="t('common.active')" /></div>
            </form>
            <template #footer>
                <UiButton @click="shiftOpen = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="shift-form" variant="primary" icon="floppy-disk" :loading="shiftForm.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>

        <SlideOver v-model:open="deviceOpen" :title="editingDevice ? t('hr.edit_device') : t('hr.new_device')">
            <form id="device-form" class="grid grid-cols-1 gap-3" novalidate @submit.prevent="saveDevice">
                <FormField id="device_name" :label="t('fields.name')" :error="deviceForm.errors.name" required><TextInput id="device_name" v-model="deviceForm.name" /></FormField>
                <FormField id="serial_number" :label="t('hr.serial_number')" :helper="t('hr.serial_helper')" :error="deviceForm.errors.serial_number" required><TextInput id="serial_number" v-model="deviceForm.serial_number" :invalid="!!deviceForm.errors.serial_number" /></FormField>
                <FormField id="location" :label="t('hr.location')" optional><TextInput id="location" v-model="deviceForm.location" /></FormField>
                <SwitchField v-model="deviceForm.is_active" :label="t('common.active')" />
            </form>
            <template #footer>
                <UiButton @click="deviceOpen = false">{{ t('common.cancel') }}</UiButton>
                <UiButton type="submit" form="device-form" variant="primary" icon="floppy-disk" :loading="deviceForm.processing">{{ t('common.save') }}</UiButton>
            </template>
        </SlideOver>
    </div>
</template>
