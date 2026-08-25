import { apiRequest } from '@/services/apiClient';
import { fetchCategories } from '@/services/categoryService';
import type {
    ApiDataResponse,
    Category,
    Priority,
    Purpose,
    WishlistItem,
    WishlistStatus,
} from '@/types';

export type WishlistQuery = {
    search?: string;
    category_id?: number;
    priority?: Priority;
    purpose?: Purpose;
    status?: WishlistStatus;
    sort?: 'priority' | 'price' | 'newest' | 'oldest';
    page?: number;
    per_page?: number;
};

export type WishlistPayload = {
    name: string;
    category_id: number;
    priority: Priority;
    purpose: Purpose;
    estimated_price: number;
    notes?: string | null;
    product_url?: string | null;
};

export function fetchWishlistItems(
    query: WishlistQuery = {},
): Promise<{ data: WishlistItem[]; meta: PaginationMeta }> {
    return apiRequest('/api/wishlist-items', {
        query: query as Record<string, string | number | boolean | undefined>,
    });
}

export function fetchWishlistItem(
    id: number,
): Promise<ApiDataResponse<WishlistItem>> {
    return apiRequest(`/api/wishlist-items/${id}`);
}

export function createWishlistItem(
    payload: WishlistPayload,
): Promise<ApiDataResponse<WishlistItem>> {
    return apiRequest('/api/wishlist-items', { method: 'POST', body: payload });
}

export function updateWishlistItem(
    id: number,
    payload: Partial<WishlistPayload>,
): Promise<ApiDataResponse<WishlistItem>> {
    return apiRequest(`/api/wishlist-items/${id}`, {
        method: 'PUT',
        body: payload,
    });
}

export function archiveWishlistItem(
    id: number,
): Promise<ApiDataResponse<WishlistItem>> {
    return apiRequest(`/api/wishlist-items/${id}/archive`, {
        method: 'PATCH',
        body: {},
    });
}

export function restoreWishlistItem(
    id: number,
): Promise<ApiDataResponse<WishlistItem>> {
    return apiRequest(`/api/wishlist-items/${id}/restore`, {
        method: 'PATCH',
        body: {},
    });
}

export function useCategoriesLoader(): () => Promise<Category[]> {
    let cache: Category[] | null = null;

    return () => {
        if (cache) {
            return Promise.resolve(cache);
        }

        return fetchCategories().then((categories) => {
            cache = categories;

            return categories;
        });
    };
}

type PaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};
