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
import {
    cancelAdminEvent,
    getAdminEvents,
} from '@/services/adminEvents';
import { getCategories } from '@/services/categories';
import { getApiErrorMessage } from '@/services/errors';
import { useAuthStore } from '@/stores/auth';
import type {
    AdminEvent,
    AdminEventFilters,
    AdminEventStatus,
} from '@/types/adminEvent';
import type { Category } from '@/types/category';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const events = ref<AdminEvent[]>([]);
const categories = ref<Category[]>([]);

const isLoading = ref(true);
const errorMessage = ref('');
const successMessage = ref('');
const cancellingEventId = ref<number | null>(null);

const currentPage = ref(1);
const lastPage = ref(1);
const totalEvents = ref(0);

const filters = reactive({
    search: '',
    categoryId: '',
    status: '',
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
    filters.status = queryString(route.query.status);
    filters.dateFrom = queryString(route.query.date_from);
    filters.dateTo = queryString(route.query.date_to);
}

function currentPageFromUrl(): number {
    const page = Number(route.query.page ?? 1);

    return Number.isInteger(page) && page > 0
        ? page
        : 1;
}

function buildApiFilters(): AdminEventFilters {
    const apiFilters: AdminEventFilters = {};

    if (filters.search) {
        apiFilters.search = filters.search;
    }

    if (filters.categoryId) {
        apiFilters.category_id = Number(
            filters.categoryId,
        );
    }

    if (filters.status) {
        apiFilters.status =
            filters.status as AdminEventStatus;
    }

    if (filters.dateFrom) {
        apiFilters.date_from = filters.dateFrom;
    }

    if (filters.dateTo) {
        apiFilters.date_to = filters.dateTo;
    }

    return apiFilters;
}

function buildRouteQuery(
    page = 1,
): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.search) {
        query.search = filters.search;
    }

    if (filters.categoryId) {
        query.category_id = filters.categoryId;
    }

    if (filters.status) {
        query.status = filters.status;
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
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}

function statusLabel(
    status: AdminEventStatus,
): string {
    const labels: Record<AdminEventStatus, string> = {
        draft: 'Ciornă',
        published: 'Publicat',
        cancelled: 'Anulat',
        completed: 'Finalizat',
    };

    return labels[status];
}

async function loadCategories(): Promise<void> {
    try {
        categories.value = await getCategories();
    } catch {
        categories.value = [];
    }
}

async function loadEvents(): Promise<void> {
    if (! authStore.isAdmin) {
        await router.replace({
            name: 'home',
        });

        return;
    }

    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await getAdminEvents(
            currentPageFromUrl(),
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
    successMessage.value = '';

    await router.push({
        name: 'admin-events',
        query: buildRouteQuery(),
    });
}

async function resetFilters(): Promise<void> {
    filters.search = '';
    filters.categoryId = '';
    filters.status = '';
    filters.dateFrom = '';
    filters.dateTo = '';

    successMessage.value = '';

    await router.push({
        name: 'admin-events',
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
        name: 'admin-events',
        query: buildRouteQuery(page),
    });

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
}

async function handleCancellation(
    event: AdminEvent,
): Promise<void> {
    const confirmed = window.confirm(
        `Sigur vrei să anulezi evenimentul „${event.title}”?`,
    );

    if (! confirmed) {
        return;
    }

    errorMessage.value = '';
    successMessage.value = '';
    cancellingEventId.value = event.id;

    try {
        await cancelAdminEvent(event.id);

        successMessage.value =
            'Evenimentul a fost anulat cu succes.';

        await loadEvents();
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Evenimentul nu a putut fi anulat.',
        );
    } finally {
        cancellingEventId.value = null;
    }
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
    <main class="admin-page">
        <div class="container">
            <div class="admin-heading">
                <div>
                    <p class="eyebrow">
                        Administrare
                    </p>

                    <h1 class="page-title">
                        Evenimente
                    </h1>

                    <p class="page-description">
                        Vezi și administrează toate evenimentele platformei.
                    </p>
                </div>

                <RouterLink
                    class="admin-back-link"
                    to="/admin/dashboard"
                >
                    ← Dashboard
                </RouterLink>
            </div>

            <p
                v-if="successMessage"
                class="success-message"
            >
                {{ successMessage }}
            </p>

            <p
                v-if="errorMessage"
                class="form-error"
            >
                {{ errorMessage }}
            </p>

            <form
                class="event-filters admin-event-filters"
                @submit.prevent="applyFilters"
            >
                <div class="filter-field filter-search">
                    <label for="admin-event-search">
                        Căutare
                    </label>

                    <input
                        id="admin-event-search"
                        v-model.trim="filters.search"
                        type="search"
                        placeholder="Titlu sau descriere"
                    >
                </div>

                <div class="filter-field">
                    <label for="admin-event-category">
                        Categorie
                    </label>

                    <select
                        id="admin-event-category"
                        v-model="filters.categoryId"
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
                    <label for="admin-event-status">
                        Status
                    </label>

                    <select
                        id="admin-event-status"
                        v-model="filters.status"
                    >
                        <option value="">
                            Toate statusurile
                        </option>

                        <option value="draft">
                            Ciornă
                        </option>

                        <option value="published">
                            Publicat
                        </option>

                        <option value="cancelled">
                            Anulat
                        </option>

                        <option value="completed">
                            Finalizat
                        </option>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="admin-event-date-from">
                        De la data
                    </label>

                    <input
                        id="admin-event-date-from"
                        v-model="filters.dateFrom"
                        type="date"
                    >
                </div>

                <div class="filter-field">
                    <label for="admin-event-date-to">
                        Până la data
                    </label>

                    <input
                        id="admin-event-date-to"
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

            <section
                v-else-if="events.length > 0"
                class="admin-list-section"
            >
                <div class="admin-section-title">
                    <h2>
                        Evenimente existente
                    </h2>

                    <span>
                        {{ totalEvents }}
                        {{
                            totalEvents === 1
                                ? 'eveniment'
                                : 'evenimente'
                        }}
                    </span>
                </div>

                <div class="admin-events-list">
                    <article
                        v-for="event in events"
                        :key="event.id"
                        class="admin-event-card"
                    >
                        <div class="admin-event-main">
                            <div class="admin-event-heading">
                                <span
                                    class="admin-event-status"
                                    :class="`admin-event-status-${event.status}`"
                                >
                                    {{ statusLabel(event.status) }}
                                </span>

                                <span class="category-badge">
                                    {{ event.category.name }}
                                </span>
                            </div>

                            <h3>
                                {{ event.title }}
                            </h3>

                            <p class="admin-event-meta">
                                {{ formatDate(event.starts_at) }}
                                ·
                                {{
                                    event.location
                                        ?? 'Locație neanunțată'
                                }}
                            </p>

                            <p class="admin-event-description">
                                {{ event.description }}
                            </p>

                            <p class="admin-event-creator">
                                Creat de {{ event.creator.name }}
                                · {{ event.capacity }} locuri
                            </p>
                        </div>

                        <div class="admin-event-actions">
                            <RouterLink
                                v-if="
                                    event.status === 'published'
                                "
                                class="secondary-button admin-event-action"
                                :to="`/events/${event.id}`"
                            >
                                Vezi public
                            </RouterLink>

                            <button
                                v-if="
                                    event.status !== 'cancelled'
                                    && event.status !== 'completed'
                                "
                                class="danger-button"
                                type="button"
                                :disabled="
                                    cancellingEventId === event.id
                                "
                                @click="handleCancellation(event)"
                            >
                                {{
                                    cancellingEventId === event.id
                                        ? 'Se anulează...'
                                        : 'Anulează'
                                }}
                            </button>
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
            </section>

            <div
                v-else
                class="empty-state"
            >
                <h2>
                    Nu există evenimente
                </h2>

                <p>
                    Modifică filtrele sau creează un eveniment nou.
                </p>
            </div>
        </div>
    </main>
</template>
