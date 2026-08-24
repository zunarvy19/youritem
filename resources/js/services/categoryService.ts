import { apiRequest } from '@/services/apiClient';
import type { Category } from '@/types';

export function fetchCategories(includeInactive = false): Promise<Category[]> {
    return apiRequest<{ data: Category[] }>('/api/categories', {
        query: { include_inactive: includeInactive || undefined },
    }).then((response) => response.data);
}
