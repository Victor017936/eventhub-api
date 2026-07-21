<script setup lang="ts">
import {
    onMounted,
    reactive,
    ref,
} from 'vue';
import {
    createCategory,
    deactivateCategory,
    getCategories,
    updateCategory,
} from '@/services/categories';
import { getApiErrorMessage } from '@/services/errors';
import type { Category } from '@/types/category';

const categories = ref<Category[]>([]);

const isLoading = ref(true);
const isSaving = ref(false);
const deactivatingCategoryId = ref<number | null>(null);
const editingCategoryId = ref<number | null>(null);

const errorMessage = ref('');
const successMessage = ref('');

const form = reactive({
    name: '',
    slug: '',
    description: '',
    isActive: true,
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

function resetForm(): void {
    editingCategoryId.value = null;

    form.name = '';
    form.slug = '';
    form.description = '';
    form.isActive = true;
}

function editCategory(category: Category): void {
    editingCategoryId.value = category.id;

    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description ?? '';
    form.isActive = category.is_active;

    successMessage.value = '';
    errorMessage.value = '';

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
}

async function loadCategories(): Promise<void> {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        categories.value = await getCategories();
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Categoriile nu au putut fi încărcate.',
        );
    } finally {
        isLoading.value = false;
    }
}

async function saveCategory(): Promise<void> {
    errorMessage.value = '';
    successMessage.value = '';

    if (! form.name.trim()) {
        errorMessage.value =
            'Numele categoriei este obligatoriu.';

        return;
    }

    isSaving.value = true;

    const payload = {
        name: form.name.trim(),
        slug: form.slug || generateSlug(form.name),
        description: form.description.trim() || null,
        is_active: form.isActive,
    };

    try {
        if (editingCategoryId.value !== null) {
            await updateCategory(
                editingCategoryId.value,
                payload,
            );

            successMessage.value =
                'Categoria a fost actualizată cu succes.';
        } else {
            await createCategory(payload);

            successMessage.value =
                'Categoria a fost creată cu succes.';
        }

        resetForm();
        await loadCategories();
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Categoria nu a putut fi salvată.',
        );
    } finally {
        isSaving.value = false;
    }
}

async function handleDeactivation(
    category: Category,
): Promise<void> {
    const confirmed = window.confirm(
        `Sigur vrei să dezactivezi categoria „${category.name}”?`,
    );

    if (! confirmed) {
        return;
    }

    errorMessage.value = '';
    successMessage.value = '';
    deactivatingCategoryId.value = category.id;

    try {
        await deactivateCategory(category.id);

        successMessage.value =
            'Categoria a fost dezactivată cu succes.';

        if (editingCategoryId.value === category.id) {
            resetForm();
        }

        await loadCategories();
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Categoria nu a putut fi dezactivată.',
        );
    } finally {
        deactivatingCategoryId.value = null;
    }
}

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
                        Categorii
                    </h1>

                    <p class="page-description">
                        Creează și administrează categoriile evenimentelor.
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

            <section class="admin-form-section">
                <div class="admin-section-title">
                    <h2>
                        {{
                            editingCategoryId !== null
                                ? 'Editează categoria'
                                : 'Categorie nouă'
                        }}
                    </h2>

                    <button
                        v-if="editingCategoryId !== null"
                        class="secondary-button"
                        type="button"
                        @click="resetForm"
                    >
                        Renunță la editare
                    </button>
                </div>

                <form
                    class="admin-category-form"
                    @submit.prevent="saveCategory"
                >
                    <div class="filter-field">
                        <label for="category-name">
                            Nume
                        </label>

                        <input
                            id="category-name"
                            v-model="form.name"
                            type="text"
                            placeholder="Exemplu: Business"
                            required
                        >
                    </div>

                    <div class="filter-field">
                        <label for="category-description">
                            Descriere
                        </label>

                        <textarea
                            id="category-description"
                            v-model="form.description"
                            rows="4"
                            placeholder="Descrierea categoriei"
                        />
                    </div>

                    <label
                        v-if="editingCategoryId !== null"
                        class="category-checkbox"
                    >
                        <input
                            v-model="form.isActive"
                            type="checkbox"
                        >

                        Categoria este activă
                    </label>

                    <button
                        class="primary-button"
                        type="submit"
                        :disabled="isSaving"
                    >
                        {{
                            isSaving
                                ? 'Se salvează...'
                                : editingCategoryId !== null
                                    ? 'Salvează modificările'
                                    : 'Creează categoria'
                        }}
                    </button>
                </form>
            </section>

            <section class="admin-list-section">
                <div class="admin-section-title">
                    <h2>
                        Categorii existente
                    </h2>

                    <span>
                        {{ categories.length }}
                        {{
                            categories.length === 1
                                ? 'categorie'
                                : 'categorii'
                        }}
                    </span>
                </div>

                <p
                    v-if="isLoading"
                    class="state-message"
                >
                    Se încarcă categoriile...
                </p>

                <div
                    v-else-if="categories.length > 0"
                    class="admin-categories-list"
                >
                    <article
                        v-for="category in categories"
                        :key="category.id"
                        class="admin-category-card"
                    >
                        <div>
                            <div class="category-card-heading">
                                <h3>
                                    {{ category.name }}
                                </h3>

                                <span
                                    class="category-status"
                                    :class="{
                                        'category-status-inactive':
                                            ! category.is_active,
                                    }"
                                >
                                    {{
                                        category.is_active
                                            ? 'Activă'
                                            : 'Inactivă'
                                    }}
                                </span>
                            </div>

                            <p class="category-slug">
                                /{{ category.slug }}
                            </p>

                            <p class="category-description">
                                {{
                                    category.description
                                        ?? 'Fără descriere'
                                }}
                            </p>
                        </div>

                        <div class="category-actions">
                            <button
                                class="secondary-button"
                                type="button"
                                @click="editCategory(category)"
                            >
                                Editează
                            </button>

                            <button
                                v-if="category.is_active"
                                class="danger-button"
                                type="button"
                                :disabled="
                                    deactivatingCategoryId
                                        === category.id
                                "
                                @click="
                                    handleDeactivation(category)
                                "
                            >
                                {{
                                    deactivatingCategoryId
                                        === category.id
                                        ? 'Se dezactivează...'
                                        : 'Dezactivează'
                                }}
                            </button>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="empty-state"
                >
                    Nu există încă nicio categorie.
                </div>
            </section>
        </div>
    </main>
</template>

