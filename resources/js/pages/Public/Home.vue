<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

defineProps({ center: { type: Object, default: null } });

const { t } = useI18n();
const features = ['patients', 'lab', 'reports', 'billing', 'commission', 'messaging'];
const icons = { patients: 'hospital-user', lab: 'flask', reports: 'file-medical', billing: 'cash-register', commission: 'hand-holding-dollar', messaging: 'comment-sms' };
</script>

<template>
    <Head>
        <title>{{ center?.name ?? 'DT Medix' }}</title>
        <meta name="description" :content="center ? t('public.center_description', { name: center.name }) : t('public.tagline')" />
    </Head>
    <PublicLayout :center-name="center?.name">
        <section v-if="center" class="space-y-4">
            <h1 class="text-2xl font-bold">{{ center.name }}</h1>
            <p v-if="center.address" class="flex items-center gap-2 text-muted"><AppIcon name="location-dot" /> {{ center.address }}</p>
            <p v-if="center.phone" class="flex items-center gap-2 text-muted"><AppIcon name="phone" /> {{ center.phone }}</p>
            <div class="flex flex-wrap gap-2">
                <Link v-if="route().has('public.booking')" :href="route('public.booking')" class="inline-flex h-11 items-center gap-2 rounded-md bg-accent px-4 font-medium text-accent-foreground"><AppIcon name="calendar-check" /> {{ t('public.book_appointment') }}</Link>
                <Link v-if="route().has('portal.login')" :href="route('portal.login')" class="inline-flex h-11 items-center gap-2 rounded-md border border-border px-4 font-medium"><AppIcon name="file-medical" /> {{ t('public.my_reports') }}</Link>
            </div>
        </section>
        <section v-else class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold">DT Medix</h1>
                <p class="mt-1 text-muted">{{ t('public.tagline') }}</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="feature in features" :key="feature" class="card flex items-start gap-3">
                    <AppIcon :name="icons[feature]" size="lg" class="mt-0.5 text-accent" />
                    <div>
                        <h2 class="text-sm font-semibold">{{ t(`public.features.${feature}.title`) }}</h2>
                        <p class="text-[13px] text-muted">{{ t(`public.features.${feature}.text`) }}</p>
                    </div>
                </div>
            </div>
            <Link :href="route('login')" class="inline-flex h-11 items-center gap-2 rounded-md bg-primary px-4 font-medium text-primary-foreground">{{ t('auth.sign_in') }}</Link>
        </section>
    </PublicLayout>
</template>
