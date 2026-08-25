<script setup lang="ts">
import { computed, ref } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import { formatIdr } from '@/lib/format';
import type { RecommendationItem } from '@/types';

const props = defineProps<{
    items: RecommendationItem[];
    total: number;
    remaining: number;
    utilization: number;
    availableBudget: number;
}>();

const emit = defineEmits<{ buy: [item: RecommendationItem] }>();

const expanded = ref(false);

const utilizationPct = computed(() => Math.round(props.utilization * 100));
</script>

<template>
    <article
        class="overflow-hidden rounded-2xl border border-violet-200 bg-violet-50/60 shadow-sm"
    >
        <div class="p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span
                        class="rounded-xl bg-violet-100 p-2 text-violet-600"
                        aria-hidden="true"
                    >
                        <AppIcon name="sparkles" />
                    </span>
                    <div>
                        <p
                            class="text-[11px] font-bold tracking-widest text-violet-500 uppercase"
                        >
                            Best Value
                        </p>
                        <h2 class="text-lg font-bold text-neutral-900">
                            Budget Optimization
                        </h2>
                    </div>
                </div>

                <span
                    v-if="items.length"
                    class="badge border-violet-200 bg-white text-violet-700"
                >
                    {{ items.length }} item{{ items.length > 1 ? 's' : '' }}
                    together
                </span>
            </div>

            <p class="mt-2 text-sm text-neutral-600">
                An alternative way to use your budget — a combination with
                higher overall value while staying within your limit.
            </p>

            <template v-if="items.length">
                <!-- Collapsed summary -->
                <dl class="mt-4 grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-xl bg-white px-3 py-3">
                        <dt
                            class="text-[11px] font-semibold tracking-wide text-neutral-400 uppercase"
                        >
                            Total
                        </dt>
                        <dd
                            class="mt-1 truncate text-sm font-bold text-neutral-900"
                        >
                            {{ formatIdr(total) }}
                        </dd>
                    </div>
                    <div class="rounded-xl bg-white px-3 py-3">
                        <dt
                            class="text-[11px] font-semibold tracking-wide text-neutral-400 uppercase"
                        >
                            Used
                        </dt>
                        <dd
                            class="mt-1 truncate text-sm font-bold text-violet-700"
                        >
                            {{ utilizationPct }}%
                        </dd>
                    </div>
                    <div class="rounded-xl bg-white px-3 py-3">
                        <dt
                            class="text-[11px] font-semibold tracking-wide text-neutral-400 uppercase"
                        >
                            Remaining
                        </dt>
                        <dd
                            class="mt-1 truncate text-sm font-bold text-neutral-900"
                        >
                            {{ formatIdr(remaining) }}
                        </dd>
                    </div>
                </dl>

                <button
                    type="button"
                    class="btn mt-4 w-full border border-violet-300 bg-white text-violet-700 hover:bg-violet-100 sm:w-auto"
                    :aria-expanded="expanded"
                    @click="expanded = !expanded"
                >
                    {{ expanded ? 'Hide Selection' : 'Review Selection' }}
                </button>

                <!-- Expanded detail -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="-translate-y-1 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                >
                    <ul
                        v-if="expanded"
                        class="mt-4 divide-y divide-violet-100 overflow-hidden rounded-xl border border-violet-100 bg-white"
                    >
                        <li
                            v-for="item in items"
                            :key="item.id"
                            class="flex items-center justify-between gap-3 px-4 py-3"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold text-neutral-900"
                                >
                                    {{ item.name }}
                                </p>
                                <p class="text-xs text-neutral-500">
                                    {{ item.category.name }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span
                                    class="text-sm font-semibold text-neutral-900"
                                    >{{ formatIdr(item.estimated_price) }}</span
                                >
                                <button
                                    type="button"
                                    class="btn-success btn-sm"
                                    @click="emit('buy', item)"
                                >
                                    Buy
                                </button>
                            </div>
                        </li>
                    </ul>
                </Transition>

                <p class="mt-3 text-xs text-violet-700/80">
                    Budget {{ formatIdr(availableBudget) }} · buying these
                    together uses the most of what matters.
                </p>
            </template>

            <p
                v-else
                class="mt-4 rounded-xl bg-white px-4 py-3 text-sm text-neutral-500"
            >
                No better combination is available right now. Priority First
                already covers your best options.
            </p>
        </div>
    </article>
</template>
