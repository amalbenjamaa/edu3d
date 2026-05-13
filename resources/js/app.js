import './bootstrap'
import '../css/app.css'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createPinia } from 'pinia'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

createInertiaApp({
    // Titre des pages : "Cours | edu3d"
    title: (title) => `${title} | edu3d`,

    // Résoudre automatiquement les composants de page dans resources/js/Pages/
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {
        const pinia = createPinia()

        createApp({ render: () => h(App, props) })
            .use(plugin)   // Inertia
            .use(pinia)    // Pinia store
            .mount(el)
    },

    progress: {
        color: '#6366f1', // barre de progression violet
    },
})