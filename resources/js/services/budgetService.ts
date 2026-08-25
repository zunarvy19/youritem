import { apiRequest } from '@/services/apiClient';
import type {
    BudgetOverview,
    BudgetTransactionType,
    RecommendationResult,
} from '@/types';

export function fetchRecommendations(): Promise<RecommendationResult> {
    return apiRequest<{ data: RecommendationResult }>(
        '/api/shopping/recommendations',
    ).then((response) => response.data);
}

export function fetchBudget(): Promise<BudgetOverview> {
    return apiRequest<{ data: BudgetOverview }>('/api/budget').then(
        (response) => response.data,
    );
}

export function updateBudget(
    amount: number,
    description = 'Balance correction',
): Promise<{ message: string }> {
    return apiRequest('/api/budget', {
        method: 'PUT',
        body: { amount, description },
    });
}

export function createBudgetTransaction(payload: {
    type: Extract<BudgetTransactionType, 'INCOME' | 'EXPENSE'>;
    amount: number;
    description?: string | null;
    occurred_at?: string | null;
}): Promise<{ message: string }> {
    return apiRequest('/api/budget/transactions', {
        method: 'POST',
        body: payload,
    });
}
