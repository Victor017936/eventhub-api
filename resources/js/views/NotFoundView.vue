<template>
    <main class="not-found-page">
        <section class="not-found-card">
            <p class="not-found-code">
                404
            </p>

            <h1 class="not-found-title">
                Pagina nu a fost găsită
            </h1>

            <p class="not-found-description">
                Adresa accesată nu există sau pagina a fost mutată.
            </p>

            <div class="not-found-actions">
                <RouterLink
                    class="primary-button"
                    to="/"
                >
                    Pagina principală
                </RouterLink>

                <RouterLink
                    class="secondary-button"
                    to="/events"
                >
                    Vezi evenimentele
                </RouterLink>
            </div>
        </section>
    </main>
</template>
