<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuPortal, DropdownMenuRoot, DropdownMenuSeparator, DropdownMenuTrigger } from 'reka-ui';
import { computed } from 'vue';
import AppIcon from '@/components/ui-kit/AppIcon.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const roles = computed(() => page.props.auth?.roles ?? []);
</script>

<template>
    <DropdownMenuRoot v-if="user">
        <DropdownMenuTrigger class="flex h-7 items-center gap-2 rounded-md px-1.5 hover:bg-subtle" :aria-label="$t('nav.user_menu')">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-[11px] font-semibold text-primary-foreground">{{ user.name.slice(0, 1) }}</span>
            <span class="hidden max-w-32 truncate text-[13px] lg:inline">{{ user.name }}</span>
            <AppIcon name="chevron-down" size="xs" class="text-muted" />
        </DropdownMenuTrigger>
        <DropdownMenuPortal>
            <DropdownMenuContent align="end" :side-offset="6" class="z-50 min-w-48 rounded-md border border-border bg-surface p-1 text-[13px] shadow-md">
                <DropdownMenuLabel class="px-2 py-1.5">
                    <div class="font-medium">{{ user.name }}</div>
                    <div class="text-xs text-muted">{{ roles.map((r) => $t(`roles.${r}`)).join(', ') }}</div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator class="my-1 h-px bg-border" />
                <DropdownMenuItem v-if="route().has('admin.profile.edit')" as-child>
                    <Link :href="route('admin.profile.edit')" class="flex h-7 items-center gap-2 rounded px-2 outline-none data-[highlighted]:bg-subtle">
                        <AppIcon name="user" size="sm" /> {{ $t('nav.profile') }}
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem class="flex h-7 cursor-pointer items-center gap-2 rounded px-2 outline-none data-[highlighted]:bg-subtle" @select="router.post(route('logout'))">
                    <AppIcon name="right-from-bracket" size="sm" /> {{ $t('auth.sign_out') }}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenuPortal>
    </DropdownMenuRoot>
</template>
