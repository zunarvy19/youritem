<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import AppModal from '@/components/AppModal.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import { formatIdr } from '@/lib/format';
import { purchaseItem } from '@/services/purchaseService';
import type { RecommendationItem } from '@/types';

const props = defineProps<{
    open: boolean;
    item: RecommendationItem | null;
    availableBudget: number;
}>();

const emit = defineEmits<{ close: []; purchased: [itemId: number] }>();

const form = reactive({
    actual_price: null as number | null,
});

const processing = ref(false);
const errorMessage = ref('');
const fieldErrors = ref<Record<string, string[]>>({});

watch(
    () => props.open,
    (open) => {
        if (open && props.item) {
            form.actual_price = props.item.estimated_price;
            errorMessage.value = '';
            fieldErrors.value = {};
        }
    },
);

const actualPrice = computed(() => form.actual_price ?? 0);
const remaining = computed(() => props.availableBudget - actualPrice.value);
const priceInvalid = computed(
    () =>
        !Number.isFinite(actualPrice.value) ||
        actualPrice.value <= 0 ||
        !Number.isInteger(actualPrice.value),
);

async function submit(): Promise<void> {
    if (processing.value || props.item === null) {
        return;
    }

    if (priceInvalid.value) {
        fieldErrors.value = {
            actual_price: [
                'Purchase price must be a whole number greater than zero.',
            ],
        };

        return;
    }

    processing.value = true;
    errorMessage.value = '';
    fieldErrors.value = {};

    try {
        await purchaseItem(props.item.id, actualPrice.value);
        emit('purchased', props.item.id);
    } catch (error) {
        if (error instanceof Error) {
            errorMessage.value = error.message;
        } else {
            errorMessage.value = 'Unable to complete the purchase.';
        }
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <AppModal
        v-if="open && item"
        title="Confirm Purchase"
        max-width="max-w-sm"
        @close="emit('close')"
    >
        <form class="space-y-4" novalidate @submit.prevent="submit">
            <div class="rounded-xl bg-neutral-50 px-4 py-3">
                <p
                    class="text-[11px] font-semibold tracking-wider text-neutral-400 uppercase"
                >
                    Item
                </p>
                <p class="mt-0.5 font-bold text-neutral-900">{{ item.name }}</p>
            </div>

            <dl class="space-y-2 rounded-xl bg-neutral-50 px-4 py-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-neutral-500">Estimated price</dt>
                    <dd class="font-medium">
                        {{ formatIdr(item.estimated_price) }}
                    </dd>
                </div>
                <div
                    class="flex justify-between border-t border-neutral-200/70 pt-2"
                >
                    <dt class="text-neutral-500">Available budget</dt>
                    <dd class="font-medium">
                        {{ formatIdr(availableBudget) }}
                    </dd>
                </div>
                <div
                    class="flex justify-between"
                    :class="
                        remaining < 0
                            ? 'font-bold text-rose-600'
                            : 'font-bold text-emerald-700'
                    "
                >
                    <dt>Remaining budget</dt>
                    <dd>{{ formatIdr(Math.max(0, remaining)) }}</dd>
                </div>
            </dl>

            <div>
                <label for="purchase-price" class="field-label"
                    >Actual purchase price *</label
                >
                <MoneyInput id="purchase-price" v-model="form.actual_price" />
                <p class="mt-1 text-xs text-neutral-400">
                    May differ from the estimate — the actual amount is what
                    gets deducted.
                </p>
                <p v-if="fieldErrors.actual_price" class="field-error">
                    {{ fieldErrors.actual_price[0] }}
                </p>
            </div>

            <p
                v-if="errorMessage"
                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
                role="alert"
            >
                {{ errorMessage }}
            </p>

            <div class="flex justify-end gap-3 pt-1">
                <button
                    type="button"
                    class="btn-secondary"
                    @click="emit('close')"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="processing || priceInvalid || remaining < 0"
                    class="btn-success"
                >
                    {{ processing ? 'Purchasing...' : 'Confirm Purchase' }}
                </button>
            </div>
        </form>
    </AppModal>
</template>
