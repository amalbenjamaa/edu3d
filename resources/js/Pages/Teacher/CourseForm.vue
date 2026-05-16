<template>
  <TeacherLayout>
    <div class="p-8 max-w-lg mx-auto">
      <h1 class="text-2xl font-bold text-white mb-6">Nouveau cours</h1>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="label">Titre *</label>
          <input v-model="form.title" class="input" required/>
        </div>
        <div>
          <label class="label">Description</label>
          <textarea v-model="form.description" class="input" rows="3"/>
        </div>
        <div>
          <label class="label">Niveau *</label>
          <select v-model="form.level" class="input">
            <option value="beginner">Débutant</option>
            <option value="intermediate">Intermédiaire</option>
            <option value="advanced">Avancé</option>
          </select>
        </div>
        <div>
          <label class="label">
            <input type="checkbox" v-model="form.is_published"/> Publier le cours
          </label>
        </div>
        <div class="flex gap-3">
          <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? '...' : 'Créer' }}</button>
          <Link href="/teacher/courses" class="btn-ghost">Annuler</Link>
        </div>
      </form>
      <p v-if="error" class="error">{{ error }}</p>
    </div>
  </TeacherLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import TeacherLayout from '@/Layouts/TeacherLayout.vue'

const form = ref({ title: '', description: '', level: 'beginner', is_published: false })
const saving = ref(false)
const error = ref('')

async function submit() {
  saving.value = true
  error.value = ''
  try {
    await axios.post('/api/courses', form.value)
    router.visit('/teacher/courses')
  } catch (e) {
    error.value = Object.values(e?.response?.data?.errors || {})[0]?.[0] || 'Erreur'
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.label { display: block; font-size: 0.8rem; color: #94a3b8; margin-bottom: 4px; }
.input { width: 100%; padding: 0.55rem; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #fff; }
.btn-primary { padding: 0.55rem 1.1rem; background: #7c3aed; color: #fff; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; }
.btn-ghost { padding: 0.55rem 1.1rem; color: #94a3b8; text-decoration: none; }
.error { color: #f87171; margin-top: 1rem; font-size: 0.88rem; }
</style>
