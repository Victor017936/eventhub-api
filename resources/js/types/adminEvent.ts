import type { Category } from '@/types/category';

export type AdminEventStatus =
    | 'draft'
    | 'published'
    | 'cancelled'
    | 'completed';

export interface AdminEventCreator {
    id: number;
    name: string;
    email: string;
}

export interface AdminEvent {
    id: number;
    category_id: number;
    created_by: number;
    title: string;
    slug: string;
    description: string;
    location: string | null;
    starts_at: string;
    ends_at: string;
    booking_starts_at: string | null;
    booking_ends_at: string | null;
    capacity: number;
    status: AdminEventStatus;
    created_at: string;
    updated_at: string;
    category: Category;
    creator: AdminEventCreator;
}

export interface AdminEventFilters {
    search?: string;
    category_id?: number;
    status?: AdminEventStatus;
    date_from?: string;
    date_to?: string;
}
