import { apiRequest } from '@/services/apiClient';
import type { ApiDataResponse, Purchase } from '@/types';

export function purchaseItem(
    itemId: number,
    actualPrice: number,
    purchasedAt?: string,
): Promise<ApiDataResponse<Purchase>> {
    return apiRequest(`/api/wishlist-items/${itemId}/purchase`, {
        method: 'POST',
        body: {
            actual_price: actualPrice,
            ...(purchasedAt ? { purchased_at: purchasedAt } : {}),
        },
    });
}

export function fetchPurchases(): Promise<{
    data: Purchase[];
    meta: { current_page: number; last_page: number; total: number };
}> {
    return apiRequest('/api/purchases');
}
