<script setup lang="ts">
import { onMounted, ref } from 'vue';
import BudgetHero from '@/components/BudgetHero.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useToast } from '@/composables/useToast';
import { ApiError } from '@/services/apiClient';
import { fetchBudget, updateBudget } from '@/services/budgetService';

const toast = useToast();

const amount = ref<number | null>(null);
const draft = ref<number | null>(null);
const loading = ref(true);
const processing = ref(false);
const error = ref('');
const editing = ref(false);

async function load(): Promise<void> {
    loading.value = true;

    try {
        amount.value = await fetchBudget();
    } catch (error) {
        if (!(error instanceof ApiError && error.isUnauthenticated)) {
            toast.error('Failed to load your budget.');
        }
    } finally {
        loading.value = false;
    }
}

onMounted(load);

function startEditing(): void {
    draft.value = amount.value;
    error.value = '';
    editing.value = true;
}

async function save(): Promise<void> {
    if (draft.value === null || !Number.isInteger(draft.value) || draft.value < 0) {
        error.value = 'Budget must be a whole number of zero or more.';

        return;
    }

    processing.value = true;
    error.value = '';

    try {
        const response = await updateBudget(draft.value);
        amount.value = draft.value;
        editing.value = false;
        toast.success(response.message ?? 'Budget updated.');
    } catch (err) {
        error.value = err instanceof ApiError ? err.message : 'Unable to update the budget.';
    } finally {
        processing.value = false;
    }
}
</script>


<template>
    <section>
        <PageHeader
            title="Budget"
            subtitle="The money you have available for planned purchases."
        />

        <div
            v-if="loading"
            class="h-40 animate-pulse rounded-3xl bg-neutral-200/80"
        />

        <template v-else>
            <BudgetHero
                :amount="amount ?? 0"
                :editable="!editing"
                @edit="startEditing"
            />

            <div
                v-if="editing"
                class="card mt-6 p-6"
            >
                <h2 class="mb-4 font-semibold text-neutral-900">Set a new budget</h2>
                <label
                    for="budget-input"
                    class="field-label"
                >New budget *</label>
                <input
                    id="budget-input"
                    type="text"
                    inputmode="numeric"
                    class="input"
                    :value="draft === null ? '' : new Intl.NumberFormat('id-ID').format(draft)"
                    @input="
                        (event) => {
                            const digits = (event.target as HTMLInputElement).value.replace(/\D/g, '');
                            draft = digits === '' ? null : Number(digits);
                            (event.target as HTMLInputElement).value =
                                digits === '' ? '' : new Intl.NumberFormat('id-ID').format(Number(digits));
                        }
                    "
                />
                <p
                    v-if="error"
                    class="field-error"
                >
                    {{ error }}
                </p>

                <div class="mt-5 flex gap-3">
                    <button
                        type="button"
                        class="btn-primary"
                        :disabled="processing"
                        @click="save"
                    >
                        {{ processing ? 'Saving...' : 'Save Budget' }}
                    </button>
                    <button
                        type="button"
                        class="btn-secondary"
                        @click="editing = false"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <p
                v-else
                class="mt-6 text-sm text-neutral-500"
            >
                Your recommendations and purchase limits are always based on this amount.
                You can change it anytime from here or the Shopping page.
            </p>
        </template>
    </section>
</template>
