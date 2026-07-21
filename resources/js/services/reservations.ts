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

export async function getMyReservationForEvent(
    eventId: number,
): Promise<ReservationItem | null> {
    let page = 1;

    while (true) {
        const response = await getMyReservations(page);

        const reservation = response.data.find(
            (item) => item.event_id === eventId,
        );

        if (reservation) {
            return reservation;
        }

        if (response.current_page >= response.last_page) {
            return null;
        }

        page += 1;
    }
}

export async function cancelReservation(
    reservationId: number,
): Promise<void> {
    await api.delete(`/reservations/${reservationId}`);
}
