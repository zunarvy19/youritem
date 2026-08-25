<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import AppModal from '@/components/AppModal.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import ErrorState from '@/components/ErrorState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { ApiError } from '@/services/apiClient';
import {
    createCategory,
    deleteCategory,
    fetchCategories,
    updateCategory,
} from '@/services/categoryService';
import type { Category } from '@/types';

const { t } = useI18n();
const toast = useToast();
const categories = ref<Category[]>([]);
const loading = ref(true);
const loadError = ref(false);
const formOpen = ref(false);
const editing = ref<Category | null>(null);
const deleting = ref<Category | null>(null);
const processing = ref(false);
const fieldErrors = ref<Record<string, string[]>>({});
const form = reactive({ name: '', is_active: true });

const grouped = computed(() => [
    {
        key: 'active',
        label: t('common.active'),
        hint: t('categories.available_hint'),
        items: categories.value.filter((category) => category.is_active),
    },
    {
        key: 'inactive',
        label: t('common.inactive'),
        hint: t('categories.inactive_hint'),
        items: categories.value.filter((category) => !category.is_active),
    },
]);

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        categories.value = await fetchCategories(true);
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

function openCreate(): void {
    editing.value = null;
    form.name = '';
    form.is_active = true;
    fieldErrors.value = {};
    formOpen.value = true;
}

function openEdit(category: Category): void {
    editing.value = category;
    form.name = category.name;
    form.is_active = category.is_active;
    fieldErrors.value = {};
    formOpen.value = true;
}

async function submit(): Promise<void> {
    if (processing.value) {
        return;
    }

    processing.value = true;
    fieldErrors.value = {};

    try {
        if (editing.value) {
            await updateCategory(editing.value, form);
            toast.success(t('categories.updated'));
        } else {
            await createCategory(form);
            toast.success(t('categories.created'));
        }

        formOpen.value = false;
        await load();
    } catch (error) {
        if (error instanceof ApiError) {
            fieldErrors.value = error.errors;
            toast.error(error.message);
        }
    } finally {
        processing.value = false;
    }
}

async function toggleStatus(category: Category): Promise<void> {
    try {
        await updateCategory(category, { is_active: !category.is_active });
        toast.success(t('categories.updated'));
        await load();
    } catch (error) {
        toast.error(error instanceof Error ? error.message : t('common.error'));
    }
}

async function confirmDelete(): Promise<void> {
    if (!deleting.value || processing.value) {
        return;
    }

    processing.value = true;

    try {
        await deleteCategory(deleting.value);
        toast.success(t('categories.deleted'));
        deleting.value = null;
        await load();
    } catch (error) {
        const message =
            error instanceof ApiError && error.errors.category
                ? t('categories.delete_used_error')
                : error instanceof Error
                  ? error.message
                  : t('common.error');
        toast.error(message);
        deleting.value = null;
    } finally {
        processing.value = false;
    }
}

onMounted(load);
</script>

<template>
    <section>
        <PageHeader
            :title="t('categories.title')"
            :subtitle="t('categories.subtitle')"
        >
            <template #actions>
                <button type="button" class="btn-primary" @click="openCreate">
                    + {{ t('categories.add') }}
                </button>
            </template>
        </PageHeader>

        <div
            v-if="loading"
            class="h-64 animate-pulse rounded-2xl bg-neutral-200/80"
        />

        <ErrorState
            v-else-if="loadError"
            :message="t('categories.load_error')"
            @retry="load"
        />

        <EmptyState
            v-else-if="!categories.length"
            :title="t('categories.empty')"
            :description="t('categories.empty_description')"
        >
            <template #actions>
                <button type="button" class="btn-primary" @click="openCreate">
                    {{ t('categories.add') }}
                </button>
            </template>
        </EmptyState>

        <div v-else class="space-y-8">
            <section
                v-for="group in grouped.filter((item) => item.items.length)"
                :key="group.key"
            >
                <div class="mb-3 flex items-end justify-between gap-3">
                    <div>
                        <h2
                            class="text-xs font-semibold tracking-wider text-neutral-500 uppercase"
                        >
                            {{ group.label }} · {{ group.items.length }}
                        </h2>
                        <p class="mt-1 text-xs text-neutral-400">
                            {{ group.hint }}
                        </p>
                    </div>
                </div>

                <ul
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <li
                        v-for="category in group.items"
                        :key="category.id"
                        class="card flex items-center gap-3 p-4"
                    >
                        <span
                            class="h-2.5 w-2.5 shrink-0 rounded-full"
                            :class="
                                category.is_active
                                    ? 'bg-emerald-400'
                                    : 'bg-neutral-300'
                            "
                        />
                        <span
                            class="min-w-0 flex-1 truncate text-sm font-semibold text-neutral-800"
                        >
                            {{ category.name }}
                        </span>
                        <div class="flex shrink-0 items-center gap-1">
                            <button
                                type="button"
                                class="rounded-lg px-2 py-1 text-xs font-semibold text-neutral-500 hover:bg-neutral-100 hover:text-neutral-800"
                                @click="toggleStatus(category)"
                            >
                                {{
                                    category.is_active
                                        ? t('common.inactive')
                                        : t('common.active')
                                }}
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-2 py-1 text-xs font-semibold text-indigo-600 hover:bg-indigo-50"
                                @click="openEdit(category)"
                            >
                                {{ t('common.edit') }}
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                                @click="deleting = category"
                            >
                                {{ t('common.delete') }}
                            </button>
                        </div>
                    </li>
                </ul>
            </section>
        </div>

        <AppModal
            v-if="formOpen"
            :title="
                editing
                    ? t('categories.edit_title')
                    : t('categories.create_title')
            "
            @close="formOpen = false"
        >
            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label for="category-name" class="field-label">{{
                        t('categories.name')
                    }}</label>
                    <input
                        id="category-name"
                        v-model="form.name"
                        class="input"
                        type="text"
                        maxlength="100"
                        required
                        autofocus
                    />
                    <p v-if="fieldErrors.name" class="field-error">
                        {{ fieldErrors.name[0] }}
                    </p>
                </div>

                <label
                    class="flex items-start gap-3 rounded-xl bg-neutral-50 p-3"
                >
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 accent-indigo-600"
                    />
                    <span>
                        <span
                            class="block text-sm font-semibold text-neutral-800"
                            >{{ t('common.active') }}</span
                        >
                        <span class="mt-0.5 block text-xs text-neutral-500">{{
                            t('categories.status_help')
                        }}</span>
                    </span>
                </label>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        class="btn-secondary"
                        @click="formOpen = false"
                    >
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="btn-primary"
                        :disabled="processing"
                    >
                        {{
                            processing
                                ? editing
                                    ? t('common.saving')
                                    : t('categories.creating')
                                : editing
                                  ? t('common.save')
                                  : t('categories.create')
                        }}
                    </button>
                </div>
            </form>
        </AppModal>

        <ConfirmDialog
            :open="deleting !== null"
            :title="t('categories.delete_title')"
            :message="
                t('categories.delete_message', { name: deleting?.name ?? '' })
            "
            :confirm-label="
                processing ? t('common.deleting') : t('common.delete')
            "
            :cancel-label="t('common.cancel')"
            :processing="processing"
            danger
            @close="deleting = null"
            @confirm="confirmDelete"
        />
    </section>
</template>
