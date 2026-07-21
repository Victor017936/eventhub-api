import api from '@/services/api';
import type {
    AdminEventReservationsResponse,
} from '@/types/adminReservation';

export async function getAdminEventReservations(
    eventId: number,
    page = 1,
): Promise<AdminEventReservationsResponse> {
    const response =
        await api.get<AdminEventReservationsResponse>(
            `/admin/events/${eventId}/reservations`,
            {
                params: {
                    page,
                },
            },
        );

    return response.data;
}
