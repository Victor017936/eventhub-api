import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useAuthStore } from './stores/auth';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);

const authStore = useAuthStore(pinia);

async function startApplication(): Promise<void> {
    await authStore.initialize();

    window.addEventListener(
        'eventhub:unauthorized',
        () => {
            const currentRoute =
                router.currentRoute.value;

            const redirectPath =
                currentRoute.fullPath;

            authStore.clearAuthentication(true);

            if (currentRoute.name === 'login') {
                return;
            }

            void router.replace({
                name: 'login',
                query: {
                    redirect: redirectPath,
                    reason: 'expired',
                },
            });
        },
    );

    app.use(router);

    await router.isReady();

    app.mount('#app');
}

void startApplication();
