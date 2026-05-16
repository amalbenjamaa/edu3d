<template>
  <TeacherLayout>
    <div class="p-8 max-w-6xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-white">Mes classes</h1>
          <p class="text-sm text-slate-400 mt-1">Gérez vos classes et accédez aux slides 3D</p>
        </div>
        <button class="btn-primary" @click="openModal('create')">+ Nouvelle classe</button>
      </div>

      <div class="search-inline mb-6">
        <input v-model="search" placeholder="Rechercher une classe..." class="search-input"/>
      </div>

      <div class="table-wrap" v-if="filtered.length">
        <table class="data-table">
          <thead>
            <tr>
              <th>Classe</th>
              <th>Cours</th>
              <th>Capacité</th>
              <th>Slides</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cls in filtered" :key="cls.id">
              <td class="font-medium text-white">{{ cls.name }}</td>
              <td class="text-slate-400">{{ cls.course?.title || '—' }}</td>
              <td>{{ cls.capacity }}</td>
              <td>{{ cls.slides_count ?? 0 }}</td>
              <td class="actions">
                <Link :href="`/teacher/slides?classroom=${cls.id}`" class="btn-ghost">Slides</Link>
                <button class="btn-ghost" @click="openModal('edit', cls)">Modifier</button>
                <button class="btn-danger" @click="confirmDelete(cls)">Supprimer</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else class="empty">Aucune classe trouvée. Créez d'abord un cours.</p>

      <div v-if="modal.show" class="modal-overlay" @click.self="modal.show = false">
        <div class="modal">
          <h3>{{ modal.mode === 'create' ? 'Nouvelle classe' : 'Modifier la classe' }}</h3>
          <div class="field"><label>Nom *</label><input v-model="modal.form.name"/></div>
          <div class="field">
            <label>Cours *</label>
            <select v-model="modal.form.course_id">
              <option value="">— Sélectionner —</option>
              <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
            </select>
          </div>
          <div class="field">
            <label>Capacité *</label>
            <input type="number" v-model.number="modal.form.capacity" min="1" max="500"/>
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

const classrooms = ref([])
const courses = ref([])
const search = ref('')
const saving = ref(false)
const toast = ref({ show: false, msg: '', type: 'success' })
const modal = ref({
  show: false, mode: 'create', editId: null,
  form: { name: '', course_id: '', capacity: 30 },
})

const filtered = computed(() => {
  if (!search.value) return classrooms.value
  const q = search.value.toLowerCase()
  return classrooms.value.filter(c =>
    c.name?.toLowerCase().includes(q) || c.course?.title?.toLowerCase().includes(q)
  )
})

function showToast(msg, type = 'success') {
  toast.value = { show: true, msg, type }
  setTimeout(() => toast.value.show = false, 3000)
}

async function load() {
  try {
    const [clsRes, courseRes] = await Promise.all([
      axios.get('/api/classrooms'),
      axios.get('/api/courses'),
    ])
    classrooms.value = clsRes.data.data || clsRes.data
    courses.value = courseRes.data.data || courseRes.data
  } catch {
    showToast('Erreur chargement', 'error')
  }
}

function openModal(mode, cls = null) {
  modal.value = {
    show: true, mode, editId: cls?.id || null,
    form: {
      name: cls?.name || '',
      course_id: cls?.course_id || cls?.course?.id || '',
      capacity: cls?.capacity ?? 30,
    },
  }
}

async function save() {
  if (!modal.value.form.name.trim()) { showToast('Nom requis', 'error'); return }
  if (!modal.value.form.course_id) { showToast('Cours requis', 'error'); return }
  saving.value = true
  try {
    if (modal.value.mode === 'create') {
      await axios.post('/api/classrooms', modal.value.form)
      showToast('Classe créée')
    } else {
      await axios.put(`/api/classrooms/${modal.value.editId}`, modal.value.form)
      showToast('Classe mise à jour')
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

async function confirmDelete(cls) {
  if (!confirm(`Supprimer « ${cls.name} » ?`)) return
  try {
    await axios.delete(`/api/classrooms/${cls.id}`)
    showToast('Classe supprimée')
    await load()
  } catch {
    showToast('Erreur suppression', 'error')
  }
}

onMounted(load)
</script>

<style scoped>
.btn-primary { padding: 0.55rem 1.1rem; background: #7c3aed; color: #fff; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; }
.btn-ghost { padding: 0.4rem 0.8rem; background: rgba(255,255,255,0.06); color: #cbd5e1; border-radius: 6px; border: none; cursor: pointer; font-size: 0.82rem; text-decoration: none; display: inline-block; }
.btn-danger { padding: 0.4rem 0.8rem; background: rgba(239,68,68,0.15); color: #f87171; border-radius: 6px; border: none; cursor: pointer; font-size: 0.82rem; }
.search-inline { max-width: 360px; }
.search-input { width: 100%; padding: 0.55rem 1rem; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #fff; }
.table-wrap { background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { text-align: left; padding: 0.75rem 1rem; font-size: 0.72rem; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #1e293b; }
.data-table td { padding: 0.85rem 1rem; border-bottom: 1px solid #1e293b; color: #cbd5e1; font-size: 0.88rem; }
.actions { display: flex; gap: 6px; flex-wrap: wrap; }
.empty { color: #64748b; text-align: center; padding: 3rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 50; }
.modal { background: #0f172a; border: 1px solid #334155; border-radius: 16px; padding: 1.5rem; width: 100%; max-width: 420px; }
.modal h3 { color: #fff; margin-bottom: 1rem; font-weight: 700; }
.field { margin-bottom: 0.8rem; }
.field label { display: block; font-size: 0.8rem; color: #94a3b8; margin-bottom: 4px; }
.field input, .field select { width: 100%; padding: 0.55rem; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #fff; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 1rem; }
.toast { position: fixed; bottom: 1.5rem; right: 1.5rem; padding: 0.75rem 1.2rem; border-radius: 10px; z-index: 100; }
.toast.success { background: rgba(0,245,212,0.15); color: #00f5d4; border: 1px solid rgba(0,245,212,0.3); }
.toast.error { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
</style>
