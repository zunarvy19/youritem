<script setup lang="ts">
import { useRoute } from 'vue-router';
import AppIcon from '@/components/AppIcon.vue';
import type { IconName } from '@/components/icons';

export interface NavItem {
    name: string;
    label: string;
    to: string;
    icon: IconName;
}

defineProps<{
    items: NavItem[];
    compact?: boolean;
}>();

const route = useRoute();

function isActive(item: NavItem): boolean {
    return (
        route.path === item.to ||
        (item.to !== '/' && route.path.startsWith(`${item.to}/`))
    );
}
</script>

<template>
    <nav :aria-label="compact ? 'Primary' : 'Main navigation'">
        <ul :class="compact ? 'flex w-full justify-around' : 'space-y-1'">
            <li
                v-for="item in items"
                :key="item.name"
                class="w-full"
                :class="compact ? 'max-w-24' : ''"
            >
                <RouterLink
                    :to="item.to"
                    class="relative flex items-center rounded-xl text-sm font-medium transition-colors duration-150"
                    :class="[
                        compact
                            ? 'flex-col gap-0.5 px-2 py-2 text-[11px]'
                            : 'gap-3 px-3 py-2.5',
                        isActive(item)
                            ? compact
                                ? 'text-indigo-600'
                                : 'bg-indigo-50 text-indigo-700'
                            : compact
                              ? 'text-neutral-400'
                              : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900',
                    ]"
                    :aria-current="isActive(item) ? 'page' : undefined"
                >
                    <span
                        v-if="!compact && isActive(item)"
                        class="absolute top-1/2 left-0 h-5 w-1 -translate-y-1/2 rounded-r-full bg-indigo-600"
                        aria-hidden="true"
                    />
                    <AppIcon :name="item.icon" />
                    <span class="truncate">{{ item.label }}</span>
                </RouterLink>
            </li>
        </ul>
    </nav>
</template>
