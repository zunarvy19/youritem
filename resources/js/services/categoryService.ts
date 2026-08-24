import { destroy, index, store, update } from '@/routes/api/categories';
import { apiRequest } from '@/services/apiClient';
import type { Category } from '@/types';

export function fetchCategories(includeInactive = false): Promise<Category[]> {
    return apiRequest<{ data: Category[] }>(index.url(), {
        query: { include_inactive: includeInactive || undefined },
    }).then((response) => response.data);
}

export function createCategory(input: {
    name: string;
    is_active: boolean;
}): Promise<Category> {
    return apiRequest<{ data: Category }>(store.url(), {
        method: 'POST',
        body: input,
    }).then((response) => response.data);
}

export function updateCategory(
    category: Category,
    input: { name?: string; is_active?: boolean },
): Promise<Category> {
    return apiRequest<{ data: Category }>(update.url(category.id), {
        method: 'PUT',
        body: input,
    }).then((response) => response.data);
}

export function deleteCategory(category: Category): Promise<void> {
    return apiRequest<void>(destroy.url(category.id), { method: 'DELETE' });
}
