<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/EmptyState.vue';
import ErrorState from '@/components/ErrorState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PriorityBadge from '@/components/PriorityBadge.vue';
import PurposeBadge from '@/components/PurposeBadge.vue';
import { useToast } from '@/composables/useToast';
import { formatIdr } from '@/lib/format';
import { ApiError } from '@/services/apiClient';
import { fetchPurchases } from '@/services/purchaseService';
import type { Purchase } from '@/types';

const toast = useToast();

const purchases = ref<Purchase[]>([]);
const meta = reactive({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const loadError = ref(false);

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const response = await fetchPurchases();
        purchases.value = response.data;
        meta.current_page = response.meta.current_page;
        meta.last_page = response.meta.last_page;
        meta.total = response.meta.total;
    } catch (error) {
        loadError.value = true;

        if (!(error instanceof ApiError && error.isUnauthenticated)) {
            toast.error('Failed to load your purchase history.');
        }
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());

const formatDate = (value: string): string =>
    new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

const totalSpent = computed(() =>
    purchases.value.reduce((sum, purchase) => sum + purchase.actual_price, 0),
);

/** Estimated total of this page's items, to surface savings/overspend context. */
const estimatedTotal = computed(() =>
    purchases.value.reduce((sum, purchase) => sum + (purchase.wishlist_item.estimated_price ?? 0), 0),
);

const savings = computed(() => estimatedTotal.value - totalSpent.value);

function actualPriceTone(purchase: Purchase): string {
    const estimated = purchase.wishlist_item.estimated_price;

    if (estimated === null) {
return 'text-neutral-400';
}

    return purchase.actual_price < estimated
        ? 'font-medium text-emerald-600'
        : 'font-medium text-rose-500';
}
</script>

<template>
    <section>
        <PageHeader
            title="Purchase History"
            subtitle="Your completed purchases."
        />

        <div
            v-if="loading"
            class="space-y-3"
            role="status"
            aria-label="Loading content"
        >
            <div
                v-for="n in 4"
                :key="n"
                class="h-16 animate-pulse rounded-xl bg-neutral-200/80"
            />
            <span class="sr-only">Loading…</span>
        </div>

        <ErrorState
            v-else-if="loadError"
            message="We couldn't load your purchase history."
            @retry="load()"
        />

        <EmptyState
            v-else-if="!purchases.length"
            title="No purchases yet."
            description="Items you buy will appear here."
        >
            <template #actions>
                <RouterLink
                    to="/shopping"
                    class="btn-primary btn-sm"
                >Go to Shopping</RouterLink>
            </template>
        </EmptyState>

        <template v-else>
            <!-- Mobile cards -->
            <ul class="space-y-3 lg:hidden">
                <li
                    v-for="purchase in purchases"
                    :key="purchase.id"
                    class="card p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-neutral-900">
                                {{ purchase.wishlist_item.name ?? '—' }}
                            </p>
                            <p class="text-xs text-neutral-500">
                                {{ formatDate(purchase.purchased_at) }} ·
                                {{ purchase.wishlist_item.category.name ?? '—' }}
                            </p>
                        </div>
                        <span class="shrink-0 font-bold text-neutral-900">{{ formatIdr(purchase.actual_price) }}</span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <PurposeBadge
                            v-if="purchase.wishlist_item.purpose"
                            :value="purchase.wishlist_item.purpose"
                        />
                        <PriorityBadge
                            v-if="purchase.wishlist_item.priority"
                            :value="purchase.wishlist_item.priority"
                        />
                        <span
                            v-if="
                                purchase.wishlist_item.estimated_price !== null &&
                                    purchase.wishlist_item.estimated_price !== purchase.actual_price
                            "
                            class="text-xs"
                            :class="actualPriceTone(purchase)"
                        >
                            est. {{ formatIdr(purchase.wishlist_item.estimated_price) }}
                        </span>
                    </div>
                </li>
            </ul>

            <!-- Desktop table -->
            <div class="card hidden overflow-hidden lg:block">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-neutral-100 bg-neutral-50/70 text-xs tracking-wide text-neutral-400 uppercase">
                            <th class="px-5 py-3.5 font-semibold">Item</th>
                            <th class="px-4 py-3.5 font-semibold">Category</th>
                            <th class="px-4 py-3.5 font-semibold">Type</th>
                            <th class="px-4 py-3.5 font-semibold">Priority</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Estimated</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Actual Price</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Purchased</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr
                            v-for="purchase in purchases"
                            :key="purchase.id"
                            class="transition-colors duration-150 hover:bg-neutral-50"
                        >
                            <td class="px-5 py-3.5 font-semibold text-neutral-900">
                                {{ purchase.wishlist_item.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-sm text-neutral-500">
                                {{ purchase.wishlist_item.category.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3.5">
                                <PurposeBadge
                                    v-if="purchase.wishlist_item.purpose"
                                    :value="purchase.wishlist_item.purpose"
                                />
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <PriorityBadge
                                    v-if="purchase.wishlist_item.priority"
                                    :value="purchase.wishlist_item.priority"
                                />
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3.5 text-right text-sm text-neutral-400">
                                {{ purchase.wishlist_item.estimated_price !== null ? formatIdr(purchase.wishlist_item.estimated_price) : '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-sm font-bold text-neutral-900">
                                {{ formatIdr(purchase.actual_price) }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-sm text-neutral-500">
                                {{ formatDate(purchase.purchased_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="purchases.length"
                class="card mt-4 flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm"
            >
                <span class="text-neutral-500">
                    {{ meta.total }} purchase{{ meta.total === 1 ? '' : 's' }} · page
                    {{ meta.current_page }} of {{ meta.last_page }}
                </span>
                <span class="text-neutral-600">
                    Spent on this page:
                    <strong class="text-neutral-900">{{ formatIdr(totalSpent) }}</strong>
                    <span
                        v-if="savings !== 0 && estimatedTotal > 0"
                        class="ml-2 font-medium"
                        :class="savings > 0 ? 'text-emerald-700' : 'text-rose-600'"
                    >
                        ({{ savings > 0 ? 'saved' : 'over' }} {{ formatIdr(Math.abs(savings)) }} vs estimate)
                    </span>
                </span>
            </div>

            <div
                v-if="meta.last_page > 1"
                class="mt-4 flex justify-end gap-2"
            >
                <button
                    type="button"
                    class="btn-secondary btn-sm"
                    :disabled="meta.current_page <= 1"
                    @click="load()"
                >
                    Previous
                </button>
                <button
                    type="button"
                    class="btn-secondary btn-sm"
                    :disabled="meta.current_page >= meta.last_page"
                    @click="load()"
                >
                    Next
                </button>
            </div>
        </template>
    </section>
</template>
