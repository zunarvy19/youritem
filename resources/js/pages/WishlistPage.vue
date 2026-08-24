<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import ErrorState from '@/components/ErrorState.vue';
import OverflowMenu from '@/components/OverflowMenu.vue';
import type {MenuItem} from '@/components/OverflowMenu.vue';
import PageHeader from '@/components/PageHeader.vue';
import PriorityBadge from '@/components/PriorityBadge.vue';
import PurposeBadge from '@/components/PurposeBadge.vue';
import WishlistItemFormModal from '@/components/WishlistItemFormModal.vue';
import { useToast } from '@/composables/useToast';
import { formatIdr } from '@/lib/format';
import { ApiError } from '@/services/apiClient';
import {
    archiveWishlistItem,
    fetchWishlistItems,
    restoreWishlistItem,
    useCategoriesLoader,
} from '@/services/wishlistService';
import type { Category, Priority, Purpose, WishlistItem, WishlistStatus } from '@/types';

const toast = useToast();

const items = ref<WishlistItem[]>([]);
const categories = ref<Category[]>([]);
const meta = reactive({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const loadError = ref(false);

const filters = reactive({
    search: '',
    category_id: undefined as number | undefined,
    priority: undefined as Priority | undefined,
    purpose: undefined as Purpose | undefined,
    status: 'ACTIVE' as WishlistStatus | '',
    sort: 'newest' as 'priority' | 'price' | 'newest' | 'oldest',
});

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const formOpen = ref(false);
const editingItem = ref<WishlistItem | null>(null);

const filtersOpen = ref(false);

const confirmState = reactive({
    open: false,
    processing: false,
    itemId: 0,
    mode: 'archive' as 'archive' | 'restore',
});

async function load(page = 1): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        const response = await fetchWishlistItems({
            page,
            search: filters.search.trim() || undefined,
            category_id: filters.category_id,
            priority: filters.priority,
            purpose: filters.purpose,
            status: filters.status === '' ? undefined : filters.status,
            sort: filters.sort,
        });

        items.value = response.data;
        meta.current_page = response.meta.current_page;
        meta.last_page = response.meta.last_page;
        meta.total = response.meta.total;
    } catch (error) {
        loadError.value = true;

        if (!(error instanceof ApiError && error.isUnauthenticated)) {
            toast.error('Failed to load your wishlist.');
        }
    } finally {
        loading.value = false;
    }
}

function onSearchInput(): void {
    if (searchTimer) {
clearTimeout(searchTimer);
}

    searchTimer = setTimeout(() => load(1), 300);
}

onMounted(async () => {
    categories.value = await useCategoriesLoader()();
    await load();
});

// --- Form ---
function openCreate(): void {
    editingItem.value = null;
    formOpen.value = true;
}

function openEdit(item: WishlistItem): void {
    editingItem.value = item;
    formOpen.value = true;
}

function onSaved(): void {
    formOpen.value = false;
    void load(meta.current_page);
}

// --- Row actions via overflow menu ---
function menuFor(item: WishlistItem): MenuItem[] {
    if (item.status === 'ACTIVE') {
        return [
            { key: 'edit', label: 'Edit', icon: 'pencil' },
            { key: 'archive', label: 'Archive', icon: 'archive', danger: true },
        ];
    }

    return [{ key: 'restore', label: 'Restore', icon: 'restore' }];
}

function onAction(item: WishlistItem, key: string): void {
    if (key === 'edit') {
openEdit(item);
} else if (key === 'archive') {
requestConfirm(item, 'archive');
} else if (key === 'restore') {
requestConfirm(item, 'restore');
}
}

function requestConfirm(item: WishlistItem, mode: 'archive' | 'restore'): void {
    confirmState.itemId = item.id;
    confirmState.mode = mode;
    confirmState.open = true;
}

async function runConfirm(): Promise<void> {
    confirmState.processing = true;

    try {
        const response =
            confirmState.mode === 'archive'
                ? await archiveWishlistItem(confirmState.itemId)
                : await restoreWishlistItem(confirmState.itemId);

        toast.success(response.message ?? 'Updated.');
        confirmState.open = false;
        await load(meta.current_page);
    } catch (error) {
        toast.error(error instanceof ApiError ? error.message : 'Something went wrong.');
    } finally {
        confirmState.processing = false;
    }
}

const hasActiveFilters = computed(
    () =>
        filters.search !== '' ||
        filters.category_id !== undefined ||
        filters.priority !== undefined ||
        filters.purpose !== undefined ||
        (filters.status !== '' && filters.status !== 'ACTIVE'),
);

function applyFiltersFromDrawer(): void {
    filtersOpen.value = false;
    void load(1);
}

function clearFilters(): void {
    filters.search = '';
    filters.category_id = undefined;
    filters.priority = undefined;
    filters.purpose = undefined;
    filters.status = 'ACTIVE';
    filters.sort = 'newest';
    void load(1);
}
</script>

<template>
    <section>
        <PageHeader
            title="Wishlist"
            subtitle="Items you want to buy."
        >
            <template #actions>
                <button
                    type="button"
                    class="btn-secondary lg:hidden"
                    aria-label="Open filters"
                    @click="filtersOpen = true"
                >
                    <AppIcon
                        name="funnel"
                        class="h-4 w-4"
                    />
                    Filters
                </button>
                <button
                    type="button"
                    class="btn-primary"
                    @click="openCreate"
                >
                    <AppIcon
                        name="plus"
                        class="h-4 w-4"
                    />
                    Add Item
                </button>
            </template>
        </PageHeader>

        <!-- Desktop toolbar -->
        <div class="card mb-5 hidden flex-wrap items-center gap-3 p-3 lg:flex">
            <input
                v-model="filters.search"
                type="search"
                placeholder="Search items..."
                aria-label="Search items"
                class="input min-w-[12rem] max-w-xs flex-1"
                @input="onSearchInput"
            />

            <select
                v-model.number="filters.category_id"
                aria-label="Filter by category"
                class="input w-auto"
                @change="load(1)"
            >
                <option :value="undefined">All categories</option>
                <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                >
                    {{ category.name }}
                </option>
            </select>

            <select
                v-model="filters.priority"
                aria-label="Filter by priority"
                class="input w-auto"
                @change="load(1)"
            >
                <option :value="undefined">All priorities</option>
                <option value="HIGH">High</option>
                <option value="MEDIUM">Medium</option>
                <option value="LOW">Low</option>
            </select>

            <select
                v-model="filters.purpose"
                aria-label="Filter by need or want"
                class="input w-auto"
                @change="load(1)"
            >
                <option :value="undefined">Need &amp; Want</option>
                <option value="NEED">Need</option>
                <option value="WANT">Want</option>
            </select>

            <select
                v-model="filters.status"
                aria-label="Filter by status"
                class="input w-auto"
                @change="load(1)"
            >
                <option value="ACTIVE">Active</option>
                <option value="ARCHIVED">Archived</option>
                <option value="">All statuses</option>
            </select>

            <select
                v-model="filters.sort"
                aria-label="Sort items"
                class="input ml-auto w-auto"
                @change="load(1)"
            >
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="priority">By priority</option>
                <option value="price">By price</option>
            </select>
        </div>

        <!-- Mobile active-filter summary chip row -->
        <div
            v-if="hasActiveFilters"
            class="mb-4 flex items-center justify-between text-sm lg:hidden"
        >
            <span class="text-neutral-500">Filters applied</span>
            <button
                type="button"
                class="font-medium text-indigo-600 underline underline-offset-2"
                @click="clearFilters"
            >
                Clear all
            </button>
        </div>

        <div
            v-if="loading"
            role="status"
            aria-label="Loading content"
        >
            <div class="space-y-2">
                <div
                    v-for="n in 5"
                    :key="n"
                    class="h-14 animate-pulse rounded-xl bg-neutral-200/80"
                />
            </div>
            <span class="sr-only">Loading…</span>
        </div>

        <ErrorState
            v-else-if="loadError"
            message="We couldn't load your wishlist."
            @retry="load()"
        />

        <EmptyState
            v-else-if="!items.length && !hasActiveFilters"
            title="Your wishlist is empty."
            description="Start adding things you want to buy and we'll help you prioritize them."
        >
            <template #actions>
                <button
                    type="button"
                    class="btn-primary"
                    @click="openCreate"
                >
                    Add Your First Item
                </button>
            </template>
        </EmptyState>

        <EmptyState
            v-else-if="!items.length"
            title="No items match your filters."
            description="Try adjusting your search or clearing the filters."
        >
            <template #actions>
                <button
                    type="button"
                    class="btn-secondary"
                    @click="clearFilters"
                >
                    Clear Filters
                </button>
            </template>
        </EmptyState>

        <template v-else>
            <!-- Mobile cards -->
            <ul class="space-y-3 lg:hidden">
                <li
                    v-for="item in items"
                    :key="item.id"
                    class="card card-hover p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-neutral-900">{{ item.name }}</p>
                            <p class="text-xs text-neutral-500">{{ item.category.name }}</p>
                        </div>
                        <OverflowMenu
                            :items="menuFor(item)"
                            @select="(key) => onAction(item, key)"
                        />
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <PriorityBadge :value="item.priority" />
                        <PurposeBadge :value="item.purpose" />
                        <span
                            v-if="item.status === 'ARCHIVED'"
                            class="badge border-neutral-200 bg-neutral-100 text-neutral-500"
                        >Archived</span>
                    </div>
                    <p class="mt-2 font-bold text-neutral-900">{{ formatIdr(item.estimated_price) }}</p>
                </li>
            </ul>

            <!-- Desktop table -->
            <div class="card hidden overflow-hidden lg:block">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-neutral-100 bg-neutral-50/70 text-xs tracking-wide text-neutral-400 uppercase">
                            <th class="px-5 py-3.5 font-semibold">Item</th>
                            <th class="px-4 py-3.5 font-semibold">Category</th>
                            <th class="px-4 py-3.5 font-semibold">Priority</th>
                            <th class="px-4 py-3.5 font-semibold">Type</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Price</th>
                            <th class="w-16 px-4 py-3.5"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr
                            v-for="item in items"
                            :key="item.id"
                            class="group transition-colors duration-150 hover:bg-neutral-50"
                        >
                            <td class="px-5 py-3.5">
                                <span class="font-semibold text-neutral-900">{{ item.name }}</span>
                                <span
                                    v-if="item.status === 'ARCHIVED'"
                                    class="badge ml-2 border-neutral-200 bg-neutral-100 text-neutral-500"
                                >Archived</span>
                                <span
                                    v-else-if="item.status === 'PURCHASED'"
                                    class="badge ml-2 border-emerald-200 bg-emerald-50 text-emerald-700"
                                >Purchased</span>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-neutral-500">{{ item.category.name }}</td>
                            <td class="px-4 py-3.5"><PriorityBadge :value="item.priority" /></td>
                            <td class="px-4 py-3.5"><PurposeBadge :value="item.purpose" /></td>
                            <td class="px-4 py-3.5 text-right text-sm font-bold text-neutral-900">
                                {{ formatIdr(item.estimated_price) }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <OverflowMenu
                                    :items="menuFor(item)"
                                    @select="(key) => onAction(item, key)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="meta.last_page > 1"
                class="mt-4 flex items-center justify-between text-sm text-neutral-600"
            >
                <span>Page {{ meta.current_page }} of {{ meta.last_page }} · {{ meta.total }} items</span>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="btn-secondary btn-sm"
                        :disabled="meta.current_page <= 1"
                        @click="load(meta.current_page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        type="button"
                        class="btn-secondary btn-sm"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="load(meta.current_page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </template>

        <!-- Mobile filter drawer -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-150"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="filtersOpen"
                    class="fixed inset-0 z-40 bg-neutral-950/40 lg:hidden"
                    @click="filtersOpen = false"
                />
            </Transition>
            <Transition
                enter-active-class="transition-transform duration-250 ease-out"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-200 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <aside
                    v-if="filtersOpen"
                    class="fixed inset-y-0 right-0 z-50 flex w-80 max-w-[85vw] flex-col bg-white shadow-xl"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Wishlist filters"
                >
                    <header class="flex items-center justify-between border-b border-neutral-100 px-5 py-4">
                        <h2 class="font-bold text-neutral-900">Filters &amp; Sort</h2>
                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100"
                            aria-label="Close filters"
                            @click="filtersOpen = false"
                        >
                            ✕
                        </button>
                    </header>

                    <div class="flex-1 space-y-4 overflow-y-auto p-5">
                        <div>
                            <label
                                for="drawer-search"
                                class="field-label"
                            >Search</label>
                            <input
                                id="drawer-search"
                                v-model="filters.search"
                                type="search"
                                class="input"
                                placeholder="Search items..."
                                @input="onSearchInput"
                            />
                        </div>

                        <div>
                            <label
                                for="drawer-category"
                                class="field-label"
                            >Category</label>
                            <select
                                id="drawer-category"
                                v-model.number="filters.category_id"
                                class="input"
                            >
                                <option :value="undefined">All categories</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <fieldset>
                            <legend class="field-label">Priority</legend>
                            <div class="flex gap-2">
                                <button
                                    v-for="option in ['HIGH', 'MEDIUM', 'LOW'] as const"
                                    :key="option"
                                    type="button"
                                    class="flex-1 rounded-xl border px-2 py-2 text-xs font-semibold capitalize transition-colors duration-150"
                                    :class="
                                        filters.priority === option
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                            : 'border-neutral-300 text-neutral-600 hover:bg-neutral-50'
                                    "
                                    :aria-pressed="filters.priority === option"
                                    @click="filters.priority = filters.priority === option ? undefined : option"
                                >
                                    {{ option.toLowerCase() }}
                                </button>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="field-label">Type</legend>
                            <div class="flex gap-2">
                                <button
                                    v-for="option in ['NEED', 'WANT'] as const"
                                    :key="option"
                                    type="button"
                                    class="flex-1 rounded-xl border px-2 py-2 text-xs font-semibold transition-colors duration-150"
                                    :class="
                                        filters.purpose === option
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                            : 'border-neutral-300 text-neutral-600 hover:bg-neutral-50'
                                    "
                                    :aria-pressed="filters.purpose === option"
                                    @click="filters.purpose = filters.purpose === option ? undefined : option"
                                >
                                    {{ option.toLowerCase() }}
                                </button>
                            </div>
                        </fieldset>

                        <div>
                            <label
                                for="drawer-status"
                                class="field-label"
                            >Status</label>
                            <select
                                id="drawer-status"
                                v-model="filters.status"
                                class="input"
                            >
                                <option value="ACTIVE">Active</option>
                                <option value="ARCHIVED">Archived</option>
                                <option value="">All statuses</option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="drawer-sort"
                                class="field-label"
                            >Sort by</label>
                            <select
                                id="drawer-sort"
                                v-model="filters.sort"
                                class="input"
                            >
                                <option value="newest">Newest first</option>
                                <option value="oldest">Oldest first</option>
                                <option value="priority">Priority</option>
                                <option value="price">Price</option>
                            </select>
                        </div>
                    </div>

                    <footer class="border-t border-neutral-100 p-5">
                        <button
                            type="button"
                            class="btn-primary w-full"
                            @click="applyFiltersFromDrawer"
                        >
                            Show Results
                        </button>
                        <button
                            type="button"
                            class="mt-2 w-full rounded-xl px-3 py-2 text-sm font-medium text-neutral-500 hover:bg-neutral-50"
                            @click="clearFilters(); filtersOpen = false"
                        >
                            Clear all filters
                        </button>
                    </footer>
                </aside>
            </Transition>
        </Teleport>

        <WishlistItemFormModal
            :open="formOpen"
            :categories="categories"
            :item="editingItem"
            @close="formOpen = false"
            @saved="onSaved"
        />

        <ConfirmDialog
            :open="confirmState.open"
            :title="confirmState.mode === 'archive' ? 'Archive this item?' : 'Restore this item?'"
            :message="
                confirmState.mode === 'archive'
                    ? 'This item will no longer appear in your shopping recommendations.'
                    : 'This item will reappear in your active wishlist and recommendations.'
            "
            :confirm-label="confirmState.mode === 'archive' ? 'Archive' : 'Restore'"
            :processing="confirmState.processing"
            :danger="confirmState.mode === 'archive'"
            @close="confirmState.open = false"
            @confirm="runConfirm"
        />
    </section>
</template>
