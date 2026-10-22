<script setup>
defineProps({
    eyebrow: { type: String, default: null },
    title: { type: String, required: true },
    lead: { type: String, default: null },
    center: { type: Boolean, default: false },
});
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-3" :class="center ? 'justify-center text-center' : ''">
        <div :class="center ? 'mx-auto' : ''">
            <p v-if="eyebrow" class="site-eyebrow">{{ eyebrow }}</p>
            <h2 class="site-h2">{{ title }}</h2>
            <p v-if="lead" class="site-lead" :class="center ? 'mx-auto' : ''">{{ lead }}</p>
        </div>
        <slot />
    </div>
</template>
