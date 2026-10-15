<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';

const page = usePage();
const branches = computed(() => page.props.branches ?? []);
const current = computed({
    get: () => page.props.current_branch_id,
    set: (value) => router.post(route('admin.branch.switch'), { branch_id: value }, { preserveScroll: true }),
});
</script>

<template>
    <label v-if="branches.length" class="flex items-center gap-1.5">
        <AppIcon name="building" size="sm" class="text-muted" />
        <span class="sr-only">{{ $t('branches.current') }}</span>
        <select v-model="current" class="control !h-7 !w-auto max-w-44 pr-6" :disabled="branches.length < 2">
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.code }} - {{ branch.name }}</option>
        </select>
    </label>
</template>
