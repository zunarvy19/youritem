<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AppIcon from '@/components/AppIcon.vue';
import type { IconName } from '@/components/icons';
import { useI18n } from '@/composables/useI18n';

type MobileNavItem = {
    name: string;
    label: string;
    to: string;
    icon: IconName;
};

const route = useRoute();
const { t } = useI18n();
const moreOpen = ref(false);

const primaryItems = computed<MobileNavItem[]>(() => [
    { name: 'dashboard', label: t('nav.home'), to: '/', icon: 'home' },
    {
        name: 'wishlist',
        label: t('nav.wishlist'),
        to: '/wishlist',
        icon: 'heart',
    },
    { name: 'budget', label: t('nav.budget'), to: '/budget', icon: 'wallet' },
    {
        name: 'shopping',
        label: t('nav.shopping'),
        to: '/shopping',
        icon: 'bag',
    },
]);

const secondaryItems = computed<MobileNavItem[]>(() => [
    {
        name: 'purchases',
        label: t('nav.purchases'),
        to: '/purchases',
        icon: 'clock',
    },
    {
        name: 'categories',
        label: t('nav.categories'),
        to: '/categories',
        icon: 'tag',
    },
    {
        name: 'settings',
        label: t('nav.settings'),
        to: '/settings',
        icon: 'user',
    },
]);

const moreIsActive = computed(() => secondaryItems.value.some(isActive));

function isActive(item: MobileNavItem): boolean {
    return (
        route.path === item.to ||
        (item.to !== '/' && route.path.startsWith(`${item.to}/`))
    );
}

function closeMore(): void {
    moreOpen.value = false;
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        closeMore();
    }
}

watch(
    () => route.fullPath,
    () => closeMore(),
);

watch(moreOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <button
                v-if="moreOpen"
                type="button"
                class="fixed inset-0 z-40 bg-neutral-950/40 lg:hidden"
                :aria-label="t('common.close')"
                @click="closeMore"
            />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-250 ease-out"
            enter-from-class="translate-y-full"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-to-class="translate-y-full"
        >
            <section
                v-if="moreOpen"
                class="fixed inset-x-0 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-50 rounded-t-3xl bg-white px-4 pt-3 pb-5 shadow-2xl lg:hidden"
                role="dialog"
                aria-modal="true"
                :aria-label="t('nav.more_menu')"
                @keydown="onKeydown"
            >
                <div
                    class="mx-auto mb-4 h-1 w-10 rounded-full bg-neutral-200"
                />
                <div class="mb-3 flex items-center justify-between gap-4 px-1">
                    <h2 class="text-base font-bold text-neutral-900">
                        {{ t('nav.more_menu') }}
                    </h2>
                    <button
                        type="button"
                        class="rounded-full bg-neutral-100 px-3 py-1.5 text-xs font-semibold text-neutral-600"
                        @click="closeMore"
                    >
                        {{ t('common.close') }}
                    </button>
                </div>
                <div class="grid gap-2">
                    <RouterLink
                        v-for="item in secondaryItems"
                        :key="item.name"
                        :to="item.to"
                        class="flex min-h-14 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition-colors"
                        :class="
                            isActive(item)
                                ? 'bg-indigo-50 text-indigo-700'
                                : 'text-neutral-700 hover:bg-neutral-50'
                        "
                        :aria-current="isActive(item) ? 'page' : undefined"
                        @click="closeMore"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl"
                            :class="
                                isActive(item)
                                    ? 'bg-indigo-100'
                                    : 'bg-neutral-100'
                            "
                        >
                            <AppIcon :name="item.icon" />
                        </span>
                        <span>{{ item.label }}</span>
                    </RouterLink>
                </div>
            </section>
        </Transition>
    </Teleport>

    <nav
        class="fixed inset-x-0 bottom-0 z-50 border-t border-neutral-200/80 bg-white/95 px-2 pt-1 pb-[env(safe-area-inset-bottom)] shadow-[0_-8px_24px_rgba(15,23,42,0.06)] backdrop-blur lg:hidden"
        :aria-label="t('nav.mobile_navigation')"
    >
        <ul class="grid grid-cols-5">
            <li v-for="item in primaryItems" :key="item.name" class="min-w-0">
                <RouterLink
                    :to="item.to"
                    class="relative flex min-h-16 min-w-0 flex-col items-center justify-center gap-1 rounded-2xl px-1 text-[10px] font-semibold transition-colors"
                    :class="
                        isActive(item) ? 'text-indigo-600' : 'text-neutral-400'
                    "
                    :aria-current="isActive(item) ? 'page' : undefined"
                >
                    <span
                        class="flex h-8 min-w-10 items-center justify-center rounded-full px-2 transition-colors"
                        :class="isActive(item) ? 'bg-indigo-100' : ''"
                    >
                        <AppIcon :name="item.icon" />
                    </span>
                    <span class="w-full truncate text-center">{{
                        item.label
                    }}</span>
                </RouterLink>
            </li>
            <li class="min-w-0">
                <button
                    type="button"
                    class="relative flex min-h-16 w-full min-w-0 flex-col items-center justify-center gap-1 rounded-2xl px-1 text-[10px] font-semibold transition-colors"
                    :class="
                        moreOpen || moreIsActive
                            ? 'text-indigo-600'
                            : 'text-neutral-400'
                    "
                    :aria-expanded="moreOpen"
                    aria-haspopup="dialog"
                    @click="moreOpen = !moreOpen"
                >
                    <span
                        class="flex h-8 min-w-10 items-center justify-center rounded-full px-2 transition-colors"
                        :class="moreOpen || moreIsActive ? 'bg-indigo-100' : ''"
                    >
                        <AppIcon name="dots" />
                    </span>
                    <span class="w-full truncate text-center">{{
                        t('nav.more')
                    }}</span>
                </button>
            </li>
        </ul>
    </nav>
</template>
