<template>
  <TeacherLayout>
    <div class="p-8 max-w-4xl mx-auto">
      <Link href="/teacher/courses" class="back">← Retour aux cours</Link>
      <div v-if="loading" class="text-slate-400">Chargement...</div>
      <template v-else-if="course">
        <h1 class="text-2xl font-bold text-white mt-4">{{ course.title }}</h1>
        <p class="text-slate-400 mt-2">{{ course.description || 'Sans description' }}</p>
        <div class="badges mt-3">
          <span class="badge">{{ levelLabel(course.level) }}</span>
          <span v-if="course.is_published" class="badge published">Publié</span>
        </div>
        <h2 class="text-lg font-semibold text-white mt-8 mb-4">Classes ({{ course.classrooms?.length ?? 0 }})</h2>
        <ul class="class-list">
          <li v-for="cls in course.classrooms" :key="cls.id">
            <span>{{ cls.name }}</span>
            <Link :href="`/teacher/slides?classroom=${cls.id}`" class="link">Gérer les slides →</Link>
          </li>
        </ul>
        <p v-if="!course.classrooms?.length" class="text-slate-500">Aucune classe. Créez-en une depuis Mes classes.</p>
      </template>
    </div>
  </TeacherLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import TeacherLayout from '@/Layouts/TeacherLayout.vue'

const props = defineProps({ courseId: [String, Number] })
const course = ref(null)
const loading = ref(true)

function levelLabel(l) {
  return { beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé' }[l] ?? l
}

onMounted(async () => {
  try {
    const { data } = await axios.get(`/api/courses/${props.courseId}`)
    course.value = data.course || data.data || data
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.back { color: #818cf8; font-size: 0.88rem; text-decoration: none; }
.badges { display: flex; gap: 8px; }
.badge { font-size: 0.75rem; padding: 3px 10px; border-radius: 12px; background: #334155; color: #94a3b8; }
.badge.published { background: rgba(124,58,237,0.2); color: #a78bfa; }
.class-list { list-style: none; padding: 0; }
.class-list li { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; margin-bottom: 8px; color: #e2e8f0; }
.link { color: #818cf8; font-size: 0.85rem; text-decoration: none; }
</style>
