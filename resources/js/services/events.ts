import api from '@/services/api';
import type {
    EventItem,
    PaginatedResponse,
} from '@/types/event';

interface EventResponse {
    data: EventItem;
}

export interface EventFilters {
    search?: string;
    category_id?: number;
    location?: string;
    date_from?: string;
    date_to?: string;
    per_page?: number;
}

export async function getEvents(
    page = 1,
    filters: EventFilters = {},
): Promise<PaginatedResponse<EventItem>> {
    const response = await api.get<PaginatedResponse<EventItem>>(
        '/events',
        {
            params: {
                page,
                ...filters,
            },
        },
    );

    return response.data;
}

export async function getEvent(
    eventId: number,
): Promise<EventItem> {
    const response = await api.get<EventResponse>(
        `/events/${eventId}`,
    );

    return response.data.data;
}
