<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PriorityBadge from '@/components/PriorityBadge.vue';
import PurposeBadge from '@/components/PurposeBadge.vue';
import WishlistItemFormModal from '@/components/WishlistItemFormModal.vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { formatIdr } from '@/lib/format';
import { ApiError } from '@/services/apiClient';
import {
    fetchWishlistItem,
    useCategoriesLoader,
} from '@/services/wishlistService';
import type { Category, WishlistItem } from '@/types';

const props = defineProps<{ id: string }>();
const { t } = useI18n();
const toast = useToast();
const item = ref<WishlistItem | null>(null);
const categories = ref<Category[]>([]);
const loading = ref(true);
const editing = ref(false);

async function load(): Promise<void> {
    loading.value = true;

    try {
        item.value = (await fetchWishlistItem(Number(props.id))).data;
    } catch (error) {
        toast.error(
            error instanceof ApiError ? error.message : t('common.error'),
        );
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    categories.value = await useCategoriesLoader()();
    await load();
});
</script>

<template>
    <section>
        <RouterLink
            :to="{ name: 'wishlist' }"
            class="mb-4 inline-flex text-sm font-semibold text-indigo-600"
            >← {{ t('wishlist.back') }}</RouterLink
        >
        <div
            v-if="loading"
            class="h-72 animate-pulse rounded-3xl bg-neutral-200/80"
        />
        <template v-else-if="item">
            <PageHeader :title="item.name" :subtitle="item.category.name">
                <template #actions
                    ><button
                        v-if="item.status !== 'PURCHASED'"
                        class="btn-secondary"
                        @click="editing = true"
                    >
                        {{ t('common.edit') }}
                    </button></template
                >
            </PageHeader>
            <div
                class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.6fr)]"
            >
                <div class="card overflow-hidden">
                    <div class="aspect-[16/8] bg-neutral-100">
                        <img
                            v-if="item.preview?.image_url"
                            :src="item.preview.image_url"
                            :alt="item.preview.title || item.name"
                            class="h-full w-full object-contain"
                            referrerpolicy="no-referrer"
                        />
                        <div
                            v-else
                            class="flex h-full items-center justify-center text-sm text-neutral-400"
                        >
                            {{
                                item.product_url
                                    ? t('wishlist.preview_pending')
                                    : t('wishlist.no_preview')
                            }}
                        </div>
                    </div>
                    <div class="space-y-3 p-6">
                        <p
                            v-if="item.preview?.site_name"
                            class="text-xs font-bold tracking-wide text-indigo-600 uppercase"
                        >
                            {{ item.preview.site_name }}
                        </p>
                        <h2 class="text-xl font-bold text-neutral-900">
                            {{ item.preview?.title || item.name }}
                        </h2>
                        <p
                            v-if="item.preview?.description"
                            class="text-sm leading-6 text-neutral-600"
                        >
                            {{ item.preview.description }}
                        </p>
                        <a
                            v-if="item.product_url"
                            :href="item.product_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn-primary inline-flex"
                            >{{ t('wishlist.open_link') }} ↗</a
                        >
                    </div>
                </div>
                <aside class="card h-fit space-y-5 p-6">
                    <div>
                        <p class="text-sm text-neutral-500">
                            {{ t('wishlist.estimated_price') }}
                        </p>
                        <p class="text-2xl font-black text-neutral-900">
                            {{ formatIdr(item.estimated_price) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <PriorityBadge :value="item.priority" /><PurposeBadge
                            :value="item.purpose"
                        />
                    </div>
                    <div v-if="item.notes">
                        <p class="field-label">{{ t('wishlist.notes') }}</p>
                        <p class="text-sm whitespace-pre-wrap text-neutral-700">
                            {{ item.notes }}
                        </p>
                    </div>
                </aside>
            </div>
            <WishlistItemFormModal
                :open="editing"
                :categories="categories"
                :item="item"
                @close="editing = false"
                @saved="
                    (saved) => {
                        item = saved;
                        editing = false;
                    }
                "
            />
        </template>
        <EmptyState v-else :title="t('wishlist.not_found')" />
    </section>
</template>
