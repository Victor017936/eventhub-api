<script setup lang="ts">
import {
    ref,
    watch,
} from 'vue';
import {
    useRoute,
    useRouter,
} from 'vue-router';
import { getAdminEventReservations } from '@/services/adminReservations';
import { getApiErrorMessage } from '@/services/errors';
import { useAuthStore } from '@/stores/auth';
import type {
    AdminReservation,
    AdminReservationEvent,
    AdminReservationSummary,
    AdminReservationStatus,
} from '@/types/adminReservation';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const event = ref<AdminReservationEvent | null>(null);

const summary = ref<AdminReservationSummary>({
    confirmed: 0,
    cancelled: 0,
    available_places: 0,
});

const reservations = ref<AdminReservation[]>([]);

const isLoading = ref(true);
const errorMessage = ref('');

const currentPage = ref(1);
const lastPage = ref(1);
const totalReservations = ref(0);

function getEventId(): number {
    return Number(route.params.id);
}

function getPageFromUrl(): number {
    const page = Number(route.query.page ?? 1);

    return Number.isInteger(page) && page > 0
        ? page
        : 1;
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('ro-RO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function statusLabel(
    status: AdminReservationStatus,
): string {
    return status === 'confirmed'
        ? 'Confirmată'
        : 'Anulată';
}

async function loadReservations(): Promise<void> {
    if (! authStore.isAdmin) {
        await router.replace({
            name: 'home',
        });

        return;
    }

    const eventId = getEventId();

    if (! Number.isInteger(eventId) || eventId < 1) {
        errorMessage.value =
            'ID-ul evenimentului nu este valid.';
        isLoading.value = false;

        return;
    }

    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response =
            await getAdminEventReservations(
                eventId,
                getPageFromUrl(),
            );

        event.value = response.event;
        summary.value = response.summary;
        reservations.value =
            response.reservations.data;

        currentPage.value =
            response.reservations.current_page;

        lastPage.value =
            response.reservations.last_page;

        totalReservations.value =
            response.reservations.total;
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Participanții nu au putut fi încărcați.',
        );
    } finally {
        isLoading.value = false;
    }
}

async function changePage(page: number): Promise<void> {
    if (
        page < 1
        || page > lastPage.value
        || page === currentPage.value
    ) {
        return;
    }

    await router.push({
        name: 'admin-event-reservations',
        params: {
            id: getEventId(),
        },
        query: page > 1
            ? {
                page: String(page),
            }
            : {},
    });

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
}

watch(
    () => [
        route.params.id,
        route.query.page,
    ],
    () => {
        void loadReservations();
    },
    {
        immediate: true,
    },
);
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
                        Participanți
                    </h1>

                    <p class="page-description">
                        {{
                            event
                                ? event.title
                                : 'Rezervările evenimentului'
                        }}
                    </p>
                </div>

                <RouterLink
                    class="admin-back-link"
                    to="/admin/events"
                >
                    ← Înapoi la evenimente
                </RouterLink>
            </div>

            <p
                v-if="isLoading"
                class="state-message"
            >
                Se încarcă participanții...
            </p>

            <p
                v-else-if="errorMessage"
                class="form-error"
            >
                {{ errorMessage }}
            </p>

            <div
                v-else-if="event"
                class="admin-reservations-content"
            >
                <section class="reservation-summary-grid">
                    <article class="dashboard-card">
                        <span class="dashboard-card-label">
                            Confirmate
                        </span>

                        <strong class="dashboard-card-value">
                            {{ summary.confirmed }}
                        </strong>

                        <span class="dashboard-card-description">
                            Rezervări active
                        </span>
                    </article>

                    <article class="dashboard-card">
                        <span class="dashboard-card-label">
                            Anulate
                        </span>

                        <strong class="dashboard-card-value">
                            {{ summary.cancelled }}
                        </strong>

                        <span class="dashboard-card-description">
                            Rezervări anulate
                        </span>
                    </article>

                    <article class="dashboard-card">
                        <span class="dashboard-card-label">
                            Locuri disponibile
                        </span>

                        <strong class="dashboard-card-value">
                            {{ summary.available_places }}
                        </strong>

                        <span class="dashboard-card-description">
                            din {{ event.capacity }} locuri
                        </span>
                    </article>
                </section>

                <section class="admin-list-section">
                    <div class="admin-section-title">
                        <h2>
                            Lista participanților
                        </h2>

                        <span>
                            {{ totalReservations }}
                            {{
                                totalReservations === 1
                                    ? 'rezervare'
                                    : 'rezervări'
                            }}
                        </span>
                    </div>

                    <div
                        v-if="reservations.length > 0"
                        class="dashboard-table-wrapper"
                    >
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Participant</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Data rezervării</th>
                                    <th>Data anulării</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="reservation in reservations"
                                    :key="reservation.id"
                                >
                                    <td>
                                        <strong>
                                            {{ reservation.user.name }}
                                        </strong>
                                    </td>

                                    <td>
                                        <a
                                            class="participant-email"
                                            :href="`mailto:${reservation.user.email}`"
                                        >
                                            {{ reservation.user.email }}
                                        </a>
                                    </td>

                                    <td>
                                        <span
                                            class="reservation-status"
                                            :class="{
                                                'reservation-status-cancelled':
                                                    reservation.status
                                                    === 'cancelled',
                                            }"
                                        >
                                            {{
                                                statusLabel(
                                                    reservation.status,
                                                )
                                            }}
                                        </span>
                                    </td>

                                    <td>
                                        {{
                                            formatDate(
                                                reservation.created_at,
                                            )
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            reservation.cancelled_at
                                                ? formatDate(
                                                    reservation.cancelled_at,
                                                )
                                                : '—'
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-else
                        class="empty-state"
                    >
                        <h2>
                            Nu există participanți
                        </h2>

                        <p>
                            Evenimentul nu are încă nicio rezervare.
                        </p>
                    </div>

                    <div
                        v-if="lastPage > 1"
                        class="pagination"
                    >
                        <button
                            type="button"
                            :disabled="currentPage === 1"
                            @click="changePage(currentPage - 1)"
                        >
                            Anterior
                        </button>

                        <span>
                            Pagina {{ currentPage }}
                            din {{ lastPage }}
                        </span>

                        <button
                            type="button"
                            :disabled="
                                currentPage === lastPage
                            "
                            @click="changePage(currentPage + 1)"
                        >
                            Următor
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </main>
</template>
