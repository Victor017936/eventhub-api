import api from '@/services/api';
import type { PaginatedResponse } from '@/types/event';
import type {
    AdminEvent,
    AdminEventFilters,
    AdminEventPayload,
} from '@/types/adminEvent';

interface AdminEventResponse {
    data: AdminEvent;
}

export async function getAdminEvents(
    page = 1,
    filters: AdminEventFilters = {},
): Promise<PaginatedResponse<AdminEvent>> {
    const response = await api.get<
        PaginatedResponse<AdminEvent>
    >('/admin/events', {
        params: {
            page,
            ...filters,
        },
    });

    return response.data;
}

export async function getAdminEvent(
    eventId: number,
): Promise<AdminEvent> {
    const response = await api.get<AdminEventResponse>(
        `/admin/events/${eventId}`,
    );

    return response.data.data;
}

export async function createAdminEvent(
    payload: AdminEventPayload,
): Promise<AdminEvent> {
    const response = await api.post<AdminEventResponse>(
        '/events',
        payload,
    );

    return response.data.data;
}

export async function updateAdminEvent(
    eventId: number,
    payload: AdminEventPayload,
): Promise<AdminEvent> {
    const response = await api.put<AdminEventResponse>(
        `/events/${eventId}`,
        payload,
    );

    return response.data.data;
}

export async function cancelAdminEvent(
    eventId: number,
): Promise<void> {
    await api.delete(`/events/${eventId}`);
}
