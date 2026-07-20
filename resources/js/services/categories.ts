import api from '@/services/api';
import type { EventCategory } from '@/types/event';

type CategoriesResponse =
    | EventCategory[]
    | {
        data: EventCategory[];
    };

export async function getCategories(): Promise<EventCategory[]> {
    const response = await api.get<CategoriesResponse>(
        '/categories',
    );

    return Array.isArray(response.data)
        ? response.data
        : response.data.data;
}
