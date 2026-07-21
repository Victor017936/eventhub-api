export interface EventCategory {
    id: number;
    name: string;
    slug: string;
}

export interface EventItem {
    id: number;
    category_id: number;
    title: string;
    slug: string;
    description: string | null;
    location: string | null;
    starts_at: string;
    ends_at: string;
    capacity: number;
    status: string;
    category: EventCategory;
}

export interface PaginatedResponse<T> {
    current_page: number;
    data: T[];
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}
