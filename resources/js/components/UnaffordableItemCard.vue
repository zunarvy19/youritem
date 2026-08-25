<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import PriorityBadge from '@/components/PriorityBadge.vue';
import PurposeBadge from '@/components/PurposeBadge.vue';
import { formatIdr } from '@/lib/format';
import type { UnaffordableItem } from '@/types';

const props = defineProps<{
    item: UnaffordableItem;
    availableBudget: number;
}>();

/** How close the current budget gets to the price (presentation only). */
const progressPct = computed(() => {
    if (props.item.estimated_price <= 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.round((props.availableBudget / props.item.estimated_price) * 100),
    );
});
</script>

<template>
    <article class="card p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="truncate font-semibold text-neutral-900">
                    {{ item.name }}
                </h3>
                <p class="text-xs text-neutral-500">{{ item.category.name }}</p>
            </div>
            <span
                class="rounded-xl bg-rose-50 p-2 text-rose-400"
                aria-hidden="true"
            >
                <AppIcon name="lock" class="h-4 w-4" />
            </span>
        </div>

        <div class="mt-2 flex flex-wrap items-center gap-2">
            <PriorityBadge :value="item.priority" />
            <PurposeBadge :value="item.purpose" />
        </div>

        <p
            class="mt-3 text-lg font-bold text-neutral-400 line-through decoration-rose-300/60"
        >
            {{ formatIdr(item.estimated_price) }}
        </p>

        <div class="mt-3">
            <div
                class="flex items-center justify-between text-[11px] font-medium text-neutral-400"
            >
                <span>Budget {{ formatIdr(availableBudget) }}</span>
                <span>{{ progressPct }}%</span>
            </div>
            <div
                class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-neutral-100"
            >
                <div
                    class="h-full rounded-full bg-gradient-to-r from-amber-300 to-rose-400 transition-all duration-500"
                    :style="{ width: `${progressPct}%` }"
                />
            </div>
        </div>

        <p class="mt-3 text-sm font-semibold text-rose-600">
            Need {{ formatIdr(item.amount_needed) }} more
        </p>
    </article>
</template>
