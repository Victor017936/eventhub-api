import api from '@/services/api';
import type { PaginatedResponse } from '@/types/event';
import type {
    AdminEvent,
    AdminEventFilters,
} from '@/types/adminEvent';

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

export async function cancelAdminEvent(
    eventId: number,
): Promise<void> {
    await api.delete(`/events/${eventId}`);
}
