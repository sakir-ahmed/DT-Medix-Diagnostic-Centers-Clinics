<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const locale = computed(() => page.props.app?.locale ?? 'en');

function switchTo(value) {
    if (value !== locale.value) {
        router.post(route('locale.update'), { locale: value }, { preserveScroll: true, preserveState: false });
    }
}
</script>

<template>
    <div class="inline-flex h-7 overflow-hidden rounded-md border border-border text-xs" role="group" :aria-label="$t('common.language')">
        <button type="button" class="px-2" :class="locale === 'en' ? 'bg-primary text-primary-foreground' : 'hover:bg-subtle'" :aria-pressed="locale === 'en'" @click="switchTo('en')">EN</button>
        <button type="button" class="px-2" :class="locale === 'bn' ? 'bg-primary text-primary-foreground' : 'hover:bg-subtle'" :aria-pressed="locale === 'bn'" lang="bn" @click="switchTo('bn')">বাং</button>
    </div>
</template>
