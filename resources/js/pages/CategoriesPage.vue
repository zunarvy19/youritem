<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import ErrorState from '@/components/ErrorState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { fetchCategories } from '@/services/categoryService';
import type { Category } from '@/types';

const categories = ref<Category[]>([]);
const loading = ref(true);
const loadError = ref(false);

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = false;

    try {
        categories.value = await fetchCategories();
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(load);

const grouped = computed(() => [
    {
        label: 'Active',
        hint: 'Available when adding new items',
        items: categories.value.filter((category) => category.is_active),
        tone: 'emerald' as const,
    },
    {
        label: 'Inactive',
        hint: 'Kept for existing items only',
        items: categories.value.filter((category) => !category.is_active),
        tone: 'neutral' as const,
    },
]);
</script>

<template>
    <section>
        <PageHeader
            title="Categories"
            subtitle="How your wishlist is organised."
        />

        <div
            v-if="loading"
            class="h-64 animate-pulse rounded-2xl bg-neutral-200/80"
        />

        <ErrorState
            v-else-if="loadError"
            message="We couldn't load the categories."
            @retry="load"
        />

        <EmptyState
            v-else-if="!categories.length"
            title="No categories yet."
            description="Default categories are created during setup."
        />

        <div v-else>
            <div
                v-for="group in grouped.filter((g) => g.items.length)"
                :key="group.label"
                class="mb-8"
            >
                <p class="mb-3 text-xs font-semibold tracking-wider text-neutral-400 uppercase">
                    {{ group.label }} · {{ group.items.length }}
                </p>
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <li
                        v-for="category in group.items"
                        :key="category.id"
                        class="card card-hover flex items-center gap-3 p-4"
                    >
                        <span
                            class="h-2.5 w-2.5 shrink-0 rounded-full"
                            :class="group.tone === 'emerald' ? 'bg-emerald-400' : 'bg-neutral-300'"
                            :title="group.tone === 'emerald' ? 'Active' : 'Inactive'"
                        />
                        <span class="truncate text-sm font-semibold text-neutral-800">{{ category.name }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
