export interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface CategoryPayload {
    name: string;
    slug: string;
    description: string | null;
    is_active?: boolean;
}
