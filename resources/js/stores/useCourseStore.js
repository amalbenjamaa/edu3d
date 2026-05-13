// resources/js/stores/useCourseStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './useAuthStore'

export const useCourseStore = defineStore('courses', () => {
    const courses = ref([])
    const loading = ref(false)

    async function fetchCourses() {
        const auth = useAuthStore()
        loading.value = true
        const res = await auth.apiFetch('/api/courses')
        if (res) {
            const data = await res.json()
            courses.value = data.data ?? data
        }
        loading.value = false
    }

    return { courses, loading, fetchCourses }
})