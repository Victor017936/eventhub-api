<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getApiErrorMessage } from '@/services/errors';
import { getEvent } from '@/services/events';
import {
    createReservation,
    getMyReservationForEvent,
} from '@/services/reservations';
import { useAuthStore } from '@/stores/auth';
import type { EventItem } from '@/types/event';
import type { ReservationItem } from '@/types/reservation';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const event = ref<EventItem | null>(null);
const currentReservation = ref<ReservationItem | null>(null);

const isLoading = ref(true);
const isReserving = ref(false);

const errorMessage = ref('');
const reservationErrorMessage = ref('');
const successMessage = ref('');

const reservationButtonLabel = computed(() => {
    if (isReserving.value) {
        return 'Se procesează...';
    }

    if (currentReservation.value?.status === 'confirmed') {
        return 'Rezervare confirmată';
    }

    if (currentReservation.value?.status === 'cancelled') {
        return 'Rezervă din nou';
    }

    if (! authStore.isAuthenticated) {
        return 'Autentifică-te pentru rezervare';
    }

    return 'Rezervă un loc';
});

const reservationButtonDisabled = computed(() => {
    return (
        isReserving.value
        || currentReservation.value?.status === 'confirmed'
    );
});

function formatDate(date: string): string {
    return new Intl.DateTimeFormat('ro-RO', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(date));
}

async function loadReservationState(
    eventId: number,
): Promise<void> {
    currentReservation.value = null;
    reservationErrorMessage.value = '';

    if (! authStore.isAuthenticated) {
        return;
    }

    try {
        currentReservation.value =
            await getMyReservationForEvent(eventId);
    } catch (exception: unknown) {
        reservationErrorMessage.value = getApiErrorMessage(
            exception,
            'Starea rezervării nu a putut fi verificată.',
        );
    }
}

async function loadEvent(): Promise<void> {
    isLoading.value = true;
    errorMessage.value = '';
    reservationErrorMessage.value = '';
    successMessage.value = '';
    event.value = null;

    const eventId = Number(route.params.id);

    if (! Number.isInteger(eventId) || eventId < 1) {
        errorMessage.value = 'ID-ul evenimentului nu este valid.';
        isLoading.value = false;

        return;
    }

    try {
        event.value = await getEvent(eventId);

        await loadReservationState(eventId);
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Evenimentul nu a putut fi încărcat.',
        );
    } finally {
        isLoading.value = false;
    }
}

async function reservePlace(): Promise<void> {
    reservationErrorMessage.value = '';
    successMessage.value = '';

    if (! authStore.isAuthenticated) {
        await router.push({
            name: 'login',
            query: {
                redirect: route.fullPath,
            },
        });

        return;
    }

    if (
        ! event.value
        || currentReservation.value?.status === 'confirmed'
    ) {
        return;
    }

    const wasCancelled =
        currentReservation.value?.status === 'cancelled';

    isReserving.value = true;

    try {
        await createReservation(event.value.id);

        currentReservation.value =
            await getMyReservationForEvent(event.value.id);

        successMessage.value = wasCancelled
            ? 'Rezervarea a fost reactivată cu succes.'
            : 'Rezervarea a fost creată cu succes.';
    } catch (exception: unknown) {
        reservationErrorMessage.value = getApiErrorMessage(
            exception,
            'Rezervarea nu a putut fi creată.',
        );
    } finally {
        isReserving.value = false;
    }
}

watch(
    () => route.params.id,
    () => {
        void loadEvent();
    },
    {
        immediate: true,
    },
);
</script>

<template>
    <main class="event-details-page">
        <div class="container">
            <p
                v-if="isLoading"
                class="state-message"
            >
                Se încarcă evenimentul...
            </p>

            <div
                v-else-if="errorMessage"
                class="form-error"
            >
                {{ errorMessage }}
            </div>

            <article
                v-else-if="event"
                class="event-details-card"
            >
                <RouterLink
                    class="back-link"
                    to="/events"
                >
                    ← Înapoi la evenimente
                </RouterLink>

                <div class="event-details-header">
                    <span class="category-badge">
                        {{ event.category?.name ?? 'Fără categorie' }}
                    </span>

                    <span class="event-capacity">
                        {{ event.capacity }} locuri
                    </span>
                </div>

                <h1 class="event-details-title">
                    {{ event.title }}
                </h1>

                <div class="event-information">
                    <div>
                        <span class="information-label">
                            Început
                        </span>

                        <strong>
                            {{ formatDate(event.starts_at) }}
                        </strong>
                    </div>

                    <div>
                        <span class="information-label">
                            Sfârșit
                        </span>

                        <strong>
                            {{ formatDate(event.ends_at) }}
                        </strong>
                    </div>

                    <div>
                        <span class="information-label">
                            Locație
                        </span>

                        <strong>
                            {{ event.location ?? 'Locație neanunțată' }}
                        </strong>
                    </div>
                </div>

                <section class="event-details-description">
                    <h2>
                        Despre eveniment
                    </h2>

                    <p>
                        {{
                            event.description
                                ?? 'Descrierea evenimentului nu este disponibilă.'
                        }}
                    </p>
                </section>

                <p
                    v-if="
                        ! successMessage
                        && currentReservation?.status === 'confirmed'
                    "
                    class="success-message"
                >
                    Ai deja o rezervare confirmată pentru acest eveniment.
                </p>

                <p
                    v-else-if="
                        ! successMessage
                        && currentReservation?.status === 'cancelled'
                    "
                    class="info-message"
                >
                    Rezervarea anterioară este anulată. Poți rezerva din nou.
                </p>

                <p
                    v-if="successMessage"
                    class="success-message"
                >
                    {{ successMessage }}
                </p>

                <p
                    v-if="reservationErrorMessage"
                    class="form-error reservation-message"
                >
                    {{ reservationErrorMessage }}
                </p>

                <button
                    class="reservation-button"
                    type="button"
                    :disabled="reservationButtonDisabled"
                    @click="reservePlace"
                >
                    {{ reservationButtonLabel }}
                </button>
            </article>

            <div
                v-else
                class="empty-state"
            >
                Evenimentul nu a fost găsit.
            </div>
        </div>
    </main>
</template>
