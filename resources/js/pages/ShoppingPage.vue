<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AppModal from '@/components/AppModal.vue';
import BudgetHero from '@/components/BudgetHero.vue';
import BudgetOptimizationCard from '@/components/BudgetOptimizationCard.vue';
import ErrorState from '@/components/ErrorState.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import PageHeader from '@/components/PageHeader.vue';
import PurchaseDialog from '@/components/PurchaseDialog.vue';
import RecommendationCard from '@/components/RecommendationCard.vue';
import UnaffordableItemCard from '@/components/UnaffordableItemCard.vue';
import { useToast } from '@/composables/useToast';
import { formatIdr } from '@/lib/format';
import { ApiError } from '@/services/apiClient';
import { fetchRecommendations, updateBudget } from '@/services/budgetService';
import type {
    RecommendationItem,
    RecommendationResult,
    UnaffordableItem,
} from '@/types';

const toast = useToast();

const loading = ref(true);
const loadError = ref(false);

const budget = ref(0);
const priorityFirst = reactive({ items: [] as RecommendationItem[], total: 0, remaining: 0 });
const optimization = reactive({
    items: [] as RecommendationItem[],
    total: 0,
    remaining: 0,
    score: 0,
    utilization: 0,
});
const unaffordableItems = ref<UnaffordableItem[]>([]);

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const result: RecommendationResult = await fetchRecommendations();
        budget.value = result.available_budget;
        priorityFirst.items = result.priority_first.items;
        priorityFirst.total = result.priority_first.total;
        priorityFirst.remaining = result.priority_first.remaining_budget;
        optimization.items = result.budget_optimization.items;
        optimization.total = result.budget_optimization.total;
        optimization.remaining = result.budget_optimization.remaining_budget;
        optimization.score = result.budget_optimization.score;
        optimization.utilization = result.budget_optimization.utilization;
        unaffordableItems.value = result.unaffordable;
    } catch (error) {
        loadError.value = true;

        if (!(error instanceof ApiError && error.isUnauthenticated)) {
            toast.error("We couldn't load your shopping recommendations.");
        }
    } finally {
        loading.value = false;
    }
}

onMounted(load);

// --- Budget editing ---
const budgetOpen = ref(false);
const newBudget = ref<number | null>(null);
const budgetProcessing = ref(false);
const budgetError = ref('');

function openBudgetModal(): void {
    newBudget.value = budget.value;
    budgetError.value = '';
    budgetOpen.value = true;
}

async function saveBudget(): Promise<void> {
    if (newBudget.value === null || !Number.isInteger(newBudget.value) || newBudget.value < 0) {
        budgetError.value = 'Budget must be a whole number of zero or more.';

        return;
    }

    budgetProcessing.value = true;
    budgetError.value = '';

    try {
        await updateBudget(newBudget.value);
        budgetOpen.value = false;
        toast.success('Budget updated. Recommendations refreshed.');
        await load();
    } catch (error) {
        budgetError.value =
            error instanceof ApiError ? error.message : 'Unable to update the budget.';
    } finally {
        budgetProcessing.value = false;
    }
}

// --- Purchasing ---
const purchaseTarget = ref<RecommendationItem | null>(null);
const purchaseOpen = ref(false);

function buy(item: RecommendationItem): void {
    purchaseTarget.value = item;
    purchaseOpen.value = true;
}

async function onPurchased(): Promise<void> {
    purchaseOpen.value = false;
    toast.success('Purchase completed. Recommendations refreshed.');
    await load();
}
</script>

<template>
    <section>
        <PageHeader
            title="Shopping"
            subtitle="Your recommendations based on your current budget."
        />

        <div
            v-if="loading"
            class="space-y-4"
        >
            <div class="h-44 animate-pulse rounded-3xl bg-neutral-200/80" />
            <div
                v-for="n in 3"
                :key="n"
                class="h-32 animate-pulse rounded-2xl bg-neutral-200/70"
            />
        </div>

        <ErrorState
            v-else-if="loadError"
            message="We couldn't load your shopping recommendations."
            @retry="load"
        />

        <template v-else>
            <BudgetHero
                :amount="budget"
                editable
                @edit="openBudgetModal"
            />

            <!-- Priority First — the primary decision -->
            <section
                class="mt-8 rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-5 sm:p-6"
                aria-labelledby="pf-heading"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="badge border-emerald-600 bg-emerald-600 text-white">Recommended</span>
                        <h2
                            id="pf-heading"
                            class="text-lg font-bold text-neutral-900"
                        >
                            Priority First
                        </h2>
                    </div>
                    <p class="text-sm text-neutral-500">What should you buy first?</p>
                </div>

                <!-- Empty state -->
                <div
                    v-if="!priorityFirst.items.length"
                    class="mt-4 rounded-xl bg-white p-6 text-center"
                >
                    <p class="text-base font-semibold text-neutral-900">Nothing to buy right now.</p>
                    <p class="mt-1 text-sm text-neutral-500">
                        You don't currently have enough budget for any active wishlist item.
                    </p>
                    <div class="mt-4 flex flex-wrap justify-center gap-3">
                        <RouterLink
                            to="/wishlist"
                            class="btn-secondary btn-sm"
                        >View Wishlist</RouterLink>
                        <button
                            type="button"
                            class="btn-primary btn-sm"
                            @click="openBudgetModal"
                        >Manage Budget</button>
                    </div>
                </div>

                <ol
                    v-else
                    class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-2"
                >
                    <li
                        v-for="(item, index) in priorityFirst.items"
                        :key="item.id"
                        class="contents"
                    >
                        <RecommendationCard
                            :item="item"
                            :rank="index + 1"
                            @buy="buy"
                        />
                    </li>
                </ol>

                <dl
                    v-if="priorityFirst.items.length"
                    class="mt-4 flex flex-wrap gap-x-10 gap-y-2 rounded-xl bg-white px-5 py-4 text-sm shadow-sm"
                >
                    <div class="flex gap-2">
                        <dt class="text-neutral-500">Recommended Total</dt>
                        <dd class="font-bold text-neutral-900">{{ formatIdr(priorityFirst.total) }}</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="text-neutral-500">Remaining Budget</dt>
                        <dd class="font-bold text-emerald-700">{{ formatIdr(priorityFirst.remaining) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Budget Optimization — alternative strategy -->
            <section
                class="mt-6"
                aria-labelledby="opt-heading"
            >
                <h2
                    id="opt-heading"
                    class="sr-only"
                >
                    Budget Optimization
                </h2>
                <BudgetOptimizationCard
                    :items="optimization.items"
                    :total="optimization.total"
                    :remaining="optimization.remaining"
                    :utilization="optimization.utilization"
                    :available-budget="budget"
                    @buy="buy"
                />
            </section>

            <!-- Can't Afford Yet -->
            <section
                v-if="unaffordableItems.length"
                class="mt-8"
                aria-labelledby="cant-afford-heading"
            >
                <div class="mb-4">
                    <h2
                        id="cant-afford-heading"
                        class="text-lg font-bold text-neutral-900"
                    >
                        Can't Afford Yet
                    </h2>
                    <p class="text-sm text-neutral-500">Items you may want to save for.</p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <UnaffordableItemCard
                        v-for="item in unaffordableItems"
                        :key="item.id"
                        :item="item"
                        :available-budget="budget"
                    />
                </div>
            </section>
        </template>

        <!-- Edit budget modal -->
        <AppModal
            v-if="budgetOpen"
            title="Shopping Budget"
            max-width="max-w-sm"
            @close="budgetOpen = false"
        >
            <form
                class="space-y-4"
                novalidate
                @submit.prevent="saveBudget"
            >
                <p class="text-sm text-neutral-500">
                    Current budget:
                    <strong class="text-neutral-900">Rp{{ budget.toLocaleString('id-ID') }}</strong>
                </p>

                <div>
                    <label
                        for="new-budget"
                        class="field-label"
                    >New budget *</label>
                    <MoneyInput
                        id="new-budget"
                        v-model="newBudget"
                    />
                    <p
                        v-if="budgetError"
                        class="field-error"
                    >
                        {{ budgetError }}
                    </p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button
                        type="button"
                        class="btn-secondary"
                        @click="budgetOpen = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="budgetProcessing"
                        class="btn-primary"
                    >
                        {{ budgetProcessing ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </form>
        </AppModal>

        <PurchaseDialog
            :open="purchaseOpen"
            :item="purchaseTarget"
            :available-budget="budget"
            @close="purchaseOpen = false"
            @purchased="onPurchased"
        />
    </section>
</template>
