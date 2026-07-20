import api from '@/services/api';
import type { AdminDashboardData } from '@/types/dashboard';

interface AdminDashboardResponse {
    data: AdminDashboardData;
}

export async function getAdminDashboard(): Promise<AdminDashboardData> {
    const response = await api.get<AdminDashboardResponse>(
        '/admin/dashboard',
    );

    return response.data.data;
}
