<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import AppModal from '@/components/AppModal.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { formatIdr } from '@/lib/format';
import { ApiError } from '@/services/apiClient';
import {
    createBudgetTransaction,
    fetchBudget,
    updateBudget,
} from '@/services/budgetService';
import type { BudgetOverview } from '@/types';

const { locale, t } = useI18n();
const toast = useToast();
const overview = ref<BudgetOverview | null>(null);
const loading = ref(true);
const modal = ref<'INCOME' | 'EXPENSE' | 'ADJUSTMENT' | null>(null);
const processing = ref(false);
const error = ref('');
const form = reactive({
    amount: null as number | null,
    description: '',
    occurred_at: '',
});

const monthLabel = computed(() =>
    new Intl.DateTimeFormat(locale.value === 'id' ? 'id-ID' : 'en-US', {
        month: 'long',
        year: 'numeric',
    }).format(new Date()),
);

async function load(): Promise<void> {
    loading.value = true;

    try {
        overview.value = await fetchBudget();
    } catch (err) {
        if (!(err instanceof ApiError && err.isUnauthenticated)) {
            toast.error(t('budget.load_error'));
        }
    } finally {
        loading.value = false;
    }
}

function open(type: typeof modal.value): void {
    modal.value = type;
    form.amount = type === 'ADJUSTMENT' ? (overview.value?.amount ?? 0) : null;
    form.description = '';
    form.occurred_at = new Date().toISOString().slice(0, 10);
    error.value = '';
}

async function save(): Promise<void> {
    if (
        form.amount === null ||
        !Number.isInteger(form.amount) ||
        form.amount < (modal.value === 'ADJUSTMENT' ? 0 : 1) ||
        !form.description.trim()
    ) {
        error.value = t('budget.validation');

        return;
    }

    processing.value = true;

    try {
        const response =
            modal.value === 'ADJUSTMENT'
                ? await updateBudget(form.amount, form.description.trim())
                : await createBudgetTransaction({
                      type: modal.value!,
                      amount: form.amount,
                      description: form.description.trim(),
                      occurred_at: form.occurred_at || null,
                  });
        toast.success(response.message ?? t('budget.saved'));
        modal.value = null;
        await load();
    } catch (err) {
        error.value = err instanceof ApiError ? err.message : t('common.error');
    } finally {
        processing.value = false;
    }
}

function transactionLabel(type: string): string {
    return t(`budget.type.${type.toLowerCase()}`);
}

onMounted(load);
</script>

<template>
    <section>
        <PageHeader
            :title="t('budget.title')"
            :subtitle="t('budget.subtitle')"
        />
        <div
            v-if="loading"
            class="h-56 animate-pulse rounded-3xl bg-neutral-200/80"
        />
        <template v-else-if="overview">
            <div
                class="rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 p-5 text-white shadow-lg sm:rounded-3xl sm:p-8"
            >
                <p class="text-sm font-medium text-indigo-100">
                    {{ t('budget.available') }}
                </p>
                <p class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">
                    {{ formatIdr(overview.amount) }}
                </p>
                <div
                    class="mt-6 grid grid-cols-2 gap-2.5 sm:flex sm:flex-wrap sm:gap-3"
                >
                    <button
                        class="min-w-0 rounded-xl bg-white px-3 py-2.5 text-sm font-bold text-emerald-700 sm:px-4"
                        @click="open('INCOME')"
                    >
                        + {{ t('budget.add_income') }}
                    </button>
                    <button
                        class="min-w-0 rounded-xl bg-white/15 px-3 py-2.5 text-sm font-bold text-white ring-1 ring-white/30 sm:px-4"
                        @click="open('EXPENSE')"
                    >
                        − {{ t('budget.add_expense') }}
                    </button>
                    <button
                        class="col-span-2 rounded-xl px-3 py-2.5 text-sm font-semibold text-indigo-100 hover:bg-white/10 sm:col-auto sm:px-4"
                        @click="open('ADJUSTMENT')"
                    >
                        {{ t('budget.adjust') }}
                    </button>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:mt-6 sm:gap-4">
                <div class="card min-w-0 p-4 sm:p-5">
                    <p class="text-xs leading-5 text-neutral-500 sm:text-sm">
                        {{ t('budget.income_month', { month: monthLabel }) }}
                    </p>
                    <p
                        class="mt-1 text-xl font-bold text-emerald-600 sm:text-2xl"
                    >
                        +{{ formatIdr(overview.income) }}
                    </p>
                </div>
                <div class="card min-w-0 p-4 sm:p-5">
                    <p class="text-xs leading-5 text-neutral-500 sm:text-sm">
                        {{ t('budget.expense_month', { month: monthLabel }) }}
                    </p>
                    <p class="mt-1 text-xl font-bold text-rose-600 sm:text-2xl">
                        −{{ formatIdr(overview.expense) }}
                    </p>
                </div>
            </div>

            <div class="card mt-6 overflow-hidden">
                <div class="border-b border-neutral-100 px-5 py-4">
                    <h2 class="font-bold text-neutral-900">
                        {{ t('budget.history') }}
                    </h2>
                </div>
                <div
                    v-if="!overview.transactions.length"
                    class="p-8 text-center text-sm text-neutral-500"
                >
                    {{ t('budget.empty') }}
                </div>
                <ul v-else class="divide-y divide-neutral-100">
                    <li
                        v-for="transaction in overview.transactions"
                        :key="transaction.id"
                        class="flex flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:px-5"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-neutral-900">
                                {{
                                    transaction.description ||
                                    transactionLabel(transaction.type)
                                }}
                            </p>
                            <p class="text-xs text-neutral-500">
                                {{ transactionLabel(transaction.type) }} ·
                                {{
                                    new Date(
                                        transaction.occurred_at,
                                    ).toLocaleDateString(
                                        locale === 'id' ? 'id-ID' : 'en-US',
                                    )
                                }}
                            </p>
                        </div>
                        <p
                            class="self-end font-bold sm:shrink-0 sm:self-auto"
                            :class="
                                transaction.amount >= 0
                                    ? 'text-emerald-600'
                                    : 'text-rose-600'
                            "
                        >
                            {{ transaction.amount >= 0 ? '+' : '−'
                            }}{{ formatIdr(Math.abs(transaction.amount)) }}
                        </p>
                    </li>
                </ul>
            </div>
        </template>

        <AppModal
            v-if="modal"
            :title="
                modal === 'INCOME'
                    ? t('budget.add_income')
                    : modal === 'EXPENSE'
                      ? t('budget.add_expense')
                      : t('budget.adjust')
            "
            @close="modal = null"
        >
            <form class="space-y-4" @submit.prevent="save">
                <div>
                    <label class="field-label"
                        >{{
                            modal === 'ADJUSTMENT'
                                ? t('budget.target_balance')
                                : t('budget.amount')
                        }}
                        *</label
                    ><MoneyInput v-model="form.amount" />
                </div>
                <div>
                    <label class="field-label"
                        >{{ t('budget.description') }} *</label
                    ><input
                        v-model="form.description"
                        class="input"
                        type="text"
                        maxlength="255"
                    />
                </div>
                <div v-if="modal !== 'ADJUSTMENT'">
                    <label class="field-label">{{ t('budget.date') }}</label
                    ><input
                        v-model="form.occurred_at"
                        class="input"
                        type="date"
                    />
                </div>
                <p v-if="error" class="field-error">{{ error }}</p>
                <div class="grid grid-cols-2 gap-3 sm:flex sm:justify-end">
                    <button
                        type="button"
                        class="btn-secondary"
                        @click="modal = null"
                    >
                        {{ t('common.cancel') }}</button
                    ><button class="btn-primary" :disabled="processing">
                        {{ processing ? t('common.saving') : t('common.save') }}
                    </button>
                </div>
            </form>
        </AppModal>
    </section>
</template>
