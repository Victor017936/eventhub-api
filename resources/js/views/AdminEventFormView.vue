<script setup lang="ts">
import {
    computed,
    onMounted,
    reactive,
    ref,
} from 'vue';
import {
    useRoute,
    useRouter,
} from 'vue-router';
import {
    createAdminEvent,
    getAdminEvent,
    updateAdminEvent,
} from '@/services/adminEvents';
import { getCategories } from '@/services/categories';
import { getApiErrorMessage } from '@/services/errors';
import { useAuthStore } from '@/stores/auth';
import type {
    AdminEventPayload,
    AdminEventStatus,
} from '@/types/adminEvent';
import type { Category } from '@/types/category';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const categories = ref<Category[]>([]);
const isLoading = ref(true);
const isSaving = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const slugWasEdited = ref(false);

const isEditing = computed(
    () => route.name === 'admin-event-edit',
);

const eventId = computed(
    () => Number(route.params.id),
);

const form = reactive({
    categoryId: '',
    title: '',
    slug: '',
    description: '',
    location: '',
    startsAt: '',
    endsAt: '',
    bookingStartsAt: '',
    bookingEndsAt: '',
    capacity: 1,
    status: 'draft' as AdminEventStatus,
});

function generateSlug(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function syncSlugFromTitle(): void {
    if (! slugWasEdited.value) {
        form.slug = generateSlug(form.title);
    }
}

function markSlugAsEdited(): void {
    slugWasEdited.value = true;
}

function toLocalDateTime(value: string): string {
    const date = new Date(value);

    const localDate = new Date(
        date.getTime()
        - date.getTimezoneOffset() * 60_000,
    );

    return localDate
        .toISOString()
        .slice(0, 16);
}

function toApiDateTime(value: string): string {
    return new Date(value).toISOString();
}

function fillForm(event: Awaited<ReturnType<typeof getAdminEvent>>): void {
    form.categoryId = String(event.category_id);
    form.title = event.title;
    form.slug = event.slug;
    form.description = event.description;
    form.location = event.location ?? '';
    form.startsAt = toLocalDateTime(event.starts_at);
    form.endsAt = toLocalDateTime(event.ends_at);
    form.bookingStartsAt = event.booking_starts_at
        ? toLocalDateTime(event.booking_starts_at)
        : '';
    form.bookingEndsAt = event.booking_ends_at
        ? toLocalDateTime(event.booking_ends_at)
        : '';
    form.capacity = event.capacity;
    form.status = event.status;

    slugWasEdited.value = true;
}

function validateForm(): string | null {
    if (! form.categoryId) {
        return 'Selectează categoria evenimentului.';
    }

    if (! form.title.trim()) {
        return 'Titlul evenimentului este obligatoriu.';
    }

    if (! form.description.trim()) {
        return 'Descrierea evenimentului este obligatorie.';
    }

    if (! form.startsAt || ! form.endsAt) {
        return 'Data de început și data de sfârșit sunt obligatorii.';
    }

    const startsAt = new Date(form.startsAt);
    const endsAt = new Date(form.endsAt);

    if (endsAt <= startsAt) {
        return 'Data de sfârșit trebuie să fie după data de început.';
    }

    if (
        Boolean(form.bookingStartsAt)
        !== Boolean(form.bookingEndsAt)
    ) {
        return 'Completează ambele date ale perioadei de rezervare.';
    }

    if (
        form.bookingStartsAt
        && form.bookingEndsAt
    ) {
        const bookingStartsAt =
            new Date(form.bookingStartsAt);

        const bookingEndsAt =
            new Date(form.bookingEndsAt);

        if (bookingEndsAt < bookingStartsAt) {
            return 'Sfârșitul rezervărilor trebuie să fie după începutul rezervărilor.';
        }

        if (bookingStartsAt >= startsAt) {
            return 'Rezervările trebuie să înceapă înaintea evenimentului.';
        }

        if (bookingEndsAt > startsAt) {
            return 'Rezervările trebuie să se încheie cel târziu la începutul evenimentului.';
        }
    }

    if (
        ! Number.isInteger(Number(form.capacity))
        || Number(form.capacity) < 1
    ) {
        return 'Capacitatea trebuie să fie cel puțin 1.';
    }

    return null;
}

async function loadPage(): Promise<void> {
    if (! authStore.isAdmin) {
        await router.replace({
            name: 'home',
        });

        return;
    }

    isLoading.value = true;
    errorMessage.value = '';

    try {
        categories.value = (
            await getCategories()
        ).filter((category) => category.is_active);

        if (isEditing.value) {
            if (
                ! Number.isInteger(eventId.value)
                || eventId.value < 1
            ) {
                errorMessage.value =
                    'ID-ul evenimentului nu este valid.';

                return;
            }

            const event = await getAdminEvent(
                eventId.value,
            );

            fillForm(event);
        }
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Datele formularului nu au putut fi încărcate.',
        );
    } finally {
        isLoading.value = false;
    }
}

async function saveEvent(): Promise<void> {
    errorMessage.value = '';
    successMessage.value = '';

    const validationError = validateForm();

    if (validationError) {
        errorMessage.value = validationError;

        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });

        return;
    }

    const payload: AdminEventPayload = {
        category_id: Number(form.categoryId),
        title: form.title.trim(),
        slug:
            form.slug.trim()
            || generateSlug(form.title),
        description: form.description.trim(),
        location: form.location.trim() || null,
        starts_at: toApiDateTime(form.startsAt),
        ends_at: toApiDateTime(form.endsAt),
        booking_starts_at: form.bookingStartsAt
            ? toApiDateTime(form.bookingStartsAt)
            : null,
        booking_ends_at: form.bookingEndsAt
            ? toApiDateTime(form.bookingEndsAt)
            : null,
        capacity: Number(form.capacity),
        status: form.status,
    };

    isSaving.value = true;

    try {
        if (isEditing.value) {
            await updateAdminEvent(
                eventId.value,
                payload,
            );

            successMessage.value =
                'Evenimentul a fost actualizat cu succes.';
        } else {
            const event = await createAdminEvent(
                payload,
            );

            await router.replace({
                name: 'admin-event-edit',
                params: {
                    id: event.id,
                },
            });

            successMessage.value =
                'Evenimentul a fost creat cu succes.';

            slugWasEdited.value = true;
        }

        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Evenimentul nu a putut fi salvat.',
        );
    } finally {
        isSaving.value = false;
    }
}

onMounted(() => {
    void loadPage();
});
</script>

<template>
    <main class="admin-page">
        <div class="container admin-event-form-container">
            <div class="admin-heading">
                <div>
                    <p class="eyebrow">
                        Administrare
                    </p>

                    <h1 class="page-title">
                        {{
                            isEditing
                                ? 'Editează evenimentul'
                                : 'Eveniment nou'
                        }}
                    </h1>

                    <p class="page-description">
                        {{
                            isEditing
                                ? 'Modifică informațiile evenimentului.'
                                : 'Completează informațiile pentru noul eveniment.'
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

            <p
                v-if="isLoading"
                class="state-message"
            >
                Se încarcă formularul...
            </p>

            <form
                v-else
                class="admin-event-form"
                @submit.prevent="saveEvent"
            >
                <section class="admin-form-section">
                    <div class="admin-section-title">
                        <h2>
                            Informații generale
                        </h2>
                    </div>

                    <div class="admin-event-form-grid">
                        <div class="filter-field">
                            <label for="event-category">
                                Categorie
                            </label>

                            <select
                                id="event-category"
                                v-model="form.categoryId"
                                required
                            >
                                <option value="">
                                    Selectează categoria
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
                            <label for="event-status">
                                Status
                            </label>

                            <select
                                id="event-status"
                                v-model="form.status"
                            >
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

                        <div class="filter-field admin-form-full">
                            <label for="event-title">
                                Titlu
                            </label>

                            <input
                                id="event-title"
                                v-model="form.title"
                                type="text"
                                maxlength="255"
                                required
                                placeholder="Exemplu: Conferință Laravel Moldova"
                                @input="syncSlugFromTitle"
                            >
                        </div>

                        <div class="filter-field admin-form-full">
                            <label for="event-slug">
                                Slug
                            </label>

                            <input
                                id="event-slug"
                                v-model="form.slug"
                                type="text"
                                maxlength="255"
                                placeholder="conferinta-laravel-moldova"
                                @input="markSlugAsEdited"
                            >

                            <small class="field-help">
                                Folosit în URL. Se generează automat din titlu.
                            </small>
                        </div>

                        <div class="filter-field admin-form-full">
                            <label for="event-description">
                                Descriere
                            </label>

                            <textarea
                                id="event-description"
                                v-model="form.description"
                                rows="6"
                                required
                                placeholder="Descrierea completă a evenimentului"
                            />
                        </div>

                        <div class="filter-field admin-form-full">
                            <label for="event-location">
                                Locație
                            </label>

                            <input
                                id="event-location"
                                v-model="form.location"
                                type="text"
                                maxlength="255"
                                placeholder="Exemplu: Chișinău"
                            >
                        </div>
                    </div>
                </section>

                <section class="admin-form-section">
                    <div class="admin-section-title">
                        <h2>
                            Perioada evenimentului
                        </h2>
                    </div>

                    <div class="admin-event-form-grid">
                        <div class="filter-field">
                            <label for="event-starts-at">
                                Data și ora de început
                            </label>

                            <input
                                id="event-starts-at"
                                v-model="form.startsAt"
                                type="datetime-local"
                                required
                            >
                        </div>

                        <div class="filter-field">
                            <label for="event-ends-at">
                                Data și ora de sfârșit
                            </label>

                            <input
                                id="event-ends-at"
                                v-model="form.endsAt"
                                type="datetime-local"
                                required
                            >
                        </div>

                        <div class="filter-field">
                            <label for="booking-starts-at">
                                Începutul rezervărilor
                            </label>

                            <input
                                id="booking-starts-at"
                                v-model="form.bookingStartsAt"
                                type="datetime-local"
                            >
                        </div>

                        <div class="filter-field">
                            <label for="booking-ends-at">
                                Sfârșitul rezervărilor
                            </label>

                            <input
                                id="booking-ends-at"
                                v-model="form.bookingEndsAt"
                                type="datetime-local"
                            >
                        </div>
                    </div>

                    <p class="field-help">
                        Perioada rezervărilor este opțională. Dacă o completezi,
                        ambele date sunt obligatorii.
                    </p>
                </section>

                <section class="admin-form-section">
                    <div class="admin-section-title">
                        <h2>
                            Capacitate
                        </h2>
                    </div>

                    <div class="filter-field admin-capacity-field">
                        <label for="event-capacity">
                            Număr de locuri
                        </label>

                        <input
                            id="event-capacity"
                            v-model.number="form.capacity"
                            type="number"
                            min="1"
                            step="1"
                            required
                        >
                    </div>
                </section>

                <div class="admin-form-actions">
                    <RouterLink
                        class="secondary-button admin-form-cancel"
                        to="/admin/events"
                    >
                        Renunță
                    </RouterLink>

                    <button
                        class="primary-button"
                        type="submit"
                        :disabled="isSaving"
                    >
                        {{
                            isSaving
                                ? 'Se salvează...'
                                : isEditing
                                    ? 'Salvează modificările'
                                    : 'Creează evenimentul'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </main>
</template>
