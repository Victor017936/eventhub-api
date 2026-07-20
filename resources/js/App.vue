<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const applicationName = 'EventHub';

const router = useRouter();
const authStore = useAuthStore();

async function logout(): Promise<void> {
    await authStore.logout();
    await router.push('/');
}
</script>

<template>
    <div class="application">
        <header class="header">
            <div class="container header-content">
                <RouterLink
                    class="logo"
                    to="/"
                >
                    {{ applicationName }}
                </RouterLink>

                <nav class="navigation">
                    <RouterLink to="/events">
                        Evenimente
                    </RouterLink>

                    <template v-if="authStore.isAuthenticated">
                        <RouterLink to="/my-reservations">
                            Rezervările mele
                        </RouterLink>

                        <span v-if="authStore.user">
                            {{ authStore.user.name }}
                        </span>

                        <button
                            class="navigation-button"
                            type="button"
                            @click="logout"
                        >
                            Deconectare
                        </button>
                    </template>

                    <template v-else>
                        <RouterLink to="/login">
                            Autentificare
                        </RouterLink>

                        <RouterLink
                            class="register-link"
                            to="/register"
                        >
                            Creează cont
                        </RouterLink>
                    </template>
                </nav>
            </div>
        </header>

        <RouterView />
    </div>
</template>
