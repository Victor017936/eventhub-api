import api from '@/services/api';
import type {
    EventItem,
    PaginatedResponse,
} from '@/types/event';

interface EventResponse {
    data: EventItem;
}

export async function getEvents(
    page = 1,
): Promise<PaginatedResponse<EventItem>> {
    const response = await api.get<PaginatedResponse<EventItem>>(
        '/events',
        {
            params: {
                page,
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
