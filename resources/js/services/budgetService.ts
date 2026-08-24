import { apiRequest } from '@/services/apiClient';
import type { RecommendationResult } from '@/types';

export function fetchRecommendations(): Promise<RecommendationResult> {
    return apiRequest<{ data: RecommendationResult }>('/api/shopping/recommendations').then(
        (response) => response.data,
    );
}

export function fetchBudget(): Promise<number> {
    return apiRequest<{ data: { amount: number } }>('/api/budget').then(
        (response) => response.data.amount,
    );
}

export function updateBudget(amount: number): Promise<{ message: string }> {
    return apiRequest('/api/budget', {
        method: 'PUT',
        body: { amount },
    });
}
