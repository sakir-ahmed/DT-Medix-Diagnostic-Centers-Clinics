<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatMoney } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

defineProps({
    centerName: { type: String, default: null },
    departments: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
});

const { t } = useI18n();
const openDepartment = ref(null);

const departmentIcons = { pathology: 'vial', imaging: 'display', cardiology: 'heart-pulse', other: 'stethoscope' };
</script>

<template>
    <Head>
        <title>{{ t('public.tests_page_title') }}</title>
        <meta name="description" :content="t('public.tests_lead')" />
    </Head>
    <PublicLayout :center-name="centerName">
        <section class="py-4">
            <p class="badge-line">{{ t('public.tests_badge') }}</p>
            <h1 class="mt-2 font-display text-2xl font-bold tracking-tight lg:text-3xl">{{ t('public.tests_page_title') }}</h1>
            <p class="mt-2 max-w-2xl text-[15px] leading-relaxed text-muted">{{ t('public.tests_lead') }}</p>
        </section>

        <section v-if="packages.length" class="mt-4">
            <h2 class="section-title">{{ t('public.packages_title') }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="pkg in packages" :key="pkg.id" class="card flex flex-col border-primary/30 bg-primary/5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="truncate text-[15px] font-semibold">{{ pkg.name }}</h3>
                            <p v-if="pkg.description" class="mt-0.5 text-xs text-muted">{{ pkg.description }}</p>
                        </div>
                        <span class="shrink-0 font-display text-lg font-bold text-primary tabular-nums">{{ formatMoney(pkg.price) }}</span>
                    </div>
                    <ul class="mt-2.5 space-y-1 text-[13px] text-muted">
                        <li v-for="test in pkg.tests" :key="test" class="flex items-center gap-1.5">
                            <AppIcon name="check" size="sm" class="shrink-0 text-accent" /> {{ test }}
                        </li>
                    </ul>
                </article>
            </div>
        </section>

        <section class="mt-8">
            <h2 class="section-title">{{ t('public.all_tests_title') }}</h2>
            <div class="mt-3 space-y-2">
                <div v-for="department in departments" :key="department.id" class="overflow-hidden rounded-lg border border-border bg-surface">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors duration-100 hover:bg-subtle focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none"
                        :aria-expanded="openDepartment === department.id"
                        @click="openDepartment = openDepartment === department.id ? null : department.id"
                    >
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                            <AppIcon :name="departmentIcons[department.category] ?? 'stethoscope'" />
                        </span>
                        <span class="min-w-0 flex-1 text-[15px] font-semibold">{{ department.name }}</span>
                        <span class="text-xs text-muted">{{ t('public.tests_count', { count: department.tests.length }) }}</span>
                        <AppIcon :name="openDepartment === department.id ? 'chevron-up' : 'chevron-down'" class="text-muted" />
                    </button>
                    <div v-if="openDepartment === department.id" class="border-t border-border">
                        <ul class="divide-y divide-border">
                            <li v-for="test in department.tests" :key="test.name" class="flex items-center gap-3 px-4 py-2.5">
                                <AppIcon name="flask" class="shrink-0 text-accent" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[14px] font-medium">{{ test.name }}</p>
                                    <p v-if="test.preparation" class="truncate text-xs text-muted">{{ t('public.preparation') }}: {{ test.preparation }}</p>
                                </div>
                                <span class="hidden items-center gap-1 text-xs text-muted sm:flex">
                                    <AppIcon name="clock" size="sm" /> {{ t('public.report_in', { hours: test.tat_hours }) }}
                                </span>
                                <span class="w-24 text-right text-[14px] font-semibold tabular-nums">{{ formatMoney(test.price) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
