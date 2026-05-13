import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export const useAuthStore = defineStore('auth', () => {

    // ─── État ─────────────────────────────────────────────────────────────────
    const user  = ref(null)
    const token = ref(localStorage.getItem('edu3d_token') ?? null)

    // ─── Getters ──────────────────────────────────────────────────────────────
    const isAuthenticated = computed(() => !!user.value)
    const isAdmin         = computed(() => user.value?.role === 'admin')
    const isTeacher       = computed(() => user.value?.role === 'teacher')
    const isStudent       = computed(() => user.value?.role === 'student')
    const role            = computed(() => user.value?.role ?? null)

    // ─── Helpers internes ─────────────────────────────────────────────────────
    function saveToken(t) {
        token.value = t
        localStorage.setItem('edu3d_token', t)
    }

    function clearToken() {
        token.value = null
        localStorage.removeItem('edu3d_token')
    }

    // ─── Actions ──────────────────────────────────────────────────────────────

    async function login(email, password) {
        const res = await fetch('/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password }),
        })
        const data = await res.json()

        if (!res.ok) throw data // retourne les erreurs de validation

        saveToken(data.token)
        user.value = data.user
        redirectByRole()
    }

    async function register(name, email, password, passwordConfirmation, role = 'student') {
        const res = await fetch('/api/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name,
                email,
                password,
                password_confirmation: passwordConfirmation,
                role,
            }),
        })
        const data = await res.json()

        if (!res.ok) throw data

        saveToken(data.token)
        user.value = data.user
        redirectByRole()
    }

    async function logout() {
        if (token.value) {
            await fetch('/api/logout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token.value}`,
                },
            })
        }
        user.value = null
        clearToken()
        router.visit('/login')
    }

    async function fetchMe() {
        if (!token.value) return

        const res = await fetch('/api/me', {
            headers: { 'Authorization': `Bearer ${token.value}` },
        })

        if (!res.ok) {
            // Token expiré ou invalide
            clearToken()
            return
        }

        const data = await res.json()
        user.value = data.user
    }

    // Redirige vers le bon dashboard selon le rôle
    function redirectByRole() {
        const routes = {
            admin:   '/admin/dashboard',
            teacher: '/teacher/dashboard',
            student: '/student/dashboard',
        }
        router.visit(routes[user.value?.role] ?? '/')
    }

    // Helper pour les appels API authentifiés depuis les autres stores
    async function apiFetch(url, options = {}) {
        const res = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token.value}`,
                ...options.headers,
            },
        })

        if (res.status === 401) {
            clearToken()
            router.visit('/login')
            return null
        }

        return res
    }

    return {
        user, token,
        isAuthenticated, isAdmin, isTeacher, isStudent, role,
        login, register, logout, fetchMe, apiFetch, redirectByRole,
    }
})