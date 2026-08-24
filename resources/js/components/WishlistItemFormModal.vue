<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import AppModal from '@/components/AppModal.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import { useToast } from '@/composables/useToast';
import { ApiError } from '@/services/apiClient';
import { createWishlistItem, updateWishlistItem } from '@/services/wishlistService';
import type { Category, Priority, Purpose, WishlistItem } from '@/types';

const props = defineProps<{
    open: boolean;
    categories: Category[];
    item: WishlistItem | null;
}>();

const emit = defineEmits<{ close: []; saved: [item: WishlistItem] }>();

const toast = useToast();

const form = reactive({
    name: '',
    category_id: 0,
    priority: 'MEDIUM' as Priority,
    purpose: 'NEED' as Purpose,
    estimated_price: null as number | null,
    notes: '',
});

const processing = ref(false);
const errorMessage = ref('');
const fieldErrors = ref<Record<string, string[]>>({});

const isEdit = computed(() => props.item !== null);

watch(
    () => props.open,
    (open) => {
        if (!open) {
return;
}

        errorMessage.value = '';
        fieldErrors.value = {};

        if (props.item) {
            form.name = props.item.name;
            form.category_id = props.item.category.id;
            form.priority = props.item.priority;
            form.purpose = props.item.purpose;
            form.estimated_price = props.item.estimated_price;
            form.notes = props.item.notes ?? '';
        } else {
            form.name = '';
            form.category_id = props.categories[0]?.id ?? 0;
            form.priority = 'MEDIUM';
            form.purpose = 'NEED';
            form.estimated_price = null;
            form.notes = '';
        }
    },
);

const priorityOptions: { value: Priority; label: string; hint: string }[] = [
    { value: 'HIGH', label: 'High', hint: 'Very important' },
    { value: 'MEDIUM', label: 'Medium', hint: 'Somewhat urgent' },
    { value: 'LOW', label: 'Low', hint: 'Can wait' },
];

const purposeOptions: { value: Purpose; label: string; description: string }[] = [
    { value: 'NEED', label: 'Need', description: 'Essential or replacing something' },
    { value: 'WANT', label: 'Want', description: 'Nice to have' },
];

function clientValidate(): boolean {
    fieldErrors.value = {};

    if (!form.name.trim()) {
        fieldErrors.value.name = ['The name field is required.'];
    }

    if (!form.category_id) {
        fieldErrors.value.category_id = ['Please select a category.'];
    }

    if (form.estimated_price === null || !Number.isInteger(form.estimated_price) || form.estimated_price <= 0) {
        fieldErrors.value.estimated_price = ['Price must be a whole number greater than zero.'];
    }

    return Object.keys(fieldErrors.value).length === 0;
}

async function submit(): Promise<void> {
    if (processing.value) {
return;
}

    if (!clientValidate()) {
return;
}

    processing.value = true;
    errorMessage.value = '';

    const payload = {
        name: form.name.trim(),
        category_id: form.category_id,
        priority: form.priority,
        purpose: form.purpose,
        estimated_price: form.estimated_price as number,
        notes: form.notes.trim() || null,
    };

    try {
        const response = isEdit.value
            ? await updateWishlistItem(props.item!.id, payload)
            : await createWishlistItem(payload);

        toast.success(response.message ?? 'Saved.');
        emit('saved', response.data);
    } catch (error) {
        if (error instanceof ApiError) {
            errorMessage.value = error.isValidationError
                ? 'Please fix the highlighted fields.'
                : error.message;
            fieldErrors.value = error.errors;
        } else {
            errorMessage.value = 'Unable to save the item. Please try again.';
        }
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <AppModal
        v-if="open"
        :title="isEdit ? 'Edit Wishlist Item' : 'Add to Wishlist'"
        @close="emit('close')"
    >
        <form
            class="space-y-5"
            novalidate
            @submit.prevent="submit"
        >
            <p
                v-if="errorMessage"
                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
                role="alert"
            >
                {{ errorMessage }}
            </p>

            <div>
                <label
                    for="item-name"
                    class="field-label"
                >Name *</label>
                <input
                    id="item-name"
                    v-model="form.name"
                    type="text"
                    class="input"
                    placeholder="e.g. Sony WH-1000XM6"
                    :aria-invalid="fieldErrors.name ? 'true' : undefined"
                />
                <p
                    v-if="fieldErrors.name"
                    class="field-error"
                >
                    {{ fieldErrors.name[0] }}
                </p>
            </div>

            <div>
                <label
                    for="item-category"
                    class="field-label"
                >Category *</label>
                <select
                    id="item-category"
                    v-model.number="form.category_id"
                    class="input"
                >
                    <option
                        v-for="category in categories"
                        :key="category.id"
                        :value="category.id"
                    >
                        {{ category.name }}
                    </option>
                </select>
                <p
                    v-if="fieldErrors.category_id"
                    class="field-error"
                >
                    {{ fieldErrors.category_id[0] }}
                </p>
            </div>

            <fieldset>
                <legend class="field-label">Priority *</legend>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="option in priorityOptions"
                        :key="option.value"
                        type="button"
                        class="rounded-xl border-2 px-2 py-2.5 text-center transition-colors duration-150"
                        :class="
                            form.priority === option.value
                                ? option.value === 'HIGH'
                                    ? 'border-rose-400 bg-rose-50'
                                    : option.value === 'MEDIUM'
                                        ? 'border-indigo-400 bg-indigo-50'
                                        : 'border-neutral-400 bg-neutral-50'
                                : 'border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50'
                        "
                        :aria-pressed="form.priority === option.value"
                        @click="form.priority = option.value"
                    >
                        <span class="block text-sm font-bold text-neutral-900">{{ option.label }}</span>
                        <span class="mt-0.5 block text-[11px] text-neutral-500">{{ option.hint }}</span>
                    </button>
                </div>
            </fieldset>

            <fieldset>
                <legend class="field-label">Purpose *</legend>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="option in purposeOptions"
                        :key="option.value"
                        type="button"
                        class="rounded-xl border-2 px-3 py-2.5 text-left transition-colors duration-150"
                        :class="
                            form.purpose === option.value
                                ? option.value === 'NEED'
                                    ? 'border-emerald-400 bg-emerald-50'
                                    : 'border-amber-400 bg-amber-50'
                                : 'border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50'
                        "
                        :aria-pressed="form.purpose === option.value"
                        @click="form.purpose = option.value"
                    >
                        <span class="block text-sm font-bold text-neutral-900">{{ option.label }}</span>
                        <span class="mt-0.5 block text-[11px] leading-snug text-neutral-500">{{ option.description }}</span>
                    </button>
                </div>
            </fieldset>

            <div>
                <label
                    for="item-price"
                    class="field-label"
                >Estimated Price *</label>
                <MoneyInput
                    id="item-price"
                    v-model="form.estimated_price"
                    placeholder="0"
                    :aria-invalid="fieldErrors.estimated_price ? 'true' : undefined"
                />
                <p
                    v-if="fieldErrors.estimated_price"
                    class="field-error"
                >
                    {{ fieldErrors.estimated_price[0] }}
                </p>
            </div>

            <div>
                <label
                    for="item-notes"
                    class="field-label"
                >Notes</label>
                <textarea
                    id="item-notes"
                    v-model="form.notes"
                    rows="2"
                    class="input resize-none"
                    placeholder="Optional details..."
                />
            </div>

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
                    :disabled="processing"
                    class="btn-primary"
                >
                    {{ processing ? 'Saving...' : isEdit ? 'Save Changes' : 'Add Item' }}
                </button>
            </div>
        </form>
    </AppModal>
</template>
