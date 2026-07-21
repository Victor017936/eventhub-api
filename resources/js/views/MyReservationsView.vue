<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getApiErrorMessage } from '@/services/errors';
import {
    cancelReservation,
    getMyReservations,
} from '@/services/reservations';
import type { ReservationItem } from '@/types/reservation';

const reservations = ref<ReservationItem[]>([]);
const isLoading = ref(true);
const errorMessage = ref('');
const successMessage = ref('');
const cancellingReservationId = ref<number | null>(null);

const currentPage = ref(1);
const lastPage = ref(1);
const totalReservations = ref(0);

function formatDate(date: string): string {
    return new Intl.DateTimeFormat('ro-RO', {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(new Date(date));
}

function statusLabel(status: string): string {
    return status === 'confirmed'
        ? 'Confirmată'
        : 'Anulată';
}

async function loadReservations(page = 1): Promise<void> {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await getMyReservations(page);

        reservations.value = response.data;
        currentPage.value = response.current_page;
        lastPage.value = response.last_page;
        totalReservations.value = response.total;
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Rezervările nu au putut fi încărcate.',
        );
    } finally {
        isLoading.value = false;
    }
}

async function handleCancellation(
    reservation: ReservationItem,
): Promise<void> {
    const confirmed = window.confirm(
        `Sigur vrei să anulezi rezervarea pentru „${reservation.event.title}”?`,
    );

    if (! confirmed) {
        return;
    }

    errorMessage.value = '';
    successMessage.value = '';
    cancellingReservationId.value = reservation.id;

    try {
        await cancelReservation(reservation.id);

        successMessage.value =
            'Rezervarea a fost anulată cu succes.';

        await loadReservations(currentPage.value);
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Rezervarea nu a putut fi anulată.',
        );
    } finally {
        cancellingReservationId.value = null;
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

    successMessage.value = '';

    await loadReservations(page);
}

onMounted(() => {
    void loadReservations();
});
</script>

<template>
    <main class="reservations-page">
        <div class="container">
            <div class="page-heading">
                <p class="eyebrow">
                    Contul meu
                </p>

                <h1 class="page-title">
                    Rezervările mele
                </h1>

                <p class="page-description">
                    Vezi toate evenimentele la care ai rezervat un loc.
                </p>
            </div>

            <p
                v-if="successMessage"
                class="success-message reservations-message"
            >
                {{ successMessage }}
            </p>

            <p
                v-if="errorMessage"
                class="form-error reservations-message"
            >
                {{ errorMessage }}
            </p>

            <p
                v-if="isLoading"
                class="state-message"
            >
                Se încarcă rezervările...
            </p>

            <div
                v-else-if="reservations.length > 0"
                class="reservations-content"
            >
                <p class="events-count">
                    {{ totalReservations }}
                    {{
                        totalReservations === 1
                            ? 'rezervare'
                            : 'rezervări'
                    }}
                </p>

                <div class="reservations-list">
                    <article
                        v-for="reservation in reservations"
                        :key="reservation.id"
                        class="reservation-card"
                    >
                        <div class="reservation-card-content">
                            <div>
                                <span
                                    class="reservation-status"
                                    :class="{
                                        'reservation-status-cancelled':
                                            reservation.status === 'cancelled',
                                    }"
                                >
                                    {{ statusLabel(reservation.status) }}
                                </span>

                                <h2 class="reservation-title">
                                    {{ reservation.event.title }}
                                </h2>

                                <p class="event-date">
                                    {{
                                        formatDate(
                                            reservation.event.starts_at,
                                        )
                                    }}
                                </p>

                                <p class="event-location">
                                    {{
                                        reservation.event.location
                                            ?? 'Locație neanunțată'
                                    }}
                                </p>
                            </div>

                            <div class="reservation-actions">
                                <RouterLink
                                    class="reservation-details-link"
                                    :to="`/events/${reservation.event.id}`"
                                >
                                    Vezi evenimentul
                                </RouterLink>

                                <button
                                    v-if="reservation.status === 'confirmed'"
                                    class="cancel-reservation-button"
                                    type="button"
                                    :disabled="
                                        cancellingReservationId
                                            === reservation.id
                                    "
                                    @click="
                                        handleCancellation(reservation)
                                    "
                                >
                                    {{
                                        cancellingReservationId
                                            === reservation.id
                                            ? 'Se anulează...'
                                            : 'Anulează rezervarea'
                                    }}
                                </button>

                                <span
                                    v-else
                                    class="cancelled-reservation-message"
                                >
                                    Rezervare anulată
                                </span>
                            </div>
                        </div>
                    </article>
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
                        Pagina {{ currentPage }} din {{ lastPage }}
                    </span>

                    <button
                        type="button"
                        :disabled="currentPage === lastPage"
                        @click="changePage(currentPage + 1)"
                    >
                        Următor
                    </button>
                </div>
            </div>

            <div
                v-else
                class="empty-state"
            >
                <h2>
                    Nu ai nicio rezervare
                </h2>

                <RouterLink
                    class="primary-button empty-state-link"
                    to="/events"
                >
                    Vezi evenimentele
                </RouterLink>
            </div>
        </div>
    </main>
</template>
