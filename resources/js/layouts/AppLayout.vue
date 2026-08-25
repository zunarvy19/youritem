<script setup lang="ts">
import { computed } from 'vue';
import { RouterView, useRouter } from 'vue-router';
import AppNavList from '@/components/AppNavList.vue';
import type { NavItem } from '@/components/AppNavList.vue';
import BrandLogo from '@/components/BrandLogo.vue';
import ToastHost from '@/components/ToastHost.vue';
import { useAuth } from '@/composables/useAuth';
import { useI18n } from '@/composables/useI18n';

const auth = useAuth();
const router = useRouter();
const { t } = useI18n();

const mainNav = computed<NavItem[]>(() => [
    { name: 'dashboard', label: t('nav.dashboard'), to: '/', icon: 'home' },
    {
        name: 'wishlist',
        label: t('nav.wishlist'),
        to: '/wishlist',
        icon: 'heart',
    },
    {
        name: 'categories',
        label: t('nav.categories'),
        to: '/categories',
        icon: 'tag',
    },
    { name: 'budget', label: t('nav.budget'), to: '/budget', icon: 'wallet' },
    {
        name: 'shopping',
        label: t('nav.shopping'),
        to: '/shopping',
        icon: 'bag',
    },
    {
        name: 'purchases',
        label: t('nav.purchases'),
        to: '/purchases',
        icon: 'clock',
    },
]);

const profileNav = computed<NavItem[]>(() => [
    {
        name: 'settings',
        label: t('nav.settings'),
        to: '/settings',
        icon: 'user',
    },
]);

const mobileNav = computed<NavItem[]>(() => [
    { name: 'dashboard', label: t('nav.home'), to: '/', icon: 'home' },
    {
        name: 'wishlist',
        label: t('nav.wishlist'),
        to: '/wishlist',
        icon: 'heart',
    },
    {
        name: 'shopping',
        label: t('nav.shopping'),
        to: '/shopping',
        icon: 'bag',
    },
    {
        name: 'purchases',
        label: t('nav.history'),
        to: '/purchases',
        icon: 'clock',
    },
    {
        name: 'settings',
        label: t('nav.profile'),
        to: '/settings',
        icon: 'user',
    },
]);

async function handleLogout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <div class="min-h-screen bg-neutral-50">
        <!-- Desktop sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-neutral-200/80 bg-white px-4 py-6 lg:flex"
        >
            <RouterLink to="/" class="mb-8 flex items-center gap-2.5 px-2">
                <BrandLogo class="h-10 w-12" />
                <span
                    class="text-lg font-extrabold tracking-tight text-neutral-900"
                    >WiseBuy</span
                >
            </RouterLink>

            <div class="flex-1 overflow-y-auto">
                <AppNavList :items="mainNav" />

                <div class="my-5 border-t border-neutral-100" />
                <p
                    class="mb-2 px-3 text-[11px] font-semibold tracking-wider text-neutral-400 uppercase"
                >
                    {{ t('nav.account') }}
                </p>
                <AppNavList :items="profileNav" />
            </div>

            <div class="border-t border-neutral-100 pt-4">
                <!-- <div class="mb-4 flex justify-end px-2">
                    <LanguageSwitcher />
                </div> -->
                <div class="flex items-center gap-3 px-2">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600"
                    >
                        {{ auth.user.value?.name?.charAt(0)?.toUpperCase() }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-semibold text-neutral-900"
                        >
                            {{ auth.user.value?.name }}
                        </p>
                        <p class="truncate text-xs text-neutral-400">
                            {{ auth.user.value?.email }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="btn-ghost mt-3 w-full justify-start px-2 text-sm"
                    @click="handleLogout"
                >
                    {{ t('auth.logout') }}
                </button>
            </div>
        </aside>

        <!-- Mobile header -->
        <header
            class="sticky top-0 z-30 flex items-center justify-between border-b border-neutral-200/80 bg-white/90 px-4 py-3 backdrop-blur lg:hidden"
        >
            <RouterLink to="/" class="flex items-center gap-2">
                <BrandLogo class="h-8 w-10" />
                <span class="font-extrabold tracking-tight text-neutral-900"
                    >WiseBuy</span
                >
            </RouterLink>
            <div class="flex items-center gap-2">
                <!-- <LanguageSwitcher /> -->
                <button
                    type="button"
                    class="rounded-lg p-2 text-neutral-500 hover:bg-neutral-100"
                    :aria-label="t('auth.logout')"
                    @click="handleLogout"
                >
                    {{ t('auth.logout') }}
                </button>
            </div>
        </header>

        <!-- Main content -->
        <main class="px-4 py-6 pb-24 sm:px-6 lg:ml-64 lg:pb-10">
            <div class="mx-auto max-w-5xl">
                <RouterView />
            </div>
        </main>

        <!-- Mobile bottom navigation -->
        <nav
            class="fixed inset-x-0 bottom-0 z-30 border-t border-neutral-200/80 bg-white/95 pt-1 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden"
        >
            <AppNavList :items="mobileNav" compact />
        </nav>

        <ToastHost />
    </div>
</template>
