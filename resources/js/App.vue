<script setup lang="ts">
import {
    ref,
    watch,
} from 'vue';
import {
    useRoute,
    useRouter,
} from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const applicationName = 'EventHub';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const isMenuOpen = ref(false);

function toggleMenu(): void {
    isMenuOpen.value = ! isMenuOpen.value;
}

function closeMenu(): void {
    isMenuOpen.value = false;
}

async function logout(): Promise<void> {
    closeMenu();

    try {
        await authStore.logout();
    } finally {
        await router.push('/');
    }
}

watch(
    () => route.fullPath,
    () => {
        closeMenu();
    },
);
</script>

<template>
    <div class="application">
        <header class="header">
            <div class="container header-content">
                <RouterLink
                    class="logo"
                    to="/"
                    @click="closeMenu"
                >
                    {{ applicationName }}
                </RouterLink>

                <button
                    class="mobile-menu-button"
                    type="button"
                    aria-label="Deschide meniul"
                    aria-controls="main-navigation"
                    :aria-expanded="isMenuOpen"
                    @click="toggleMenu"
                >
                    <span />
                    <span />
                    <span />
                </button>

                <nav
                    id="main-navigation"
                    class="navigation"
                    :class="{
                        'navigation-open': isMenuOpen,
                    }"
                >
                    <RouterLink to="/events">
                        Evenimente
                    </RouterLink>

                    <template v-if="authStore.isAuthenticated">
                        <RouterLink to="/my-reservations">
                            Rezervările mele
                        </RouterLink>

                        <RouterLink
                            v-if="authStore.isAdmin"
                            to="/admin/dashboard"
                        >
                            Administrare
                        </RouterLink>

                        <span
                            v-if="authStore.user"
                            class="navigation-user"
                        >
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
