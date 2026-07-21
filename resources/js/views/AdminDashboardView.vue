<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { getAdminDashboard } from '@/services/adminDashboard';
import { getApiErrorMessage } from '@/services/errors';
import { useAuthStore } from '@/stores/auth';
import type { AdminDashboardData } from '@/types/dashboard';

const router = useRouter();
const authStore = useAuthStore();

const dashboard = ref<AdminDashboardData | null>(null);
const isLoading = ref(true);
const errorMessage = ref('');

function occupancyWidth(value: number): string {
    const safeValue = Math.min(Math.max(value, 0), 100);

    return `${safeValue}%`;
}

async function loadDashboard(): Promise<void> {
    if (! authStore.isAdmin) {
        await router.replace({
            name: 'home',
        });

        return;
    }

    isLoading.value = true;
    errorMessage.value = '';

    try {
        dashboard.value = await getAdminDashboard();
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Dashboardul nu a putut fi încărcat.',
        );
    } finally {
        isLoading.value = false;
    }
}

onMounted(() => {
    void loadDashboard();
});
</script>

<template>
    <main class="admin-page">
        <div class="container">
            <div class="admin-heading">
                <div>
                    <p class="eyebrow">
                        Administrare
                    </p>

                    <h1 class="page-title">
                        Dashboard
                    </h1>

                    <p class="page-description">
                        Vezi situația generală a platformei EventHub.
                    </p>
                </div>

                <div class="admin-heading-actions">
                    <RouterLink
                        class="primary-button"
                        to="/admin/events"
                    >
                        Administrează evenimentele
                    </RouterLink>

                    <RouterLink
                        class="secondary-button"
                        to="/admin/categories"
                    >
                        Administrează categoriile
                    </RouterLink>
                </div>
            </div>

            <p
                v-if="isLoading"
                class="state-message"
            >
                Se încarcă dashboardul...
            </p>

            <p
                v-else-if="errorMessage"
                class="form-error"
            >
                {{ errorMessage }}
            </p>

            <div
                v-else-if="dashboard"
                class="admin-dashboard"
            >
                <section class="dashboard-statistics">
                    <article class="dashboard-card">
                        <span class="dashboard-card-label">
                            Utilizatori
                        </span>

                        <strong class="dashboard-card-value">
                            {{ dashboard.users.total }}
                        </strong>

                        <span class="dashboard-card-description">
                            Conturi înregistrate
                        </span>
                    </article>

                    <article class="dashboard-card">
                        <span class="dashboard-card-label">
                            Categorii
                        </span>

                        <strong class="dashboard-card-value">
                            {{ dashboard.categories.total }}
                        </strong>

                        <span class="dashboard-card-description">
                            {{ dashboard.categories.active }}
                            categorii active
                        </span>
                    </article>

                    <article class="dashboard-card">
                        <span class="dashboard-card-label">
                            Evenimente
                        </span>

                        <strong class="dashboard-card-value">
                            {{ dashboard.events.total }}
                        </strong>

                        <span class="dashboard-card-description">
                            {{ dashboard.events.published }} publicate ·
                            {{ dashboard.events.upcoming }} viitoare
                        </span>
                    </article>

                    <article class="dashboard-card">
                        <span class="dashboard-card-label">
                            Rezervări
                        </span>

                        <strong class="dashboard-card-value">
                            {{ dashboard.reservations.total }}
                        </strong>

                        <span class="dashboard-card-description">
                            {{ dashboard.reservations.confirmed }} confirmate ·
                            {{ dashboard.reservations.cancelled }} anulate
                        </span>
                    </article>
                </section>

                <section class="dashboard-section">
                    <div class="dashboard-section-heading">
                        <div>
                            <p class="eyebrow">
                                Performanță
                            </p>

                            <h2>
                                Evenimente populare
                            </h2>
                        </div>

                        <RouterLink
                            class="dashboard-section-link"
                            to="/events"
                        >
                            Vezi evenimentele
                        </RouterLink>
                    </div>

                    <div
                        v-if="dashboard.top_events.length > 0"
                        class="dashboard-table-wrapper"
                    >
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Eveniment</th>
                                    <th>Capacitate</th>
                                    <th>Rezervări confirmate</th>
                                    <th>Ocupare</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="event in dashboard.top_events"
                                    :key="event.id"
                                >
                                    <td>
                                        <RouterLink
                                            class="dashboard-event-link"
                                            :to="`/events/${event.id}`"
                                        >
                                            {{ event.title }}
                                        </RouterLink>
                                    </td>

                                    <td>
                                        {{ event.capacity }}
                                    </td>

                                    <td>
                                        {{ event.confirmed_reservations }}
                                    </td>

                                    <td>
                                        <div class="occupancy-information">
                                            <div class="occupancy-bar">
                                                <span
                                                    :style="{
                                                        width: occupancyWidth(
                                                            event.occupancy_rate,
                                                        ),
                                                    }"
                                                />
                                            </div>

                                            <strong>
                                                {{ event.occupancy_rate }}%
                                            </strong>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-else
                        class="empty-state"
                    >
                        Nu există încă evenimente cu rezervări.
                    </div>
                </section>
            </div>
        </div>
    </main>
</template>


