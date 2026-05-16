<template>
  <TeacherLayout>
    <div class="p-8 max-w-6xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-white">Mes cours</h1>
          <p class="text-sm text-slate-400 mt-1">Créez et gérez vos cours 3D</p>
        </div>
        <button class="btn-primary" @click="openModal('create')">+ Nouveau cours</button>
      </div>

      <div class="search-inline mb-6">
        <input v-model="search" placeholder="Rechercher un cours..." class="search-input"/>
      </div>

      <div class="courses-grid" v-if="filtered.length">
        <div v-for="c in filtered" :key="c.id" class="course-card">
          <div class="course-card-top">
            <span class="level-badge" :class="c.level">{{ levelLabel(c.level) }}</span>
            <span v-if="c.is_published" class="published-badge">Publié</span>
          </div>
          <h3 class="course-title">{{ c.title }}</h3>
          <p class="course-desc">{{ c.description || 'Aucune description' }}</p>
          <div class="course-actions">
            <Link :href="`/teacher/courses/${c.id}`" class="btn-ghost">Détail</Link>
            <button class="btn-ghost" @click="openModal('edit', c)">Modifier</button>
            <button class="btn-danger" @click="confirmDelete(c)">Supprimer</button>
          </div>
        </div>
      </div>
      <p v-else class="empty">Aucun cours trouvé.</p>

      <div v-if="modal.show" class="modal-overlay" @click.self="modal.show = false">
        <div class="modal">
          <h3>{{ modal.mode === 'create' ? 'Nouveau cours' : 'Modifier le cours' }}</h3>
          <div class="field"><label>Titre *</label><input v-model="modal.form.title"/></div>
          <div class="field"><label>Description</label><textarea v-model="modal.form.description" rows="3"/></div>
          <div class="field">
            <label>Niveau *</label>
            <select v-model="modal.form.level">
              <option value="beginner">Débutant</option>
              <option value="intermediate">Intermédiaire</option>
              <option value="advanced">Avancé</option>
            </select>
          </div>
          <div class="field">
            <label><input type="checkbox" v-model="modal.form.is_published"/> Publier (visible aux étudiants)</label>
          </div>
          <div class="modal-actions">
            <button class="btn-ghost" @click="modal.show = false">Annuler</button>
            <button class="btn-primary" @click="save" :disabled="saving">{{ saving ? '...' : 'Enregistrer' }}</button>
          </div>
        </div>
      </div>

      <div v-if="toast.show" class="toast" :class="toast.type">{{ toast.msg }}</div>
    </div>
  </TeacherLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import TeacherLayout from '@/Layouts/TeacherLayout.vue'

const courses = ref([])
const search = ref('')
const saving = ref(false)
const toast = ref({ show: false, msg: '', type: 'success' })
const modal = ref({
  show: false, mode: 'create', editId: null,
  form: { title: '', description: '', level: 'beginner', is_published: false },
})

const filtered = computed(() => {
  if (!search.value) return courses.value
  const q = search.value.toLowerCase()
  return courses.value.filter(c => c.title?.toLowerCase().includes(q))
})

function levelLabel(l) {
  return { beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé' }[l] ?? l
}

function showToast(msg, type = 'success') {
  toast.value = { show: true, msg, type }
  setTimeout(() => toast.value.show = false, 3000)
}

async function load() {
  try {
    const { data } = await axios.get('/api/courses')
    courses.value = data.data || data
  } catch {
    showToast('Erreur chargement', 'error')
  }
}

function openModal(mode, course = null) {
  modal.value = {
    show: true, mode, editId: course?.id || null,
    form: {
      title: course?.title || '',
      description: course?.description || '',
      level: course?.level || 'beginner',
      is_published: course?.is_published ?? false,
    },
  }
}

async function save() {
  if (!modal.value.form.title.trim()) { showToast('Titre requis', 'error'); return }
  saving.value = true
  try {
    if (modal.value.mode === 'create') {
      await axios.post('/api/courses', modal.value.form)
      showToast('Cours créé')
    } else {
      await axios.put(`/api/courses/${modal.value.editId}`, modal.value.form)
      showToast('Cours mis à jour')
    }
    modal.value.show = false
    await load()
  } catch (e) {
    const msg = Object.values(e?.response?.data?.errors || {})[0]?.[0] || 'Erreur'
    showToast(msg, 'error')
  } finally {
    saving.value = false
  }
}

async function confirmDelete(course) {
  if (!confirm(`Supprimer « ${course.title} » ?`)) return
  try {
    await axios.delete(`/api/courses/${course.id}`)
    showToast('Cours supprimé')
    await load()
  } catch {
    showToast('Erreur suppression', 'error')
  }
}

onMounted(load)
</script>

<style scoped>
.btn-primary { padding: 0.55rem 1.1rem; background: #7c3aed; color: #fff; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; }
.btn-ghost { padding: 0.4rem 0.8rem; background: rgba(255,255,255,0.06); color: #cbd5e1; border-radius: 6px; border: none; cursor: pointer; font-size: 0.82rem; text-decoration: none; }
.btn-danger { padding: 0.4rem 0.8rem; background: rgba(239,68,68,0.15); color: #f87171; border-radius: 6px; border: none; cursor: pointer; font-size: 0.82rem; }
.search-inline { max-width: 360px; }
.search-input { width: 100%; padding: 0.55rem 1rem; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #fff; }
.courses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
.course-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; padding: 1.2rem; }
.course-card-top { display: flex; gap: 8px; margin-bottom: 0.6rem; }
.level-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 12px; background: #334155; color: #94a3b8; }
.level-badge.beginner { background: rgba(34,197,94,0.15); color: #4ade80; }
.level-badge.intermediate { background: rgba(245,158,11,0.15); color: #fbbf24; }
.level-badge.advanced { background: rgba(239,68,68,0.15); color: #f87171; }
.published-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 12px; background: rgba(124,58,237,0.2); color: #a78bfa; }
.course-title { font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 0.4rem; }
.course-desc { font-size: 0.85rem; color: #64748b; margin-bottom: 1rem; line-height: 1.5; }
.course-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.empty { color: #64748b; text-align: center; padding: 3rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 50; }
.modal { background: #0f172a; border: 1px solid #334155; border-radius: 16px; padding: 1.5rem; width: 100%; max-width: 420px; }
.modal h3 { color: #fff; margin-bottom: 1rem; font-weight: 700; }
.field { margin-bottom: 0.8rem; }
.field label { display: block; font-size: 0.8rem; color: #94a3b8; margin-bottom: 4px; }
.field input, .field textarea, .field select { width: 100%; padding: 0.55rem; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #fff; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 1rem; }
.toast { position: fixed; bottom: 1.5rem; right: 1.5rem; padding: 0.75rem 1.2rem; border-radius: 10px; z-index: 100; }
.toast.success { background: rgba(0,245,212,0.15); color: #00f5d4; border: 1px solid rgba(0,245,212,0.3); }
.toast.error { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
</style>
