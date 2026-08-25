export type Priority = 'HIGH' | 'MEDIUM' | 'LOW';
export type Purpose = 'NEED' | 'WANT';
export type WishlistStatus = 'ACTIVE' | 'PURCHASED' | 'ARCHIVED';

export type Category = {
    id: number;
    name: string;
    is_active: boolean;
};

export type WishlistItem = {
    id: number;
    name: string;
    category: {
        id: number;
        name: string;
    };
    priority: Priority;
    purpose: Purpose;
    estimated_price: number;
    notes: string | null;
    product_url: string | null;
    preview: {
        title: string | null;
        description: string | null;
        image_url: string | null;
        site_name: string | null;
        fetched_at: string | null;
    } | null;
    status: WishlistStatus;
    created_at: string | null;
    updated_at: string | null;
};

export type BudgetTransactionType =
    'INCOME' | 'EXPENSE' | 'ADJUSTMENT' | 'OPENING_BALANCE';
export type BudgetTransaction = {
    id: number;
    type: BudgetTransactionType;
    amount: number;
    description: string | null;
    occurred_at: string;
    purchase: { id: number; wishlist_item_name: string | null } | null;
};
export type BudgetOverview = {
    amount: number;
    month: string;
    income: number;
    expense: number;
    transactions: BudgetTransaction[];
};

export type AuthUser = {
    id: number;
    name: string;
    email: string;
};

export type RecommendationItem = {
    id: number;
    name: string;
    category: { id: number; name: string };
    priority: Priority;
    purpose: Purpose;
    estimated_price: number;
    reasons?: string[];
};

export type UnaffordableItem = {
    id: number;
    name: string;
    category: { id: number; name: string };
    priority: Priority;
    purpose: Purpose;
    estimated_price: number;
    amount_needed: number;
};
export type RecommendationResult = {
    available_budget: number;
    priority_first: {
        items: RecommendationItem[];
        total: number;
        remaining_budget: number;
    };
    budget_optimization: {
        items: RecommendationItem[];
        total: number;
        remaining_budget: number;
        score: number;
        utilization: number;
    };
    unaffordable: UnaffordableItem[];
};

export type Purchase = {
    id: number;
    wishlist_item: {
        id: number;
        name: string | null;
        category: { id: number | null; name: string | null };
        priority: Priority | null;
        purpose: Purpose | null;
        estimated_price: number | null;
    };
    actual_price: number;
    purchased_at: string;
};
