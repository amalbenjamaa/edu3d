<template>
  <div class="teacher-root">

    <!-- SIDEBAR -->
    <aside :class="['sidebar', { collapsed: sidebarCollapsed }]">
      <div class="sidebar-header">
        <div class="brand">
          <div class="brand-icon">
            <svg width="20" height="20" viewBox="0 0 38 38" fill="none">
              <polygon points="19,2 36,32 2,32" fill="none" stroke="#a78bfa" stroke-width="2.2"/>
              <circle cx="19" cy="19" r="4" fill="#a78bfa"/>
            </svg>
          </div>
          <span class="brand-text" v-if="!sidebarCollapsed">EDU<em>3D</em></span>
        </div>
        <button class="collapse-btn" @click="sidebarCollapsed = !sidebarCollapsed">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
            <path v-if="!sidebarCollapsed" d="M15 18l-6-6 6-6"/>
            <path v-else d="M9 18l6-6-6-6"/>
          </svg>
        </button>
      </div>

      <div class="teacher-badge" v-if="!sidebarCollapsed">
        <span class="badge-dot"></span> Espace Enseignant
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section-label" v-if="!sidebarCollapsed">PRINCIPAL</div>
        <button v-for="item in mainNav" :key="item.id"
          :class="['nav-item', { active: activeView === item.id }]"
          @click="activeView = item.id" :title="item.label">
          <span class="nav-icon" v-html="item.icon"></span>
          <span class="nav-label" v-if="!sidebarCollapsed">{{ item.label }}</span>
        </button>

        <div class="nav-section-label" v-if="!sidebarCollapsed" style="margin-top:1rem">CONTENU</div>
        <button v-for="item in contentNav" :key="item.id"
          :class="['nav-item', { active: activeView === item.id }]"
          @click="activeView = item.id" :title="item.label">
          <span class="nav-icon" v-html="item.icon"></span>
          <span class="nav-label" v-if="!sidebarCollapsed">{{ item.label }}</span>
        </button>
      </nav>

      <div class="sidebar-footer" v-if="!sidebarCollapsed">
        <div class="teacher-info">
          <div class="teacher-avatar-sm">{{ userInitial }}</div>
          <div class="teacher-details">
            <div class="teacher-name-sm">{{ auth.user.name }}</div>
            <div class="teacher-role-sm">Enseignant</div>
          </div>
        </div>
        <Link :href="route('logout')" method="post" as="button" class="logout-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16,17 21,12 16,7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
        </Link>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main">
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">{{ currentPageTitle }}</h1>
          <div class="breadcrumb">Teacher / {{ currentPageTitle }}</div>
        </div>
        <div class="topbar-right">
          <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input v-model="searchQuery" placeholder="Rechercher..."/>
          </div>
          <div class="topbar-time">{{ currentTime }}</div>
        </div>
      </header>

      <transition name="fade" mode="out-in">

        <!-- ── OVERVIEW ── -->
        <div v-if="activeView === 'overview'" key="overview" class="view">
          <div class="welcome-banner">
            <div class="welcome-left">
              <p class="welcome-sub">Bienvenue de retour 👋</p>
              <h2 class="welcome-name">{{ auth.user.name }}</h2>
              <p class="welcome-desc">Vous avez <strong>{{ courses.length }}</strong> cours actifs et <strong>{{ totalStudents }}</strong> étudiants inscrits.</p>
            </div>
            <div class="welcome-right">
              <svg viewBox="0 0 120 120" width="100" height="100">
                <polygon points="60,10 110,85 10,85" fill="none" stroke="rgba(167,139,250,0.4)" stroke-width="2"/>
                <polygon points="60,25 95,80 25,80" fill="rgba(167,139,250,0.08)" stroke="rgba(167,139,250,0.25)" stroke-width="1.5"/>
                <circle cx="60" cy="60" r="8" fill="rgba(167,139,250,0.3)" stroke="#a78bfa" stroke-width="1.5"/>
                <circle cx="60" cy="60" r="3" fill="#a78bfa"/>
              </svg>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stat-card" v-for="(s, i) in statsCards" :key="s.label" :style="{ animationDelay: i * 0.08 + 's' }">
              <div class="stat-top">
                <div class="stat-icon-wrap" :style="{ background: s.bg }"><span v-html="s.icon"></span></div>
                <span :class="['trend', 'up']">↑ {{ s.trend }}</span>
              </div>
              <div class="stat-num">{{ s.val }}</div>
              <div class="stat-lbl">{{ s.label }}</div>
              <div class="stat-bar"><div class="stat-bar-inner" :style="{ width: s.pct + '%', background: s.color }"></div></div>
            </div>
          </div>

          <div class="overview-row">
            <!-- Cours récents -->
            <div class="ov-card">
              <div class="ov-card-header">
                <h3>Mes cours récents</h3>
                <button class="btn-link" @click="activeView = 'courses'">Voir tous →</button>
              </div>
              <div class="course-mini-list">
                <div class="course-mini" v-for="c in courses.slice(0, 4)" :key="c.id" @click="openCourseDetail(c)">
                  <div class="course-mini-icon">📖</div>
                  <div class="course-mini-info">
                    <div class="course-mini-title">{{ c.title }}</div>
                    <div class="course-mini-desc">{{ c.description || 'Aucune description' }}</div>
                  </div>
                </div>
                <div class="empty-state" v-if="!courses.length">Aucun cours — créez-en un !</div>
              </div>
            </div>

            <!-- Classes -->
            <div class="ov-card">
              <div class="ov-card-header">
                <h3>Mes classes</h3>
                <button class="btn-link" @click="activeView = 'classrooms'">Voir toutes →</button>
              </div>
              <div class="class-mini-list">
                <div class="class-mini" v-for="cls in classrooms.slice(0, 4)" :key="cls.id">
                  <div class="class-mini-left">
                    <div class="class-mini-icon">🏛️</div>
                    <div>
                      <div class="class-mini-name">{{ cls.name }}</div>
                      <div class="class-mini-code">Code : <strong>{{ cls.invite_code }}</strong></div>
                    </div>
                  </div>
                </div>
                <div class="empty-state" v-if="!classrooms.length">Aucune classe créée.</div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── COURSES ── -->
        <div v-else-if="activeView === 'courses'" key="courses" class="view">
          <div class="table-toolbar">
            <div class="search-inline">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input v-model="courseSearch" placeholder="Rechercher un cours..."/>
            </div>
            <button class="btn-primary" @click="openCourseModal('create')">+ Nouveau cours</button>
          </div>

          <div class="courses-grid">
            <div class="course-card" v-for="(c, i) in filteredCourses" :key="c.id" :style="{ animationDelay: i * 0.06 + 's' }">
              <div class="course-card-header">
                <div class="course-card-icon">📚</div>
                <div class="course-card-actions">
                  <button class="icon-btn edit"   @click="openCourseModal('edit', c)" title="Modifier">✏️</button>
                  <button class="icon-btn delete" @click="confirmDeleteCourse(c)"    title="Supprimer">🗑️</button>
                </div>
              </div>
              <h4 class="course-card-title">{{ c.title }}</h4>
              <p class="course-card-desc">{{ c.description || 'Aucune description' }}</p>
            </div>
          </div>
          <div class="empty-state" v-if="!filteredCourses.length">Aucun cours trouvé.</div>
        </div>

        <!-- ── CLASSROOMS ── -->
        <div v-else-if="activeView === 'classrooms'" key="classrooms" class="view">
          <div class="table-toolbar">
            <div class="search-inline">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input v-model="classroomSearch" placeholder="Rechercher une classe..."/>
            </div>
            <button class="btn-primary" @click="openClassroomModal('create')">+ Nouvelle classe</button>
          </div>

          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>Classe</th><th>Cours associé</th><th>Code d'invitation</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <tr v-for="cls in filteredClassrooms" :key="cls.id">
                  <td><div class="user-cell"><span class="class-dot">🏛️</span> {{ cls.name }}</div></td>
                  <td class="text-muted">{{ cls.course?.title || '—' }}</td>
                  <td><span class="code-badge">{{ cls.invite_code }}</span></td>
                  <td>
                    <div class="action-btns">
                      <button class="icon-btn edit"   @click="openClassroomModal('edit', cls)" title="Modifier">✏️</button>
                      <button class="icon-btn delete" @click="confirmDeleteClassroom(cls)"      title="Supprimer">🗑️</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            <div class="empty-state" v-if="!filteredClassrooms.length">Aucune classe trouvée.</div>
          </div>
        </div>

        <!-- ── PROFILE ── -->
        <div v-else-if="activeView === 'profile'" key="profile" class="view">
          <div class="profile-card">
            <div class="profile-avatar-lg">{{ userInitial }}</div>
            <h2 class="profile-name">{{ auth.user.name }}</h2>
            <p class="profile-email">{{ auth.user.email }}</p>
            <span class="role-badge teacher">Enseignant</span>
          </div>
        </div>

      </transition>
    </main>

    <!-- MODAL Cours -->
    <transition name="modal">
      <div class="modal-overlay" v-if="courseModal.show" @click.self="courseModal.show = false">
        <div class="modal">
          <div class="modal-header">
            <h3>{{ courseModal.mode === 'create' ? 'Nouveau cours' : 'Modifier le cours' }}</h3>
            <button class="modal-close" @click="courseModal.show = false">✕</button>
          </div>
          <div class="modal-body">
            <div class="field"><label>Titre</label><input v-model="courseModal.form.title" placeholder="Titre du cours"/></div>
            <div class="field"><label>Description</label><textarea v-model="courseModal.form.description" placeholder="Description du cours" rows="3"></textarea></div>
          </div>
          <div class="modal-footer">
            <button class="btn-cancel" @click="courseModal.show = false">Annuler</button>
            <button class="btn-save" @click="saveCourse" :disabled="isSaving">{{ isSaving ? 'Enregistrement...' : 'Enregistrer' }}</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- MODAL Classroom -->
    <transition name="modal">
      <div class="modal-overlay" v-if="classroomModal.show" @click.self="classroomModal.show = false">
        <div class="modal">
          <div class="modal-header">
            <h3>{{ classroomModal.mode === 'create' ? 'Nouvelle classe' : 'Modifier la classe' }}</h3>
            <button class="modal-close" @click="classroomModal.show = false">✕</button>
          </div>
          <div class="modal-body">
            <div class="field"><label>Nom de la classe</label><input v-model="classroomModal.form.name" placeholder="ex: Groupe A"/></div>
            <div class="field">
              <label>Cours associé</label>
              <select v-model="classroomModal.form.course_id">
                <option value="">— Sélectionner un cours —</option>
                <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-cancel" @click="classroomModal.show = false">Annuler</button>
            <button class="btn-save" @click="saveClassroom" :disabled="isSaving">{{ isSaving ? 'Enregistrement...' : 'Enregistrer' }}</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- MODAL delete confirm -->
    <transition name="modal">
      <div class="modal-overlay" v-if="deleteModal.show" @click.self="deleteModal.show = false">
        <div class="modal modal-sm">
          <div class="modal-header danger">
            <h3>Confirmer la suppression</h3>
            <button class="modal-close" @click="deleteModal.show = false">✕</button>
          </div>
          <div class="modal-body">
            <p>Supprimer <strong>{{ deleteModal.item?.title || deleteModal.item?.name }}</strong> ? Cette action est irréversible.</p>
          </div>
          <div class="modal-footer">
            <button class="btn-cancel" @click="deleteModal.show = false">Annuler</button>
            <button class="btn-delete" @click="doDelete" :disabled="isSaving">Supprimer</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Toast -->
    <transition name="toast">
      <div class="toast-global" v-if="toast.show" :class="toast.type">
        {{ toast.type === 'success' ? '✅' : '❌' }} {{ toast.msg }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  auth: Object,  // { user: { id, name, email, role } }
})

const sidebarCollapsed  = ref(false)
const activeView        = ref('overview')
const searchQuery       = ref('')
const courseSearch      = ref('')
const classroomSearch   = ref('')
const currentTime       = ref('')
const isSaving          = ref(false)
const courses           = ref([])
const classrooms        = ref([])
const toast             = ref({ show: false, msg: '', type: 'success' })
const courseModal       = ref({ show: false, mode: 'create', form: { title: '', description: '' }, editId: null })
const classroomModal    = ref({ show: false, mode: 'create', form: { name: '', course_id: '' }, editId: null })
const deleteModal       = ref({ show: false, item: null, type: '' })

// ── Icons ─────────────────────────────────────────────────────────────────────
const svgHome      = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>`
const svgBook      = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>`
const svgClass     = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>`
const svgProfile   = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`

const mainNav    = [{ id: 'overview', label: 'Vue globale', icon: svgHome }, { id: 'profile', label: 'Mon Profil', icon: svgProfile }]
const contentNav = [{ id: 'courses', label: 'Mes cours', icon: svgBook }, { id: 'classrooms', label: 'Mes classes', icon: svgClass }]

// ── Computed ──────────────────────────────────────────────────────────────────
const userInitial  = computed(() => props.auth?.user?.name?.charAt(0)?.toUpperCase() || 'E')
const totalStudents = computed(() => classrooms.value.reduce((acc, c) => acc + (c.students_count || 0), 0))

const currentPageTitle = computed(() => ({
  overview: 'Vue globale', courses: 'Mes Cours', classrooms: 'Mes Classes', profile: 'Mon Profil'
})[activeView.value])

const statsCards = computed(() => [
  { label: 'Cours créés',    val: courses.value.length,    pct: Math.min(courses.value.length * 10, 100), trend: courses.value.length,    color: '#a78bfa', bg: 'rgba(167,139,250,0.1)', icon: svgBook },
  { label: 'Classes actives', val: classrooms.value.length, pct: Math.min(classrooms.value.length * 20, 100), trend: classrooms.value.length, color: '#00f5d4', bg: 'rgba(0,245,212,0.1)',   icon: svgClass },
  { label: 'Étudiants',      val: totalStudents.value,     pct: Math.min(totalStudents.value, 100),       trend: totalStudents.value,     color: '#f59e0b', bg: 'rgba(245,158,11,0.1)',  icon: svgProfile },
])

const filteredCourses = computed(() => {
  if (!courseSearch.value) return courses.value
  const q = courseSearch.value.toLowerCase()
  return courses.value.filter(c => c.title?.toLowerCase().includes(q))
})

const filteredClassrooms = computed(() => {
  if (!classroomSearch.value) return classrooms.value
  const q = classroomSearch.value.toLowerCase()
  return classrooms.value.filter(c => c.name?.toLowerCase().includes(q))
})

// ── Helpers ───────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  toast.value = { show: true, msg, type }
  setTimeout(() => toast.value.show = false, 3000)
}

function openCourseDetail(course) { activeView.value = 'courses' }

// ── API ───────────────────────────────────────────────────────────────────────
async function loadCourses() {
  try {
    const { data } = await axios.get('/api/courses')
    courses.value = data.data || data
  } catch { showToast('Erreur chargement des cours', 'error') }
}

async function loadClassrooms() {
  try {
    const { data } = await axios.get('/api/classrooms')
    classrooms.value = data.data || data
  } catch { showToast('Erreur chargement des classes', 'error') }
}

function openCourseModal(mode, course = null) {
  courseModal.value = { show: true, mode, editId: course?.id || null, form: { title: course?.title || '', description: course?.description || '' } }
}

async function saveCourse() {
  if (!courseModal.value.form.title) { showToast('Titre requis', 'error'); return }
  isSaving.value = true
  try {
    if (courseModal.value.mode === 'create') {
      await axios.post('/api/courses', courseModal.value.form)
      showToast('Cours créé avec succès')
    } else {
      await axios.put(`/api/courses/${courseModal.value.editId}`, courseModal.value.form)
      showToast('Cours modifié avec succès')
    }
    courseModal.value.show = false
    await loadCourses()
  } catch { showToast('Une erreur est survenue', 'error') } finally { isSaving.value = false }
}

function openClassroomModal(mode, cls = null) {
  classroomModal.value = { show: true, mode, editId: cls?.id || null, form: { name: cls?.name || '', course_id: cls?.course_id || '' } }
}

async function saveClassroom() {
  if (!classroomModal.value.form.name) { showToast('Nom requis', 'error'); return }
  isSaving.value = true
  try {
    if (classroomModal.value.mode === 'create') {
      await axios.post('/api/classrooms', classroomModal.value.form)
      showToast('Classe créée avec succès')
    } else {
      await axios.put(`/api/classrooms/${classroomModal.value.editId}`, classroomModal.value.form)
      showToast('Classe modifiée')
    }
    classroomModal.value.show = false
    await loadClassrooms()
  } catch { showToast('Une erreur est survenue', 'error') } finally { isSaving.value = false }
}

function confirmDeleteCourse(course) { deleteModal.value = { show: true, item: course, type: 'course' } }
function confirmDeleteClassroom(cls) { deleteModal.value = { show: true, item: cls,    type: 'classroom' } }

async function doDelete() {
  isSaving.value = true
  try {
    const { type, item } = deleteModal.value
    if (type === 'course')    await axios.delete(`/api/courses/${item.id}`)
    if (type === 'classroom') await axios.delete(`/api/classrooms/${item.id}`)
    showToast('Suppression réussie')
    deleteModal.value.show = false
    await loadCourses()
    await loadClassrooms()
  } catch { showToast('Erreur lors de la suppression', 'error') } finally { isSaving.value = false }
}

// ── Clock ─────────────────────────────────────────────────────────────────────
function tick() { currentTime.value = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }

onMounted(() => {
  loadCourses()
  loadClassrooms()
  tick()
  setInterval(tick, 60000)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');
* { box-sizing: border-box; margin: 0; padding: 0; }

.teacher-root { display: flex; min-height: 100vh; background: #050b18; font-family: 'DM Sans', sans-serif; color: #fff; }

/* SIDEBAR */
.sidebar { width: 240px; min-height: 100vh; background: rgba(255,255,255,0.03); border-right: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; transition: width 0.3s; flex-shrink: 0; }
.sidebar.collapsed { width: 64px; }
.sidebar-header { display: flex; align-items: center; justify-content: space-between; padding: 1.4rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
.brand { display: flex; align-items: center; gap: 10px; overflow: hidden; }
.brand-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(167,139,250,0.1); border: 1px solid rgba(167,139,250,0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.brand-text { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.1rem; color: #fff; white-space: nowrap; }
.brand-text em { color: #a78bfa; font-style: normal; }
.collapse-btn { background: none; border: none; color: rgba(255,255,255,0.4); cursor: pointer; padding: 4px; border-radius: 6px; display: flex; align-items: center; }
.collapse-btn:hover { color: #fff; background: rgba(255,255,255,0.05); }
.teacher-badge { margin: 0.8rem 1rem; padding: 0.5rem 0.9rem; background: rgba(167,139,250,0.08); border: 1px solid rgba(167,139,250,0.2); border-radius: 20px; font-size: 0.75rem; color: #a78bfa; display: flex; align-items: center; gap: 6px; }
.badge-dot { width: 6px; height: 6px; border-radius: 50%; background: #a78bfa; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

.nav-section-label { font-size: 0.65rem; font-weight: 600; color: rgba(255,255,255,0.2); letter-spacing: 0.1em; padding: 0.5rem 0.9rem 0.3rem; text-transform: uppercase; }
.sidebar-nav { flex: 1; padding: 0.8rem 0.6rem; display: flex; flex-direction: column; gap: 3px; }
.nav-item { display: flex; align-items: center; gap: 12px; padding: 0.65rem 0.9rem; border: none; background: transparent; color: rgba(255,255,255,0.45); font-family: 'DM Sans', sans-serif; font-size: 0.88rem; cursor: pointer; border-radius: 10px; transition: all 0.2s; text-align: left; white-space: nowrap; overflow: hidden; }
.nav-item:hover { color: #fff; background: rgba(255,255,255,0.05); }
.nav-item.active { color: #a78bfa; background: rgba(167,139,250,0.1); }
.nav-icon { flex-shrink: 0; display: flex; align-items: center; }
.sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.teacher-info { display: flex; align-items: center; gap: 10px; overflow: hidden; }
.teacher-avatar-sm { width: 32px; height: 32px; border-radius: 50%; background: #a78bfa; color: #050b18; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; flex-shrink: 0; }
.teacher-name-sm { font-size: 0.82rem; font-weight: 500; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.teacher-role-sm { font-size: 0.72rem; color: rgba(255,255,255,0.35); }
.logout-btn { background: none; border: none; color: rgba(255,255,255,0.3); cursor: pointer; padding: 6px; border-radius: 8px; display: flex; align-items: center; transition: all 0.2s; }
.logout-btn:hover { color: #f87171; background: rgba(239,68,68,0.1); }

/* MAIN */
.main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.topbar { display: flex; align-items: center; justify-content: space-between; padding: 1.2rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(255,255,255,0.02); }
.topbar-left .page-title { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 700; color: #fff; }
.topbar-left .breadcrumb  { font-size: 0.75rem; color: rgba(255,255,255,0.3); margin-top: 2px; }
.topbar-right { display: flex; align-items: center; gap: 1rem; }
.search-box { display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 0.45rem 0.9rem; }
.search-box input { background: none; border: none; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.85rem; outline: none; width: 160px; }
.topbar-time { font-size: 0.82rem; color: rgba(255,255,255,0.35); }
.view { padding: 2rem; overflow-y: auto; flex: 1; animation: fadeUp 0.4s ease; }

/* Welcome banner */
.welcome-banner { display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, rgba(167,139,250,0.08), rgba(167,139,250,0.03)); border: 1px solid rgba(167,139,250,0.15); border-radius: 20px; padding: 1.8rem 2rem; margin-bottom: 1.5rem; }
.welcome-sub  { font-size: 0.85rem; color: rgba(255,255,255,0.4); margin-bottom: 4px; }
.welcome-name { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 700; color: #fff; margin-bottom: 6px; }
.welcome-desc { font-size: 0.88rem; color: rgba(255,255,255,0.5); }
.welcome-desc strong { color: #a78bfa; }

/* Stats */
.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 1.3rem; animation: fadeUp 0.5s ease both; }
.stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.stat-icon-wrap { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.trend { font-size: 0.73rem; padding: 2px 7px; border-radius: 20px; }
.trend.up { background: rgba(167,139,250,0.12); color: #a78bfa; }
.stat-num { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 700; color: #fff; }
.stat-lbl { font-size: 0.8rem; color: rgba(255,255,255,0.4); margin-top: 2px; margin-bottom: 0.8rem; }
.stat-bar { height: 3px; background: rgba(255,255,255,0.07); border-radius: 2px; }
.stat-bar-inner { height: 100%; border-radius: 2px; transition: width 0.8s; }

/* Overview row */
.overview-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.ov-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 1.4rem; }
.ov-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.ov-card-header h3 { font-family: 'Syne', sans-serif; font-size: 0.9rem; font-weight: 600; color: #fff; }
.btn-link { background: none; border: none; color: #a78bfa; font-size: 0.8rem; cursor: pointer; }
.course-mini-list, .class-mini-list { display: flex; flex-direction: column; gap: 8px; }
.course-mini { display: flex; align-items: center; gap: 12px; padding: 0.7rem; border-radius: 10px; cursor: pointer; transition: background 0.2s; }
.course-mini:hover { background: rgba(255,255,255,0.04); }
.course-mini-icon { font-size: 1.2rem; }
.course-mini-title { font-size: 0.85rem; color: #fff; font-weight: 500; }
.course-mini-desc  { font-size: 0.75rem; color: rgba(255,255,255,0.35); }
.class-mini { display: flex; align-items: center; justify-content: space-between; padding: 0.7rem; border-radius: 10px; background: rgba(255,255,255,0.02); }
.class-mini-left { display: flex; align-items: center; gap: 10px; }
.class-mini-icon { font-size: 1.2rem; }
.class-mini-name { font-size: 0.85rem; color: #fff; font-weight: 500; }
.class-mini-code { font-size: 0.75rem; color: rgba(255,255,255,0.35); }
.class-mini-code strong { color: #a78bfa; }

/* Courses grid */
.courses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; }
.course-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 1.3rem; animation: fadeUp 0.5s ease both; transition: border-color 0.2s; }
.course-card:hover { border-color: rgba(167,139,250,0.3); }
.course-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.8rem; }
.course-card-icon { font-size: 1.4rem; }
.course-card-actions { display: flex; gap: 6px; }
.course-card-title { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 600; color: #fff; margin-bottom: 6px; }
.course-card-desc { font-size: 0.82rem; color: rgba(255,255,255,0.4); line-height: 1.5; }

/* Table */
.table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
.search-inline { display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 0.5rem 1rem; flex: 1; max-width: 360px; }
.search-inline input { background: none; border: none; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.85rem; outline: none; width: 100%; }
.search-inline input::placeholder { color: rgba(255,255,255,0.25); }
.btn-primary { padding: 0.55rem 1.1rem; background: linear-gradient(135deg, #a78bfa, #7c3aed); border: none; border-radius: 10px; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(167,139,250,0.3); }
.table-wrap { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 14px; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 0.9rem 1.2rem; text-align: left; font-size: 0.72rem; font-weight: 600; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid rgba(255,255,255,0.05); }
.data-table td { padding: 0.9rem 1.2rem; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.04); color: rgba(255,255,255,0.7); }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: rgba(255,255,255,0.02); }
.user-cell { display: flex; align-items: center; gap: 8px; color: #fff; }
.text-muted { color: rgba(255,255,255,0.4) !important; }
.code-badge { background: rgba(167,139,250,0.12); border: 1px solid rgba(167,139,250,0.25); color: #a78bfa; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-family: monospace; letter-spacing: 0.05em; }
.action-btns { display: flex; gap: 6px; }
.icon-btn { width: 28px; height: 28px; border: none; border-radius: 7px; cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.icon-btn.edit   { background: rgba(167,139,250,0.1); }
.icon-btn.delete { background: rgba(239,68,68,0.1); }
.icon-btn:hover  { transform: scale(1.1); }

/* Profile */
.profile-card { max-width: 400px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 20px; padding: 2.5rem; display: flex; flex-direction: column; align-items: center; gap: 12px; }
.profile-avatar-lg { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #a78bfa, #6366f1); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; color: #fff; }
.profile-name  { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 700; color: #fff; }
.profile-email { font-size: 0.85rem; color: rgba(255,255,255,0.4); }
.role-badge { padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.role-badge.teacher { background: rgba(167,139,250,0.15); color: #a78bfa; }

.empty-state { text-align: center; padding: 2.5rem; color: rgba(255,255,255,0.25); font-size: 0.88rem; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center; }
.modal { background: #0d1b2e; border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; width: 100%; max-width: 420px; overflow: hidden; box-shadow: 0 40px 80px rgba(0,0,0,0.5); }
.modal-sm { max-width: 360px; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.4rem 1.6rem; border-bottom: 1px solid rgba(255,255,255,0.07); }
.modal-header h3 { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: #fff; }
.modal-header.danger h3 { color: #f87171; }
.modal-close { background: none; border: none; color: rgba(255,255,255,0.35); cursor: pointer; font-size: 1rem; }
.modal-close:hover { color: #fff; }
.modal-body { padding: 1.4rem 1.6rem; display: flex; flex-direction: column; gap: 1rem; }
.modal-body p { color: rgba(255,255,255,0.6); font-size: 0.88rem; line-height: 1.6; }
.modal-body .field { display: flex; flex-direction: column; gap: 6px; }
.modal-body label { font-size: 0.8rem; color: rgba(255,255,255,0.5); font-weight: 500; }
.modal-body input, .modal-body textarea, .modal-body select { padding: 0.65rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.88rem; outline: none; resize: vertical; }
.modal-body input:focus, .modal-body textarea:focus, .modal-body select:focus { border-color: rgba(167,139,250,0.4); }
.modal-body select option { background: #0d1b2e; }
.modal-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 1.2rem 1.6rem; border-top: 1px solid rgba(255,255,255,0.06); }
.btn-cancel { padding: 0.6rem 1.2rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: rgba(255,255,255,0.6); font-family: 'DM Sans', sans-serif; cursor: pointer; }
.btn-save   { padding: 0.6rem 1.4rem; background: linear-gradient(135deg, #a78bfa, #7c3aed); border: none; border-radius: 10px; color: #fff; font-family: 'DM Sans', sans-serif; font-weight: 600; cursor: pointer; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-delete { padding: 0.6rem 1.4rem; background: linear-gradient(135deg, #ef4444, #dc2626); border: none; border-radius: 10px; color: #fff; font-family: 'DM Sans', sans-serif; font-weight: 600; cursor: pointer; }

/* Toast */
.toast-global { position: fixed; bottom: 2rem; right: 2rem; z-index: 200; padding: 0.85rem 1.4rem; border-radius: 12px; font-size: 0.88rem; display: flex; align-items: center; gap: 8px; }
.toast-global.success { background: rgba(0,245,212,0.12); border: 1px solid rgba(0,245,212,0.3); color: #00f5d4; }
.toast-global.error   { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }

/* Transitions */
.fade-enter-active, .fade-leave-active { transition: all 0.25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(20px); }

@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 900px) {
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .overview-row { grid-template-columns: 1fr; }
  .courses-grid { grid-template-columns: 1fr; }
}
</style>