<script setup>
import { ref } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    limit: { type: Number, default: 12 },
});

const { t } = useI18n();
const open = ref(1);
const numbers = Array.from({ length: props.limit }, (_, index) => index + 1);
</script>

<template>
    <div class="divide-y divide-border overflow-hidden rounded-xl border border-border bg-surface">
        <div v-for="n in numbers" :key="n">
            <h3 class="text-[15px] font-semibold">
                <button
                    type="button"
                    class="flex w-full items-center gap-3 px-5 py-4 text-left transition-colors hover:bg-subtle focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none"
                    :aria-expanded="open === n"
                    :aria-controls="`faq-${n}`"
                    @click="open = open === n ? null : n"
                >
                    <span class="min-w-0 flex-1">{{ t(`site.faq.items.q${n}.q`) }}</span>
                    <AppIcon :name="open === n ? 'chevron-up' : 'chevron-down'" class="shrink-0 text-muted" />
                </button>
            </h3>
            <p v-show="open === n" :id="`faq-${n}`" class="px-5 pb-4 text-[14px] leading-relaxed text-muted">{{ t(`site.faq.items.q${n}.a`) }}</p>
        </div>
    </div>
</template>
