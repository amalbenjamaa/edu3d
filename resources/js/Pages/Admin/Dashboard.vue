<template>
  <div class="admin-root">

    <!-- SIDEBAR -->
    <aside :class="['sidebar', { collapsed: sidebarCollapsed }]">
      <div class="sidebar-header">
        <div class="brand">
          <div class="brand-icon">
            <svg width="22" height="22" viewBox="0 0 38 38" fill="none">
              <polygon points="19,2 36,32 2,32" fill="none" stroke="#00f5d4" stroke-width="2.2"/>
              <circle cx="19" cy="19" r="4" fill="#00f5d4"/>
            </svg>
          </div>
          <span class="brand-text" v-if="!sidebarCollapsed">EDU<em>3D</em></span>
        </div>
        <button class="collapse-btn" @click="sidebarCollapsed = !sidebarCollapsed">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
            <path v-if="!sidebarCollapsed" d="M15 18l-6-6 6-6"/>
            <path v-else d="M9 18l6-6-6-6"/>
          </svg>
        </button>
      </div>

      <nav class="sidebar-nav">
        <button v-for="item in navItems" :key="item.id"
          :class="['nav-item', { active: activeView === item.id }]"
          @click="activeView = item.id" :title="item.label">
          <span class="nav-icon" v-html="item.icon"></span>
          <span class="nav-label" v-if="!sidebarCollapsed">{{ item.label }}</span>
        </button>
      </nav>

      <div class="sidebar-footer" v-if="!sidebarCollapsed">
        <div class="admin-info">
          <div class="admin-avatar">{{ userInitial }}</div>
          <div class="admin-details">
            <div class="admin-name">{{ auth.user.name }}</div>
            <div class="admin-role">{{ auth.user.role }}</div>
          </div>
        </div>
        <Link href="/api/logout" method="post" as="button" class="logout-btn" title="Déconnexion">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16,17 21,12 16,7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
        </Link>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main">
      <!-- Topbar -->
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">{{ currentPageTitle }}</h1>
          <div class="breadcrumb">Admin / {{ currentPageTitle }}</div>
        </div>
        <div class="topbar-right">
          <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
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
          <!-- Stats cards -->
          <div class="stats-grid">
            <div class="stat-card" v-for="(s, i) in statsCards" :key="s.label" :style="{ animationDelay: i * 0.08 + 's' }">
              <div class="stat-top">
                <div class="stat-icon" :style="{ background: s.bg }"><span v-html="s.icon"></span></div>
                <div :class="['stat-trend', s.trend > 0 ? 'up' : 'down']">{{ s.trend > 0 ? '↑' : '↓' }} {{ Math.abs(s.trend) }}%</div>
              </div>
              <div class="stat-val">{{ s.val }}</div>
              <div class="stat-label">{{ s.label }}</div>
              <div class="stat-bar"><div class="stat-bar-fill" :style="{ width: s.pct + '%', background: s.color }"></div></div>
            </div>
          </div>

          <!-- Charts row -->
          <div class="charts-row">
            <!-- Donut répartition -->
            <div class="chart-card">
              <div class="card-header"><h3>Répartition des utilisateurs</h3></div>
              <div class="donut-wrap">
                <svg viewBox="0 0 120 120" class="donut-svg">
                  <circle cx="60" cy="60" r="44" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="16"/>
                  <circle cx="60" cy="60" r="44" fill="none" stroke="#00f5d4" stroke-width="16"
                    :stroke-dasharray="`${studentPct * 2.76} 276`" stroke-dashoffset="69" stroke-linecap="round"/>
                  <circle cx="60" cy="60" r="44" fill="none" stroke="#6366f1" stroke-width="16"
                    :stroke-dasharray="`${teacherPct * 2.76} 276`"
                    :stroke-dashoffset="69 - studentPct * 2.76" stroke-linecap="round"/>
                </svg>
                <div class="donut-center">
                  <span class="donut-total">{{ users.length }}</span>
                  <span class="donut-label">Total</span>
                </div>
              </div>
              <div class="donut-legend">
                <div class="legend-item"><span class="dot" style="background:#00f5d4"></span><span>Étudiants</span><strong>{{ students.length }}</strong></div>
                <div class="legend-item"><span class="dot" style="background:#6366f1"></span><span>Enseignants</span><strong>{{ teachers.length }}</strong></div>
                <div class="legend-item"><span class="dot" style="background:#f59e0b"></span><span>Admins</span><strong>{{ admins.length }}</strong></div>
              </div>
            </div>

            <!-- Utilisateurs récents -->
            <div class="chart-card flex-2">
              <div class="card-header">
                <h3>Utilisateurs récents</h3>
                <button class="btn-text" @click="activeView = 'users'">Voir tous →</button>
              </div>
              <div class="recent-list">
                <div class="recent-item" v-for="u in recentUsers" :key="u.id">
                  <div class="user-avatar-sm" :style="{ background: roleColor(u.role) }">{{ u.name?.charAt(0)?.toUpperCase() }}</div>
                  <div class="recent-info">
                    <div class="recent-name">{{ u.name }}</div>
                    <div class="recent-email">{{ u.email }}</div>
                  </div>
                  <div :class="['role-badge', u.role?.toLowerCase()]">{{ u.role }}</div>
                </div>
                <div class="empty-state" v-if="!recentUsers.length"><span>Aucun utilisateur</span></div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── USERS ── -->
        <div v-else-if="activeView === 'users'" key="users" class="view">
          <div class="sub-tabs">
            <button v-for="t in userTabs" :key="t.id" :class="['sub-tab', { active: userTab === t.id }]" @click="userTab = t.id">
              <span>{{ t.icon }}</span> {{ t.label }} <span class="count">{{ t.count }}</span>
            </button>
          </div>

          <div class="table-toolbar">
            <div class="search-inline">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input v-model="userSearch" placeholder="Rechercher un utilisateur..."/>
            </div>
            <button class="btn-add" @click="openModal('create')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Ajouter
            </button>
          </div>

          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Utilisateur</th>
                  <th>Email</th>
                  <th>Rôle</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in filteredUsers" :key="user.id">
                  <td>
                    <div class="user-cell">
                      <div class="user-avatar-sm" :style="{ background: roleColor(user.role) }">{{ user.name?.charAt(0)?.toUpperCase() }}</div>
                      <span>{{ user.name }}</span>
                    </div>
                  </td>
                  <td class="text-muted">{{ user.email }}</td>
                  <td><span :class="['role-badge', user.role?.toLowerCase()]">{{ user.role }}</span></td>
                  <td>
                    <div class="action-btns">
                      <button class="icon-btn edit" @click="openModal('edit', user)" title="Modifier">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <button class="icon-btn delete" @click="confirmDelete(user)" title="Supprimer">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3,6 5,6 21,6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            <div class="empty-state" v-if="!filteredUsers.length">Aucun utilisateur trouvé</div>
          </div>
        </div>

        <!-- ── PROFILE ── -->
        <div v-else-if="activeView === 'profile'" key="profile" class="view">
          <div class="profile-card">
            <div class="profile-avatar-lg">{{ userInitial }}</div>
            <h2 class="profile-name">{{ auth.user.name }}</h2>
            <p class="profile-email">{{ auth.user.email }}</p>
            <span class="role-badge admin">{{ auth.user.role }}</span>
          </div>
        </div>

      </transition>
    </main>

    <!-- MODAL créer/modifier utilisateur -->
    <transition name="modal">
      <div class="modal-overlay" v-if="modal.show" @click.self="modal.show = false">
        <div class="modal">
          <div class="modal-header">
            <h3>{{ modal.mode === 'create' ? 'Ajouter un utilisateur' : 'Modifier l\'utilisateur' }}</h3>
            <button class="modal-close" @click="modal.show = false">✕</button>
          </div>
          <div class="modal-body">
            <div class="field">
              <label>Nom</label>
              <input v-model="modal.form.name" placeholder="Nom complet"/>
            </div>
            <div class="field">
              <label>Email</label>
              <input type="email" v-model="modal.form.email" placeholder="email@exemple.com"/>
            </div>
            <div class="field" v-if="modal.mode === 'create'">
              <label>Mot de passe</label>
              <input type="password" v-model="modal.form.password" placeholder="••••••••"/>
            </div>
            <div class="field">
              <label>Rôle</label>
              <select v-model="modal.form.role">
                <option value="student">Étudiant</option>
                <option value="teacher">Enseignant</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-cancel" @click="modal.show = false">Annuler</button>
            <button class="btn-save" @click="saveUser" :disabled="isSaving">
              {{ isSaving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- MODAL confirmer suppression -->
    <transition name="modal">
      <div class="modal-overlay" v-if="deleteModal.show" @click.self="deleteModal.show = false">
        <div class="modal modal-sm">
          <div class="modal-header danger">
            <h3>Supprimer l'utilisateur</h3>
            <button class="modal-close" @click="deleteModal.show = false">✕</button>
          </div>
          <div class="modal-body">
            <p>Voulez-vous vraiment supprimer <strong>{{ deleteModal.user?.name }}</strong> ? Cette action est irréversible.</p>
          </div>
          <div class="modal-footer">
            <button class="btn-cancel" @click="deleteModal.show = false">Annuler</button>
            <button class="btn-delete" @click="deleteUser" :disabled="isSaving">
              {{ isSaving ? 'Suppression...' : 'Supprimer' }}
            </button>
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
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  auth: Object,  // { user: { id, name, email, role } }
})

const sidebarCollapsed = ref(false)
const activeView       = ref('overview')
const userTab          = ref('students')
const userSearch       = ref('')
const searchQuery      = ref('')
const currentTime      = ref('')
const isSaving         = ref(false)
const users            = ref([])

const toast       = ref({ show: false, msg: '', type: 'success' })
const modal       = ref({ show: false, mode: 'create', form: { name: '', email: '', password: '', role: 'student' }, editId: null })
const deleteModal = ref({ show: false, user: null })

// ── Navigation ───────────────────────────────────────────────────────────────
const svgGrid    = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>`
const svgUsers   = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`
const svgProfile = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`

const navItems = [
  { id: 'overview', label: 'Vue globale', icon: svgGrid },
  { id: 'users',    label: 'Utilisateurs', icon: svgUsers },
  { id: 'profile',  label: 'Mon Profil',   icon: svgProfile },
]

// ── Computed ─────────────────────────────────────────────────────────────────
const userInitial     = computed(() => props.auth?.user?.name?.charAt(0)?.toUpperCase() || 'A')
const students        = computed(() => users.value.filter(u => u.role === 'student'))
const teachers        = computed(() => users.value.filter(u => u.role === 'teacher'))
const admins          = computed(() => users.value.filter(u => u.role === 'admin'))
const recentUsers     = computed(() => [...users.value].slice(-5).reverse())
const studentPct      = computed(() => users.value.length ? (students.value.length / users.value.length) * 100 : 0)
const teacherPct      = computed(() => users.value.length ? (teachers.value.length / users.value.length) * 100 : 0)

const currentPageTitle = computed(() => ({
  overview: 'Vue globale', users: 'Utilisateurs', profile: 'Mon Profil'
})[activeView.value])

const statsCards = computed(() => [
  { label: 'Utilisateurs total', val: users.value.length, pct: 100, trend: 12, color: '#00f5d4', bg: 'rgba(0,245,212,0.1)', icon: svgUsers },
  { label: 'Étudiants', val: students.value.length, pct: studentPct.value, trend: 8,  color: '#6366f1', bg: 'rgba(99,102,241,0.1)', icon: svgUsers },
  { label: 'Enseignants', val: teachers.value.length, pct: teacherPct.value, trend: 5, color: '#f59e0b', bg: 'rgba(245,158,11,0.1)', icon: svgUsers },
])

const userTabs = computed(() => [
  { id: 'students', icon: '🎒', label: 'Étudiants',   count: students.value.length },
  { id: 'teachers', icon: '🧑‍🏫', label: 'Enseignants', count: teachers.value.length },
])

const filteredUsers = computed(() => {
  const src = userTab.value === 'students' ? students.value : teachers.value
  if (!userSearch.value) return src
  const q = userSearch.value.toLowerCase()
  return src.filter(u => u.name?.toLowerCase().includes(q) || u.email?.toLowerCase().includes(q))
})

// ── Helpers ───────────────────────────────────────────────────────────────────
function roleColor(role) {
  return { admin: '#f59e0b', teacher: '#6366f1', student: '#00f5d4' }[role?.toLowerCase()] || '#888'
}

function showToast(msg, type = 'success') {
  toast.value = { show: true, msg, type }
  setTimeout(() => toast.value.show = false, 3000)
}

// ── API calls ─────────────────────────────────────────────────────────────────
async function loadUsers() {
  try {
    const { data } = await axios.get('/api/admin/users')
    users.value = Array.isArray(data) ? data : data.data || []
  } catch {
    showToast('Erreur lors du chargement des utilisateurs', 'error')
  }
}

function openModal(mode, user = null) {
  modal.value = {
    show: true,
    mode,
    editId: user?.id || null,
    form: {
      name:     user?.name     || '',
      email:    user?.email    || '',
      password: '',
      role:     user?.role?.toLowerCase() || 'student',
    },
  }
}

async function saveUser() {
  isSaving.value = true
  try {
    if (modal.value.mode === 'create') {
      await axios.post('/api/admin/users', modal.value.form)
      showToast('Utilisateur créé avec succès')
    } else {
      await axios.put(`/api/admin/users/${modal.value.editId}`, {
        name: modal.value.form.name,
        role: modal.value.form.role,
      })
      showToast('Utilisateur modifié avec succès')
    }
    modal.value.show = false
    await loadUsers()
  } catch {
    showToast('Une erreur est survenue', 'error')
  } finally {
    isSaving.value = false
  }
}

function confirmDelete(user) {
  deleteModal.value = { show: true, user }
}

async function deleteUser() {
  isSaving.value = true
  try {
    await axios.delete(`/api/admin/users/${deleteModal.value.user.id}`)
    showToast('Utilisateur supprimé')
    deleteModal.value.show = false
    await loadUsers()
  } catch {
    showToast('Erreur lors de la suppression', 'error')
  } finally {
    isSaving.value = false
  }
}

// ── Clock ─────────────────────────────────────────────────────────────────────
function tick() {
  currentTime.value = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  loadUsers()
  tick()
  setInterval(tick, 60000)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');
* { box-sizing: border-box; margin: 0; padding: 0; }

.admin-root { display: flex; min-height: 100vh; background: #050b18; font-family: 'DM Sans', sans-serif; color: #fff; }

/* SIDEBAR */
.sidebar { width: 240px; min-height: 100vh; background: rgba(255,255,255,0.03); border-right: 1px solid rgba(255,255,255,0.06); display: flex; flex-direction: column; transition: width 0.3s; flex-shrink: 0; }
.sidebar.collapsed { width: 64px; }
.sidebar-header { display: flex; align-items: center; justify-content: space-between; padding: 1.4rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
.brand { display: flex; align-items: center; gap: 10px; overflow: hidden; }
.brand-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(0,245,212,0.08); border: 1px solid rgba(0,245,212,0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.brand-text { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.1rem; color: #fff; white-space: nowrap; }
.brand-text em { color: #00f5d4; font-style: normal; }
.collapse-btn { background: none; border: none; color: rgba(255,255,255,0.4); cursor: pointer; padding: 4px; border-radius: 6px; display: flex; align-items: center; }
.collapse-btn:hover { color: #fff; background: rgba(255,255,255,0.05); }

.sidebar-nav { flex: 1; padding: 1rem 0.6rem; display: flex; flex-direction: column; gap: 4px; }
.nav-item { display: flex; align-items: center; gap: 12px; padding: 0.7rem 0.9rem; border: none; background: transparent; color: rgba(255,255,255,0.45); font-family: 'DM Sans', sans-serif; font-size: 0.88rem; cursor: pointer; border-radius: 10px; transition: all 0.2s; text-align: left; white-space: nowrap; overflow: hidden; }
.nav-item:hover { color: #fff; background: rgba(255,255,255,0.05); }
.nav-item.active { color: #00f5d4; background: rgba(0,245,212,0.1); }
.nav-icon { flex-shrink: 0; display: flex; align-items: center; }

.sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.admin-info { display: flex; align-items: center; gap: 10px; overflow: hidden; cursor: pointer; }
.admin-avatar { width: 34px; height: 34px; border-radius: 50%; background: #00f5d4; color: #050b18; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; flex-shrink: 0; }
.admin-name { font-size: 0.83rem; font-weight: 500; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.admin-role { font-size: 0.72rem; color: rgba(255,255,255,0.35); text-transform: uppercase; }
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
.search-box input::placeholder { color: rgba(255,255,255,0.25); }
.topbar-time { font-size: 0.82rem; color: rgba(255,255,255,0.35); }

.view { padding: 2rem; overflow-y: auto; flex: 1; animation: fadeUp 0.4s ease; }

/* Stats grid */
.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 1.4rem; animation: fadeUp 0.5s ease both; }
.stat-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
.stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.stat-trend { font-size: 0.75rem; padding: 2px 7px; border-radius: 20px; }
.stat-trend.up   { background: rgba(0,245,212,0.12); color: #00f5d4; }
.stat-trend.down { background: rgba(239,68,68,0.12);  color: #f87171; }
.stat-val   { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 700; color: #fff; }
.stat-label { font-size: 0.8rem; color: rgba(255,255,255,0.4); margin-top: 2px; margin-bottom: 0.8rem; }
.stat-bar   { height: 3px; background: rgba(255,255,255,0.07); border-radius: 2px; }
.stat-bar-fill { height: 100%; border-radius: 2px; transition: width 0.8s ease; }

/* Charts row */
.charts-row { display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; }
.chart-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 1.4rem; }
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.2rem; }
.card-header h3 { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 600; color: #fff; }
.btn-text { background: none; border: none; color: #00f5d4; font-family: 'DM Sans', sans-serif; font-size: 0.82rem; cursor: pointer; transition: opacity 0.2s; }
.btn-text:hover { opacity: 0.7; }

.donut-wrap { position: relative; width: 120px; margin: 0 auto 1rem; }
.donut-svg { transform: rotate(-90deg); }
.donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.donut-total { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 700; color: #fff; }
.donut-label { font-size: 0.72rem; color: rgba(255,255,255,0.35); }
.donut-legend { display: flex; flex-direction: column; gap: 8px; }
.legend-item { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; }
.legend-item span { color: rgba(255,255,255,0.6); flex: 1; }
.legend-item strong { color: #fff; }
.dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

.recent-list { display: flex; flex-direction: column; gap: 10px; }
.recent-item { display: flex; align-items: center; gap: 12px; padding: 0.8rem; border-radius: 12px; background: rgba(255,255,255,0.02); transition: background 0.2s; }
.recent-item:hover { background: rgba(255,255,255,0.05); }
.user-avatar-sm { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: #050b18; flex-shrink: 0; }
.recent-info { flex: 1; overflow: hidden; }
.recent-name  { font-size: 0.85rem; font-weight: 500; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.recent-email { font-size: 0.75rem; color: rgba(255,255,255,0.35); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Users view */
.sub-tabs { display: flex; gap: 8px; margin-bottom: 1.2rem; }
.sub-tab { display: flex; align-items: center; gap: 6px; padding: 0.55rem 1rem; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07); border-radius: 10px; color: rgba(255,255,255,0.45); font-family: 'DM Sans', sans-serif; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; }
.sub-tab:hover { color: #fff; }
.sub-tab.active { border-color: #00f5d4; background: rgba(0,245,212,0.08); color: #00f5d4; }
.sub-tab .count { background: rgba(255,255,255,0.1); border-radius: 20px; padding: 1px 8px; font-size: 0.75rem; }

.table-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; gap: 1rem; }
.search-inline { display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 0.5rem 1rem; flex: 1; max-width: 360px; }
.search-inline input { background: none; border: none; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.85rem; outline: none; width: 100%; }
.search-inline input::placeholder { color: rgba(255,255,255,0.25); }
.btn-add { display: flex; align-items: center; gap: 7px; padding: 0.55rem 1.1rem; background: linear-gradient(135deg, #00f5d4, #00c4aa); border: none; border-radius: 10px; color: #050b18; font-family: 'DM Sans', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
.btn-add:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,245,212,0.25); }

.table-wrap { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 14px; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 0.9rem 1.2rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid rgba(255,255,255,0.05); }
.data-table td { padding: 0.9rem 1.2rem; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.04); }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: rgba(255,255,255,0.02); }
.user-cell { display: flex; align-items: center; gap: 10px; }
.text-muted { color: rgba(255,255,255,0.4); }
.action-btns { display: flex; gap: 6px; }
.icon-btn { width: 30px; height: 30px; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.icon-btn.edit   { background: rgba(99,102,241,0.1); color: #6366f1; }
.icon-btn.delete { background: rgba(239,68,68,0.1);  color: #f87171; }
.icon-btn:hover  { opacity: 0.8; transform: scale(1.1); }

/* Role badges */
.role-badge { padding: 3px 10px; border-radius: 20px; font-size: 0.73rem; font-weight: 600; letter-spacing: 0.03em; }
.role-badge.admin   { background: rgba(245,158,11,0.15); color: #f59e0b; }
.role-badge.teacher { background: rgba(99,102,241,0.15); color: #818cf8; }
.role-badge.student { background: rgba(0,245,212,0.12);  color: #00f5d4; }

/* Profile */
.profile-card { max-width: 400px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 20px; padding: 2.5rem; display: flex; flex-direction: column; align-items: center; gap: 12px; }
.profile-avatar-lg { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #00f5d4, #6366f1); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; color: #050b18; }
.profile-name  { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 700; color: #fff; }
.profile-email { font-size: 0.85rem; color: rgba(255,255,255,0.4); }

/* Empty state */
.empty-state { text-align: center; padding: 3rem; color: rgba(255,255,255,0.3); font-size: 0.88rem; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center; }
.modal { background: #0d1b2e; border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; width: 100%; max-width: 420px; overflow: hidden; box-shadow: 0 40px 80px rgba(0,0,0,0.5); }
.modal-sm { max-width: 360px; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.4rem 1.6rem; border-bottom: 1px solid rgba(255,255,255,0.07); }
.modal-header h3 { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: #fff; }
.modal-header.danger h3 { color: #f87171; }
.modal-close { background: none; border: none; color: rgba(255,255,255,0.35); cursor: pointer; font-size: 1rem; transition: color 0.2s; }
.modal-close:hover { color: #fff; }
.modal-body { padding: 1.4rem 1.6rem; display: flex; flex-direction: column; gap: 1rem; }
.modal-body p { color: rgba(255,255,255,0.6); font-size: 0.88rem; line-height: 1.6; }
.modal-body .field { display: flex; flex-direction: column; gap: 6px; }
.modal-body label { font-size: 0.8rem; color: rgba(255,255,255,0.5); font-weight: 500; }
.modal-body input, .modal-body select { padding: 0.65rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.88rem; outline: none; }
.modal-body input:focus, .modal-body select:focus { border-color: rgba(0,245,212,0.4); }
.modal-body select option { background: #0d1b2e; }
.modal-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 1.2rem 1.6rem; border-top: 1px solid rgba(255,255,255,0.06); }
.btn-cancel { padding: 0.6rem 1.2rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: rgba(255,255,255,0.6); font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all 0.2s; }
.btn-cancel:hover { color: #fff; }
.btn-save   { padding: 0.6rem 1.4rem; background: linear-gradient(135deg, #00f5d4, #00c4aa); border: none; border-radius: 10px; color: #050b18; font-family: 'DM Sans', sans-serif; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-delete { padding: 0.6rem 1.4rem; background: linear-gradient(135deg, #ef4444, #dc2626); border: none; border-radius: 10px; color: #fff; font-family: 'DM Sans', sans-serif; font-weight: 600; cursor: pointer; }
.btn-delete:disabled { opacity: 0.5; }

/* Toast */
.toast-global { position: fixed; bottom: 2rem; right: 2rem; z-index: 200; padding: 0.85rem 1.4rem; border-radius: 12px; font-size: 0.88rem; display: flex; align-items: center; gap: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.3); }
.toast-global.success { background: rgba(0,245,212,0.12); border: 1px solid rgba(0,245,212,0.3); color: #00f5d4; }
.toast-global.error   { background: rgba(239,68,68,0.12);  border: 1px solid rgba(239,68,68,0.3);  color: #f87171; }

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
  .charts-row { grid-template-columns: 1fr; }
}
</style>