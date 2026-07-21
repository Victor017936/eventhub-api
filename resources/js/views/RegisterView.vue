<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { getApiErrorMessage } from '@/services/errors';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
});

const errorMessage = ref('');

async function submit(): Promise<void> {
    errorMessage.value = '';

    if (form.password !== form.passwordConfirmation) {
        errorMessage.value = 'Parolele introduse nu coincid.';

        return;
    }

    try {
        await authStore.register({
            name: form.name,
            email: form.email,
            password: form.password,
            password_confirmation: form.passwordConfirmation,
        });

        await router.push('/');
    } catch (exception: unknown) {
        errorMessage.value = getApiErrorMessage(
            exception,
            'Contul nu a putut fi creat.',
        );
    }
}
</script>

<template>
    <main class="auth-page">
        <section class="auth-card">
            <div class="auth-heading">
                <p class="eyebrow">
                    Cont nou
                </p>

                <h1 class="auth-title">
                    Creează cont
                </h1>

                <p class="auth-description">
                    Completează datele pentru a te înregistra în EventHub.
                </p>
            </div>

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
                    <label for="register-name">
                        Nume
                    </label>

                    <input
                        id="register-name"
                        v-model.trim="form.name"
                        type="text"
                        autocomplete="name"
                        placeholder="Numele tău"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="register-email">
                        Email
                    </label>

                    <input
                        id="register-email"
                        v-model.trim="form.email"
                        type="email"
                        autocomplete="email"
                        placeholder="nume@example.com"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="register-password">
                        Parolă
                    </label>

                    <input
                        id="register-password"
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        minlength="8"
                        placeholder="Minimum 8 caractere"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="register-password-confirmation">
                        Confirmă parola
                    </label>

                    <input
                        id="register-password-confirmation"
                        v-model="form.passwordConfirmation"
                        type="password"
                        autocomplete="new-password"
                        minlength="8"
                        placeholder="Repetă parola"
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
                            ? 'Se creează contul...'
                            : 'Creează cont'
                    }}
                </button>
            </form>

            <p class="auth-footer">
                Ai deja cont?

                <RouterLink to="/login">
                    Autentifică-te
                </RouterLink>
            </p>
        </section>
    </main>
</template>
