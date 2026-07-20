import api from '@/services/api';
import type {
    Category,
    CategoryPayload,
} from '@/types/category';

interface CategoriesResponse {
    data: Category[];
}

interface CategoryResponse {
    data: Category;
}

export async function getCategories(): Promise<Category[]> {
    const response = await api.get<CategoriesResponse>(
        '/categories',
    );

    return response.data.data;
}

export async function createCategory(
    payload: CategoryPayload,
): Promise<Category> {
    const response = await api.post<CategoryResponse>(
        '/categories',
        payload,
    );

    return response.data.data;
}

export async function updateCategory(
    categoryId: number,
    payload: CategoryPayload,
): Promise<Category> {
    const response = await api.put<CategoryResponse>(
        `/categories/${categoryId}`,
        payload,
    );

    return response.data.data;
}

export async function deactivateCategory(
    categoryId: number,
): Promise<void> {
    await api.delete(`/categories/${categoryId}`);
}
