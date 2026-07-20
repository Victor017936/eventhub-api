import type { EventItem } from '@/types/event';

export type ReservationStatus = 'confirmed' | 'cancelled';

export interface ReservationItem {
    id: number;
    event_id: number;
    user_id: number;
    status: ReservationStatus;
    cancelled_at: string | null;
    created_at: string;
    updated_at: string;
    event: EventItem;
}
