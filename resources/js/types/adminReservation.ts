import type { PaginatedResponse } from '@/types/event';

export type AdminReservationStatus =
    | 'confirmed'
    | 'cancelled';

export interface AdminReservationUser {
    id: number;
    name: string;
    email: string;
}

export interface AdminReservation {
    id: number;
    event_id: number;
    user_id: number;
    status: AdminReservationStatus;
    cancelled_at: string | null;
    created_at: string;
    updated_at: string;
    user: AdminReservationUser;
}

export interface AdminReservationEvent {
    id: number;
    title: string;
    capacity: number;
}

export interface AdminReservationSummary {
    confirmed: number;
    cancelled: number;
    available_places: number;
}

export interface AdminEventReservationsResponse {
    event: AdminReservationEvent;
    summary: AdminReservationSummary;
    reservations: PaginatedResponse<AdminReservation>;
}
