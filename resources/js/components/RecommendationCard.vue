<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue';
import PriorityBadge from '@/components/PriorityBadge.vue';
import PurposeBadge from '@/components/PurposeBadge.vue';
import { formatIdr } from '@/lib/format';
import type { RecommendationItem } from '@/types';

defineProps<{
    item: RecommendationItem;
    /** 1-based ranking; renders the big rank number when provided. */
    rank?: number;
}>();

const emit = defineEmits<{ buy: [item: RecommendationItem] }>();
</script>

<template>
    <article
        class="card card-hover relative overflow-hidden p-5"
        data-testid="recommendation-card"
    >
        <span
            class="absolute inset-y-0 left-0 w-1 bg-emerald-400"
            aria-hidden="true"
        />

        <div class="flex items-start gap-4 pl-2">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <PriorityBadge :value="item.priority" />
                    <PurposeBadge :value="item.purpose" />
                </div>

                <h3 class="mt-2 truncate text-base font-bold text-neutral-900">
                    {{ item.name }}
                </h3>
                <p class="text-xs text-neutral-500">{{ item.category.name }}</p>

                <div class="mt-3">
                    <p class="text-lg font-bold text-neutral-900">
                        {{ formatIdr(item.estimated_price) }}
                    </p>
                </div>

                <div
                    v-if="item.reasons?.length"
                    class="mt-3 rounded-xl bg-emerald-50/70 px-3 py-2.5"
                >
                    <p
                        class="mb-1 text-[11px] font-semibold tracking-wide text-emerald-700 uppercase"
                    >
                        Why this item?
                    </p>
                    <ul class="space-y-0.5 text-sm text-emerald-800">
                        <li
                            v-for="reason in item.reasons"
                            :key="reason"
                            class="flex items-center gap-1.5"
                        >
                            <AppIcon
                                name="check"
                                class="h-3.5 w-3.5 shrink-0 text-emerald-500"
                            />
                            {{ reason }}
                        </li>
                        <li class="flex items-center gap-1.5">
                            <AppIcon
                                name="check"
                                class="h-3.5 w-3.5 shrink-0 text-emerald-500"
                            />
                            Within your current budget
                        </li>
                    </ul>
                </div>
            </div>

            <div class="flex shrink-0 flex-col items-end justify-between gap-3">
                <span
                    v-if="rank"
                    class="text-2xl leading-none font-extrabold text-neutral-200 select-none sm:text-3xl"
                    aria-hidden="true"
                >
                    {{ String(rank).padStart(2, '0') }}
                </span>
                <button
                    type="button"
                    class="btn-success btn-sm"
                    @click="emit('buy', item)"
                >
                    Buy
                </button>
            </div>
        </div>
    </article>
</template>
