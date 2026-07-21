<script setup lang="ts">
import {
    computed,
    reactive,
    ref,
} from 'vue';
import {
    useRoute,
    useRouter,
} from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { getApiErrorMessage } from '@/services/errors';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
    email: '',
    password: '',
});

const errorMessage = ref('');

const informationMessage = computed(() => {
    return route.query.reason === 'expired'
        ? 'Sesiunea ta a expirat. Autentifică-te din nou pentru a continua.'
        : '';
});

function getRedirectPath(): string {
    const redirect = route.query.redirect;

    if (
        typeof redirect !== 'string'
        || ! redirect.startsWith('/')
        || redirect.startsWith('//')
    ) {
        return '/';
    }

    return redirect;
}

async function submit(): Promise<void> {
    errorMessage.value = '';

    try {
        await authStore.login({
            email: form.email,
            password: form.password,
        });

        await router.replace(
            getRedirectPath(),
        );
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Autentificarea nu a putut fi realizată.',
        );
    }
}
</script>

<template>
    <main class="auth-page">
        <section class="auth-card">
            <div class="auth-heading">
                <p class="eyebrow">
                    Bun venit
                </p>

                <h1 class="auth-title">
                    Autentificare
                </h1>

                <p class="auth-description">
                    Introdu datele contului tău pentru a continua.
                </p>
            </div>

            <p
                v-if="informationMessage"
                class="info-message"
            >
                {{ informationMessage }}
            </p>

            <p
                v-if="errorMessage"
                class="form-error"
            >
                {{ errorMessage }}
            </p>

            <form
                class="auth-form"
                @submit.prevent="submit"
            >
                <div class="form-group">
                    <label for="login-email">
                        Email
                    </label>

                    <input
                        id="login-email"
                        v-model.trim="form.email"
                        type="email"
                        autocomplete="email"
                        placeholder="nume@example.com"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="login-password">
                        Parolă
                    </label>

                    <input
                        id="login-password"
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Introdu parola"
                        required
                    >
                </div>

                <button
                    class="submit-button"
                    type="submit"
                    :disabled="authStore.isLoading"
                >
                    {{
                        authStore.isLoading
                            ? 'Se autentifică...'
                            : 'Autentificare'
                    }}
                </button>
            </form>

            <p class="auth-footer">
                Nu ai cont?

                <RouterLink to="/register">
                    Creează unul
                </RouterLink>
            </p>
        </section>
    </main>
</template>
