<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import FormField from '@/components/ui-kit/FormField.vue';
import PageHeader from '@/components/ui-kit/PageHeader.vue';
import StatusBadge from '@/components/ui-kit/StatusBadge.vue';
import TextInput from '@/components/ui-kit/TextInput.vue';
import UiButton from '@/components/ui-kit/UiButton.vue';
import { focusFirstError } from '@/composables/useFormErrors';
import { useI18n } from '@/lib/i18n';

defineProps({
    twoFactorEnabled: { type: Boolean, default: false },
    twoFactorSetup: { type: Object, default: null },
    hasApprovalPin: { type: Boolean, default: false },
});

const { t } = useI18n();
const password = useForm({ current_password: '', password: '', password_confirmation: '' });
const pin = useForm({ current_password: '', pin: '', pin_confirmation: '' });
const confirm2fa = useForm({ code: '' });
const disable2fa = useForm({ current_password: '' });

const savePassword = () => password.put(route('admin.profile.password'), { preserveScroll: true, onError: focusFirstError, onSuccess: () => password.reset() });
const savePin = () => pin.put(route('admin.profile.pin'), { preserveScroll: true, onError: focusFirstError, onSuccess: () => pin.reset() });
const enable = () => router.post(route('admin.profile.two-factor.enable'), {}, { preserveScroll: true });
const confirm = () => confirm2fa.post(route('admin.profile.two-factor.confirm'), { preserveScroll: true, onError: focusFirstError });
const disable = () => disable2fa.delete(route('admin.profile.two-factor.disable'), { preserveScroll: true, onError: focusFirstError, onSuccess: () => disable2fa.reset() });
</script>

<template>
    <Head :title="t('nav.profile')" />
    <PageHeader :title="t('nav.profile')" />
    <div class="grid max-w-4xl gap-3 lg:grid-cols-2">
        <form class="card space-y-3" @submit.prevent="savePassword">
            <h2 class="text-sm font-semibold">{{ t('profile.change_password') }}</h2>
            <FormField id="current_password" :label="t('profile.current_password')" :error="password.errors.current_password" required>
                <TextInput id="current_password" v-model="password.current_password" type="password" autocomplete="current-password" :invalid="!!password.errors.current_password" />
            </FormField>
            <FormField id="password" :label="t('profile.new_password')" :error="password.errors.password" required>
                <TextInput id="password" v-model="password.password" type="password" autocomplete="new-password" :invalid="!!password.errors.password" />
            </FormField>
            <FormField id="password_confirmation" :label="t('profile.confirm_password')" required>
                <TextInput id="password_confirmation" v-model="password.password_confirmation" type="password" autocomplete="new-password" />
            </FormField>
            <div class="flex justify-end"><UiButton type="submit" variant="primary" icon="floppy-disk" :loading="password.processing">{{ t('profile.save_password') }}</UiButton></div>
        </form>

        <form class="card space-y-3" @submit.prevent="savePin">
            <h2 class="text-sm font-semibold">{{ t('profile.approval_pin') }} <StatusBadge :status="hasApprovalPin ? 'active' : 'pending'" :label="hasApprovalPin ? t('profile.pin_set') : t('profile.pin_not_set')" /></h2>
            <p class="text-xs text-muted">{{ t('profile.approval_pin_hint') }}</p>
            <FormField id="pin_current_password" :label="t('profile.current_password')" :error="pin.errors.current_password" required>
                <TextInput id="pin_current_password" v-model="pin.current_password" type="password" :invalid="!!pin.errors.current_password" />
            </FormField>
            <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                <FormField id="pin" :label="t('profile.pin')" :error="pin.errors.pin" required>
                    <TextInput id="pin" v-model="pin.pin" type="password" inputmode="numeric" maxlength="6" :invalid="!!pin.errors.pin" />
                </FormField>
                <FormField id="pin_confirmation" :label="t('profile.confirm_pin')" required>
                    <TextInput id="pin_confirmation" v-model="pin.pin_confirmation" type="password" inputmode="numeric" maxlength="6" />
                </FormField>
            </div>
            <div class="flex justify-end"><UiButton type="submit" variant="primary" icon="key" :loading="pin.processing">{{ t('profile.save_pin') }}</UiButton></div>
        </form>

        <section class="card space-y-3 lg:col-span-2">
            <h2 class="text-sm font-semibold">{{ t('profile.two_factor') }} <StatusBadge :status="twoFactorEnabled ? 'active' : 'pending'" :label="twoFactorEnabled ? t('profile.enabled') : t('profile.disabled')" /></h2>
            <p class="text-xs text-muted">{{ t('profile.two_factor_hint') }}</p>
            <div v-if="!twoFactorEnabled && !twoFactorSetup"><UiButton icon="lock" @click="enable">{{ t('profile.enable_two_factor') }}</UiButton></div>
            <form v-else-if="twoFactorSetup" class="flex flex-wrap items-end gap-4" @submit.prevent="confirm">
                <img :src="twoFactorSetup.qr" alt="" class="h-40 w-40 rounded border border-border bg-white" />
                <div class="space-y-3">
                    <p class="text-xs text-muted">{{ t('profile.scan_qr') }}</p>
                    <code class="block rounded bg-subtle px-2 py-1 text-xs break-all">{{ twoFactorSetup.secret }}</code>
                    <FormField id="code" :label="t('auth.code')" :error="confirm2fa.errors.code" required>
                        <TextInput id="code" v-model="confirm2fa.code" inputmode="numeric" maxlength="6" :invalid="!!confirm2fa.errors.code" />
                    </FormField>
                    <UiButton type="submit" variant="primary" :loading="confirm2fa.processing">{{ t('profile.confirm_two_factor') }}</UiButton>
                </div>
            </form>
            <form v-else class="flex flex-wrap items-end gap-2" @submit.prevent="disable">
                <FormField id="disable_password" :label="t('profile.current_password')" :error="disable2fa.errors.current_password" required>
                    <TextInput id="disable_password" v-model="disable2fa.current_password" type="password" :invalid="!!disable2fa.errors.current_password" />
                </FormField>
                <UiButton type="submit" variant="destructive" :loading="disable2fa.processing">{{ t('profile.disable_two_factor') }}</UiButton>
            </form>
        </section>
    </div>
</template>
