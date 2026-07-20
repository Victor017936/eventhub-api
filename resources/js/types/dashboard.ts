export interface DashboardTopEvent {
    id: number;
    title: string;
    capacity: number;
    confirmed_reservations: number;
    occupancy_rate: number;
}

export interface AdminDashboardData {
    users: {
        total: number;
    };
    categories: {
        total: number;
        active: number;
    };
    events: {
        total: number;
        published: number;
        upcoming: number;
    };
    reservations: {
        total: number;
        confirmed: number;
        cancelled: number;
    };
    top_events: DashboardTopEvent[];
}
