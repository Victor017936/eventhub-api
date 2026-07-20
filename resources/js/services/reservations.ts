import api from '@/services/api';
import type { PaginatedResponse } from '@/types/event';
import type { ReservationItem } from '@/types/reservation';

export async function createReservation(
    eventId: number,
): Promise<void> {
    await api.post(`/events/${eventId}/reservations`);
}

export async function getMyReservations(
    page = 1,
): Promise<PaginatedResponse<ReservationItem>> {
    const response = await api.get<
        PaginatedResponse<ReservationItem>
    >('/my-reservations', {
        params: {
            page,
        },
    });

    return response.data;
}

export async function cancelReservation(
    reservationId: number,
): Promise<void> {
    await api.delete(`/reservations/${reservationId}`);
}
