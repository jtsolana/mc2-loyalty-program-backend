export interface CustomerRow {
    id: number;
    name: string;
    username: string | null;
    email: string | null;
    phone: string | null;
    avatar: string | null;
    purchases_count: number;
    total_points: number;
    lifetime_points: number;
    created_at: string;
    hashed_id: string;
}

export interface Purchase {
    id: number;
    loyverse_receipt_id: string;
    total_amount: string;
    points_earned: number;
    status: string;
    created_at: string;
}

export interface PointTransaction {
    id: number;
    type: string;
    points: number;
    balance_after: number;
    description: string;
    created_at: string;
}

export interface UserRow {
    id: number;
    hashed_id: string;
    name: string;
    username: string | null;
    email: string | null;
    phone: string | null;
    roles: string[];
    created_at: string;
}

export interface Role {
    id: number;
    name: string;
    display_name: string;
}

export type PointRuleType = 'spend_based' | 'per_item';

export interface RuleTypeOption {
    value: PointRuleType;
    label: string;
}

export interface PointRule {
    id: number;
    hashed_id: string;
    name: string;
    type: PointRuleType;
    spend_amount: string | null;
    minimum_spend: string | null;
    points_per_unit: number | null;
    points_per_item: number | null;
    is_active: boolean;
    created_at: string;
}

export interface RewardRule {
    id: number;
    hashed_id: string;
    name: string;
    reward_title: string;
    points_required: number;
    expires_in_days: number;
    is_active: boolean;
    created_at: string;
}

export interface Promotion {
    id: number;
    hashed_id: string;
    title: string;
    excerpt: string;
    thumbnail_url: string | null;
    content: string;
    type: 'promotion' | 'announcement';
    is_published: boolean;
    published_at: string | null;
    created_at: string;
}

export interface DashboardStats {
    total_customers: number;
    total_purchases: number;
    total_points_issued: number;
    total_redemptions: number;
}

export interface MonthlyPurchase {
    month: string;
    count: number;
    revenue: string;
    points: number;
}

export interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface CompanyProfile {
    name: string | null;
    logo_url: string | null;
    address: string | null;
    contact_number: string | null;
    email: string | null;
}
