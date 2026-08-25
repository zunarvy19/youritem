<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AppIcon from '@/components/AppIcon.vue';
import BudgetHero from '@/components/BudgetHero.vue';
import BudgetOptimizationCard from '@/components/BudgetOptimizationCard.vue';
import ErrorState from '@/components/ErrorState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PurchaseDialog from '@/components/PurchaseDialog.vue';
import RecommendationCard from '@/components/RecommendationCard.vue';
import StatCard from '@/components/StatCard.vue';
import UnaffordableItemCard from '@/components/UnaffordableItemCard.vue';
import { useAuth } from '@/composables/useAuth';
import { formatIdr } from '@/lib/format';
import { ApiError } from '@/services/apiClient';
import { fetchRecommendations } from '@/services/budgetService';
import { fetchPurchases } from '@/services/purchaseService';
import { fetchWishlistItems } from '@/services/wishlistService';
import type {
    Purchase,
    RecommendationItem,
    RecommendationResult,
    UnaffordableItem,
    WishlistItem,
} from '@/types';

const auth = useAuth();

const loading = ref(true);
const loadError = ref(false);

const budget = ref(0);
const wishlistCount = ref(0);
const highPriorityCount = ref(0);
const purchaseCount = ref(0);
const recentPurchases = ref<Purchase[]>([]);

const priorityFirst = ref<RecommendationItem[]>([]);
const pfTotal = ref(0);
const pfRemaining = ref(0);
const optimizationItems = ref<RecommendationItem[]>([]);
const optimizationMeta = reactive({ total: 0, remaining: 0, utilization: 0 });
const unaffordableItems = ref<UnaffordableItem[]>([]);
const wishlistByCategory = ref<
    { name: string; count: number; value: number }[]
>([]);

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const [recommendations, purchases, wishlist] = await Promise.all([
            fetchRecommendations(),
            fetchPurchases(),
            fetchWishlistItems({ status: 'ACTIVE', per_page: 100 }),
        ]);

        applyRecommendations(recommendations);
        purchaseCount.value = purchases.meta.total;
        recentPurchases.value = purchases.data.slice(0, 3);
        applyWishlist(wishlist.data);
    } catch (error) {
        if (!(error instanceof ApiError && error.isUnauthenticated)) {
            loadError.value = true;
        }
    } finally {
        loading.value = false;
    }
}

function applyRecommendations(result: RecommendationResult): void {
    budget.value = result.available_budget;
    priorityFirst.value = result.priority_first.items.slice(0, 3);
    pfTotal.value = result.priority_first.total;
    pfRemaining.value = result.priority_first.remaining_budget;
    optimizationItems.value = result.budget_optimization.items;
    optimizationMeta.total = result.budget_optimization.total;
    optimizationMeta.remaining = result.budget_optimization.remaining_budget;
    optimizationMeta.utilization = result.budget_optimization.utilization;
    unaffordableItems.value = result.unaffordable.slice(0, 3);
}

function applyWishlist(items: WishlistItem[]): void {
    wishlistCount.value = items.length;
    highPriorityCount.value = items.filter(
        (item) => item.priority === 'HIGH',
    ).length;

    const groups = new Map<string, { count: number; value: number }>();

    for (const item of items) {
        const group = groups.get(item.category.name) ?? { count: 0, value: 0 };
        group.count += 1;
        group.value += item.estimated_price;
        groups.set(item.category.name, group);
    }

    wishlistByCategory.value = [...groups.entries()]
        .map(([name, data]) => ({ name, ...data }))
        .sort((a, b) => b.value - a.value)
        .slice(0, 6);
}

onMounted(load);

// --- Purchase flow (shared dialog) ---
const purchaseTarget = ref<RecommendationItem | null>(null);
const purchaseOpen = ref(false);

function buy(item: RecommendationItem): void {
    purchaseTarget.value = item;
    purchaseOpen.value = true;
}

async function onPurchased(): Promise<void> {
    purchaseOpen.value = false;
    await load();
}

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
});

const formatDate = (value: string): string =>
    new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });

const maxCategoryValue = computed(() =>
    Math.max(...wishlistByCategory.value.map((group) => group.value), 1),
);
</script>

<template>
    <section>
        <PageHeader
            :title="`${greeting}, ${auth.user.value?.name?.split(' ')[0] ?? ''}`"
            subtitle="Here's your purchase overview."
        >
            <template #actions>
                <RouterLink to="/shopping" class="btn-primary btn-sm">
                    View Shopping
                </RouterLink>
            </template>
        </PageHeader>

        <div v-if="loading" class="space-y-4">
            <div class="h-44 animate-pulse rounded-3xl bg-neutral-200/80" />
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="h-24 animate-pulse rounded-2xl bg-neutral-200/70"
                />
            </div>
        </div>

        <ErrorState
            v-else-if="loadError"
            message="We couldn't load your dashboard."
            @retry="load"
        />

        <template v-else>
            <!-- Current budget hero -->
            <BudgetHero :amount="budget" />

            <!-- Summary cards -->
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard
                    label="Wishlist Items"
                    :value="String(wishlistCount)"
                    icon="heart"
                    tone="indigo"
                />
                <StatCard
                    label="High Priority"
                    :value="String(highPriorityCount)"
                    icon="sparkles"
                    tone="rose"
                    hint="Active items marked important"
                />
                <StatCard
                    label="Completed Purchases"
                    :value="String(purchaseCount)"
                    icon="check"
                    tone="emerald"
                />
            </div>

            <!-- Recommendation overview -->
            <section class="mt-10" aria-labelledby="recommendations-heading">
                <h2
                    id="recommendations-heading"
                    class="text-lg font-bold text-neutral-900"
                >
                    Your recommendations
                </h2>
                <p class="mt-0.5 text-sm text-neutral-500">
                    Based on your current budget of {{ formatIdr(budget) }}.
                </p>

                <!-- Priority First — primary -->
                <div
                    class="mt-5 rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-5"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2.5">
                            <span
                                class="badge border-emerald-600 bg-emerald-600 text-white"
                                >Recommended</span
                            >
                            <h3 class="font-bold text-neutral-900">
                                Priority First
                            </h3>
                        </div>
                        <p class="text-xs text-neutral-500">
                            What should you buy first?
                        </p>
                    </div>

                    <p
                        v-if="!priorityFirst.length"
                        class="mt-4 rounded-xl bg-white px-4 py-4 text-sm text-neutral-500"
                    >
                        Nothing affordable right now — add items or increase
                        your budget.
                        <RouterLink
                            to="/shopping"
                            class="ml-1 font-medium text-indigo-600 underline underline-offset-2"
                            >Go to Shopping</RouterLink
                        >
                    </p>

                    <div
                        v-else
                        class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3"
                    >
                        <RecommendationCard
                            v-for="(item, index) in priorityFirst"
                            :key="item.id"
                            :item="item"
                            :rank="index + 1"
                            @buy="buy"
                        />
                    </div>

                    <p
                        v-if="priorityFirst.length"
                        class="mt-3 text-sm text-neutral-600"
                    >
                        Recommended total
                        <strong class="text-neutral-900">{{
                            formatIdr(pfTotal)
                        }}</strong>
                        · Remaining budget
                        <strong class="text-neutral-900">{{
                            formatIdr(pfRemaining)
                        }}</strong>
                    </p>
                </div>

                <!-- Budget Optimization — alternative -->
                <BudgetOptimizationCard
                    class="mt-4"
                    :items="optimizationItems"
                    :total="optimizationMeta.total"
                    :remaining="optimizationMeta.remaining"
                    :utilization="optimizationMeta.utilization"
                    :available-budget="budget"
                    @buy="buy"
                />

                <!-- Can't afford yet -->
                <div
                    v-if="unaffordableItems.length"
                    class="mt-4 rounded-2xl border border-rose-200/80 bg-rose-50/40 p-5"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2.5">
                            <AppIcon name="lock" class="text-rose-400" />
                            <h3 class="font-bold text-neutral-900">
                                Can't Afford Yet
                            </h3>
                        </div>
                        <RouterLink
                            to="/wishlist"
                            class="text-xs font-medium text-indigo-600 underline underline-offset-2"
                            >Review wishlist</RouterLink
                        >
                    </div>

                    <div
                        class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <UnaffordableItemCard
                            v-for="item in unaffordableItems"
                            :key="item.id"
                            :item="item"
                            :available-budget="budget"
                        />
                    </div>
                </div>
            </section>

            <!-- Recent purchases + wishlist by category -->
            <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section aria-labelledby="recent-purchases">
                    <div class="mb-3 flex items-center justify-between">
                        <h2
                            id="recent-purchases"
                            class="font-bold text-neutral-900"
                        >
                            Recent Purchases
                        </h2>
                        <RouterLink
                            to="/purchases"
                            class="text-xs font-medium text-indigo-600 underline underline-offset-2"
                            >View all</RouterLink
                        >
                    </div>

                    <div class="card divide-y divide-neutral-100 p-2">
                        <p
                            v-if="!recentPurchases.length"
                            class="px-4 py-6 text-center text-sm text-neutral-500"
                        >
                            No purchases yet.
                        </p>
                        <div
                            v-for="purchase in recentPurchases"
                            :key="purchase.id"
                            class="flex items-center justify-between gap-3 px-3 py-3"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold text-neutral-900"
                                >
                                    {{ purchase.wishlist_item.name ?? '—' }}
                                </p>
                                <p class="text-xs text-neutral-400">
                                    {{ formatDate(purchase.purchased_at) }} ·
                                    {{
                                        purchase.wishlist_item.category.name ??
                                        ''
                                    }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 text-sm font-semibold text-emerald-700"
                            >
                                −{{ formatIdr(purchase.actual_price) }}
                            </span>
                        </div>
                    </div>
                </section>

                <section aria-labelledby="wishlist-by-category">
                    <div class="mb-3 flex items-center justify-between">
                        <h2
                            id="wishlist-by-category"
                            class="font-bold text-neutral-900"
                        >
                            Wishlist by Category
                        </h2>
                        <RouterLink
                            to="/wishlist"
                            class="text-xs font-medium text-indigo-600 underline underline-offset-2"
                            >Manage</RouterLink
                        >
                    </div>

                    <div class="card space-y-4 p-5">
                        <p
                            v-if="!wishlistByCategory.length"
                            class="py-4 text-center text-sm text-neutral-500"
                        >
                            Your wishlist is empty.
                        </p>
                        <div
                            v-for="group in wishlistByCategory"
                            :key="group.name"
                        >
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="font-medium text-neutral-700">{{
                                    group.name
                                }}</span>
                                <span class="text-neutral-400"
                                    >{{ group.count }} item{{
                                        group.count > 1 ? 's' : ''
                                    }}
                                    · {{ formatIdr(group.value) }}</span
                                >
                            </div>
                            <div
                                class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-neutral-100"
                            >
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-indigo-400 to-violet-400"
                                    :style="{
                                        width: `${Math.round((group.value / maxCategoryValue) * 100)}%`,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </template>

        <PurchaseDialog
            :open="purchaseOpen"
            :item="purchaseTarget"
            :available-budget="budget"
            @close="purchaseOpen = false"
            @purchased="onPurchased"
        />
    </section>
</template>
