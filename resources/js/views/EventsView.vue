<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getApiErrorMessage } from '@/services/errors';
import { getEvents } from '@/services/events';
import type { EventItem } from '@/types/event';

const events = ref<EventItem[]>([]);
const isLoading = ref(true);
const errorMessage = ref('');

const currentPage = ref(1);
const lastPage = ref(1);
const totalEvents = ref(0);

function formatDate(date: string): string {
    return new Intl.DateTimeFormat('ro-RO', {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(new Date(date));
}

async function loadEvents(page = 1): Promise<void> {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await getEvents(page);

        events.value = response.data;
        currentPage.value = response.current_page;
        lastPage.value = response.last_page;
        totalEvents.value = response.total;
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Evenimentele nu au putut fi încărcate.',
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

    await loadEvents(page);

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
}

onMounted(() => {
    void loadEvents();
});
</script>

<template>
    <main class="events-page">
        <div class="container">
            <div class="page-heading">
                <p class="eyebrow">
                    Evenimente
                </p>

                <h1 class="page-title">
                    Evenimente disponibile
                </h1>

                <p class="page-description">
                    Descoperă evenimentele viitoare și rezervă un loc.
                </p>
            </div>

            <p
                v-if="isLoading"
                class="state-message"
            >
                Se încarcă evenimentele...
            </p>

            <p
                v-else-if="errorMessage"
                class="form-error"
            >
                {{ errorMessage }}
            </p>

            <div
                v-else-if="events.length > 0"
                class="events-content"
            >
                <p class="events-count">
                    {{ totalEvents }}
                    {{
                        totalEvents === 1
                            ? 'eveniment disponibil'
                            : 'evenimente disponibile'
                    }}
                </p>

                <div class="events-grid">
                    <article
                        v-for="event in events"
                        :key="event.id"
                        class="event-card"
                    >
                        <div class="event-card-header">
                            <span class="category-badge">
                                {{ event.category.name }}
                            </span>

                            <span class="event-capacity">
                                {{ event.capacity }} locuri
                            </span>
                        </div>

                        <h2 class="event-title">
                            {{ event.title }}
                        </h2>

                        <p class="event-date">
                            {{ formatDate(event.starts_at) }}
                        </p>

                        <p class="event-location">
                            {{
                                event.location
                                    ?? 'Locație neanunțată'
                            }}
                        </p>

                        <p class="event-description">
                            {{
                                event.description
                                    ?? 'Detaliile evenimentului vor fi anunțate.'
                            }}
                        </p>

                        <RouterLink
                            class="event-button"
                            :to="`/events/${event.id}`"
                        >
                            Vezi detalii
                        </RouterLink>
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
                    Nu există evenimente disponibile
                </h2>

                <p>
                    Evenimentele publicate și viitoare vor apărea aici.
                </p>
            </div>
        </div>
    </main>
</template>

