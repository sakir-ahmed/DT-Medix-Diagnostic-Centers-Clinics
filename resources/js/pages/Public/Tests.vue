<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageHero from '@/components/public/PageHero.vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatMoney } from '@/lib/format';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: null });

const props = defineProps({
    centerName: { type: String, default: null },
    departments: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
});

const { t } = useI18n();
const search = ref('');
const activeDepartment = ref(null);
const openDepartment = ref(null);

const departmentIcons = { pathology: 'vial', imaging: 'x-ray', cardiology: 'heart-pulse', other: 'stethoscope' };

const term = computed(() => search.value.trim().toLowerCase());
const visibleDepartments = computed(() => props.departments
    .filter((department) => activeDepartment.value === null || department.id === activeDepartment.value)
    .map((department) => ({
        ...department,
        tests: term.value === '' ? department.tests : department.tests.filter((test) => test.name.toLowerCase().includes(term.value)),
    }))
    .filter((department) => department.tests.length > 0));

const isOpen = (department) => term.value !== '' || activeDepartment.value !== null || openDepartment.value === department.id;
</script>

<template>
    <Head>
        <title>{{ t('public.tests_page_title') }}</title>
        <meta name="description" :content="t('public.tests_lead')" />
    </Head>
    <PublicLayout :center-name="centerName" :contained="false">
        <PageHero :badge="t('public.tests_badge')" :title="t('public.tests_page_title')" :lead="t('public.tests_lead')" icon="flask">
            <Link :href="route('public.booking')" class="btn-primary-lg"><AppIcon name="calendar-check" /> {{ t('public.book_appointment') }}</Link>
        </PageHero>

        <section v-if="packages.length" class="site-container site-section !pb-4">
            <h2 class="site-h2">{{ t('public.packages_title') }}</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="pkg in packages" :key="pkg.id" class="site-card flex flex-col border-primary/30 bg-primary/5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="truncate text-[15px] font-semibold">{{ pkg.name }}</h3>
                            <p v-if="pkg.description" class="mt-0.5 text-xs text-muted">{{ pkg.description }}</p>
                        </div>
                        <span class="shrink-0 font-display text-xl font-bold text-primary tabular-nums">{{ formatMoney(pkg.price) }}</span>
                    </div>
                    <p class="mt-3 text-xs font-semibold tracking-wide text-muted uppercase">{{ t('site.common.includes') }}</p>
                    <ul class="mt-2 space-y-1 text-[13px] text-muted">
                        <li v-for="test in pkg.tests" :key="test" class="flex items-center gap-1.5">
                            <AppIcon name="circle-check" size="sm" class="shrink-0 text-success" /> {{ test }}
                        </li>
                    </ul>
                </article>
            </div>
        </section>

        <section class="site-container site-section">
            <h2 class="site-h2">{{ t('public.all_tests_title') }}</h2>

            <div class="mt-5 space-y-3">
                <div class="relative max-w-md">
                    <AppIcon name="magnifying-glass" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted" />
                    <input v-model="search" type="search" class="control !h-10 !pl-9 !text-[14px]" :placeholder="t('site.common.search_placeholder')" :aria-label="t('site.common.search_placeholder')" />
                </div>
                <div v-if="departments.length > 1" class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-full border px-3.5 py-1.5 text-[13px] font-medium transition-colors" :class="activeDepartment === null ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-surface text-muted hover:bg-subtle'" @click="activeDepartment = null">{{ t('site.common.all') }}</button>
                    <button v-for="department in departments" :key="department.id" type="button" class="rounded-full border px-3.5 py-1.5 text-[13px] font-medium transition-colors" :class="activeDepartment === department.id ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-surface text-muted hover:bg-subtle'" @click="activeDepartment = activeDepartment === department.id ? null : department.id">{{ department.name }}</button>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                <div v-for="department in visibleDepartments" :key="department.id" class="overflow-hidden rounded-xl border border-border bg-surface">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-3.5 text-left transition-colors duration-100 hover:bg-subtle focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none"
                        :aria-expanded="isOpen(department)"
                        @click="openDepartment = openDepartment === department.id ? null : department.id"
                    >
                        <span class="site-icon h-10 w-10"><AppIcon :name="departmentIcons[department.category] ?? 'stethoscope'" /></span>
                        <span class="min-w-0 flex-1 text-[15px] font-semibold">{{ department.name }}</span>
                        <span class="text-xs text-muted">{{ t('public.tests_count', { count: department.tests.length }) }}</span>
                        <AppIcon :name="isOpen(department) ? 'chevron-up' : 'chevron-down'" class="text-muted" />
                    </button>
                    <div v-if="isOpen(department)" class="border-t border-border">
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

                <div v-if="visibleDepartments.length === 0" class="card text-center text-[14px] text-muted">
                    <AppIcon name="magnifying-glass" size="lg" class="text-muted/50" />
                    <p class="mt-2">{{ t('site.common.no_results') }}</p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
