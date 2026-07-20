<script setup lang="ts">
import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getApiErrorMessage } from '@/services/errors';
import { getEvent } from '@/services/events';
import { createReservation } from '@/services/reservations';
import { useAuthStore } from '@/stores/auth';
import type { EventItem } from '@/types/event';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const event = ref<EventItem | null>(null);
const isLoading = ref(true);
const isReserving = ref(false);

const errorMessage = ref('');
const reservationErrorMessage = ref('');
const successMessage = ref('');

function formatDate(date: string): string {
    return new Intl.DateTimeFormat('ro-RO', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(date));
}

async function loadEvent(): Promise<void> {
    isLoading.value = true;
    errorMessage.value = '';
    event.value = null;

    const eventId = Number(route.params.id);

    if (! Number.isInteger(eventId) || eventId < 1) {
        errorMessage.value = 'ID-ul evenimentului nu este valid.';
        isLoading.value = false;

        return;
    }

    try {
        event.value = await getEvent(eventId);
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

    if (! event.value) {
        return;
    }

    isReserving.value = true;

    try {
        await createReservation(event.value.id);

        successMessage.value =
            'Rezervarea a fost creată cu succes.';
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
                    :disabled="isReserving || Boolean(successMessage)"
                    @click="reservePlace"
                >
                    <template v-if="successMessage">
                        Rezervare confirmată
                    </template>

                    <template v-else-if="isReserving">
                        Se procesează...
                    </template>

                    <template v-else>
                        Rezervă un loc
                    </template>
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
