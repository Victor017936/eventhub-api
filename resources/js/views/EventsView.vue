<script setup lang="ts">
import {
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue';
import {
    useRoute,
    useRouter,
} from 'vue-router';
import { getCategories } from '@/services/categories';
import { getApiErrorMessage } from '@/services/errors';
import {
    getEvents,
    type EventFilters,
} from '@/services/events';
import type {
    EventCategory,
    EventItem,
} from '@/types/event';

const route = useRoute();
const router = useRouter();

const events = ref<EventItem[]>([]);
const categories = ref<EventCategory[]>([]);

const isLoading = ref(true);
const areCategoriesLoading = ref(true);
const errorMessage = ref('');

const currentPage = ref(1);
const lastPage = ref(1);
const totalEvents = ref(0);

const filters = reactive({
    search: '',
    categoryId: '',
    location: '',
    dateFrom: '',
    dateTo: '',
});

function queryString(value: unknown): string {
    return typeof value === 'string'
        ? value
        : '';
}

function syncFiltersFromUrl(): void {
    filters.search = queryString(route.query.search);
    filters.categoryId = queryString(
        route.query.category_id,
    );
    filters.location = queryString(route.query.location);
    filters.dateFrom = queryString(route.query.date_from);
    filters.dateTo = queryString(route.query.date_to);
}

function currentPageFromUrl(): number {
    const page = Number(route.query.page ?? 1);

    return Number.isInteger(page) && page > 0
        ? page
        : 1;
}

function buildApiFilters(): EventFilters {
    const apiFilters: EventFilters = {};

    if (filters.search) {
        apiFilters.search = filters.search;
    }

    if (filters.categoryId) {
        apiFilters.category_id = Number(filters.categoryId);
    }

    if (filters.location) {
        apiFilters.location = filters.location;
    }

    if (filters.dateFrom) {
        apiFilters.date_from = filters.dateFrom;
    }

    if (filters.dateTo) {
        apiFilters.date_to = filters.dateTo;
    }

    return apiFilters;
}

function buildRouteQuery(page = 1): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.search) {
        query.search = filters.search;
    }

    if (filters.categoryId) {
        query.category_id = filters.categoryId;
    }

    if (filters.location) {
        query.location = filters.location;
    }

    if (filters.dateFrom) {
        query.date_from = filters.dateFrom;
    }

    if (filters.dateTo) {
        query.date_to = filters.dateTo;
    }

    if (page > 1) {
        query.page = String(page);
    }

    return query;
}

function formatDate(date: string): string {
    return new Intl.DateTimeFormat('ro-RO', {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(new Date(date));
}

async function loadCategories(): Promise<void> {
    areCategoriesLoading.value = true;

    try {
        categories.value = await getCategories();
    } catch {
        categories.value = [];
    } finally {
        areCategoriesLoading.value = false;
    }
}

async function loadEvents(): Promise<void> {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const page = currentPageFromUrl();

        const response = await getEvents(
            page,
            buildApiFilters(),
        );

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

async function applyFilters(): Promise<void> {
    await router.push({
        name: 'events',
        query: buildRouteQuery(),
    });
}

async function resetFilters(): Promise<void> {
    filters.search = '';
    filters.categoryId = '';
    filters.location = '';
    filters.dateFrom = '';
    filters.dateTo = '';

    await router.push({
        name: 'events',
    });
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
        name: 'events',
        query: buildRouteQuery(page),
    });

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
}

watch(
    () => route.fullPath,
    () => {
        syncFiltersFromUrl();
        void loadEvents();
    },
    {
        immediate: true,
    },
);

onMounted(() => {
    void loadCategories();
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

            <form
                class="event-filters"
                @submit.prevent="applyFilters"
            >
                <div class="filter-field filter-search">
                    <label for="event-search">
                        Căutare
                    </label>

                    <input
                        id="event-search"
                        v-model.trim="filters.search"
                        type="search"
                        placeholder="Titlu sau descriere"
                    >
                </div>

                <div class="filter-field">
                    <label for="event-category">
                        Categorie
                    </label>

                    <select
                        id="event-category"
                        v-model="filters.categoryId"
                        :disabled="areCategoriesLoading"
                    >
                        <option value="">
                            Toate categoriile
                        </option>

                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="event-location">
                        Locație
                    </label>

                    <input
                        id="event-location"
                        v-model.trim="filters.location"
                        type="text"
                        placeholder="Exemplu: Chișinău"
                    >
                </div>

                <div class="filter-field">
                    <label for="event-date-from">
                        De la data
                    </label>

                    <input
                        id="event-date-from"
                        v-model="filters.dateFrom"
                        type="date"
                    >
                </div>

                <div class="filter-field">
                    <label for="event-date-to">
                        Până la data
                    </label>

                    <input
                        id="event-date-to"
                        v-model="filters.dateTo"
                        type="date"
                    >
                </div>

                <div class="filter-actions">
                    <button type="submit">
                        Aplică filtrele
                    </button>

                    <button
                        class="secondary-button"
                        type="button"
                        @click="resetFilters"
                    >
                        Resetează
                    </button>
                </div>
            </form>

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
                    Nu există evenimente pentru filtrele selectate
                </h2>

                <p>
                    Modifică filtrele sau resetează căutarea.
                </p>
            </div>
        </div>
    </main>
</template>
