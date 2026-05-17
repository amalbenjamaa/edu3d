<template>
  <div class="student-root">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-icon">🎓</div>
        <div>
          <div class="brand-title">EDU3D</div>
          <div class="brand-subtitle">Espace étudiant</div>
        </div>
      </div>

      <nav class="nav">
        <button
          v-for="item in navItems"
          :key="item.id"
          :class="['nav-item', { active: activeView === item.id }]"
          @click="activeView = item.id"
        >
          {{ item.label }}
          <span v-if="item.badge" class="nav-badge">{{ item.badge }}</span>
        </button>
      </nav>

      <Link :href="route('logout')" method="post" as="button" class="logout-btn">
        Déconnexion
      </Link>
    </aside>

    <main class="main">
      <section v-if="activeView === 'overview'" class="hero">
        <p class="eyebrow">Bienvenue</p>
        <h1>{{ auth?.user?.name || 'Étudiant' }}</h1>
        <p class="lead">Accédez à vos cours 3D et échangez avec vos enseignants.</p>
        <div class="quick-links">
          <Link href="/student/my-courses" class="quick-link">Mes cours →</Link>
          <button class="quick-link" @click="activeView = 'messages'">Messages →</button>
        </div>
      </section>

      <section v-else-if="activeView === 'courses'" class="view-panel">
        <h2 class="panel-title">Catalogue</h2>
        <p class="panel-sub">Parcourez les cours disponibles depuis la page catalogue.</p>
        <Link href="/student/courses" class="quick-link">Ouvrir le catalogue →</Link>
      </section>

      <section v-else-if="activeView === 'my-courses'" class="view-panel">
        <h2 class="panel-title">Mes cours</h2>
        <Link href="/student/my-courses" class="quick-link">Voir mes inscriptions →</Link>
      </section>

      <section v-else-if="activeView === 'messages'" class="view view-full view-chat">
        <ChatView :is-teacher="false" />
      </section>

      <section v-else-if="activeView === 'profile'" class="view-panel">
        <h2 class="panel-title">Mon profil</h2>
        <p class="panel-sub">{{ auth?.user?.email }}</p>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ChatView from './ChatView.vue'

defineProps({
  auth: Object,
})

const activeView = ref('overview')
const unreadCount = ref(0)

const navItems = computed(() => [
  { id: 'overview', label: 'Vue globale' },
  { id: 'my-courses', label: 'Mes cours' },
  { id: 'courses', label: 'Catalogue' },
  { id: 'messages', label: 'Messages', badge: unreadCount.value || null },
  { id: 'profile', label: 'Mon profil' },
])
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;700&display=swap');

:global(*) { box-sizing: border-box; }

.student-root {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 260px 1fr;
  background: #050b18;
  color: #fff;
  font-family: 'DM Sans', sans-serif;
}

.sidebar {
  padding: 1.5rem;
  border-right: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.03);
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.brand { display: flex; align-items: center; gap: 0.9rem; }
.brand-icon {
  width: 44px; height: 44px; border-radius: 14px;
  display: grid; place-items: center;
  background: rgba(0, 245, 212, 0.12);
  border: 1px solid rgba(0, 245, 212, 0.22);
}
.brand-title { font-family: 'Syne', sans-serif; font-weight: 800; letter-spacing: 0.04em; }
.brand-subtitle { font-size: 0.8rem; color: rgba(255, 255, 255, 0.45); }

.nav { display: grid; gap: 0.6rem; }
.nav-item, .logout-btn {
  width: 100%; border: 0; border-radius: 12px;
  padding: 0.85rem 1rem; text-align: left;
  font: inherit; color: inherit;
  background: rgba(255, 255, 255, 0.04);
  cursor: pointer;
  display: flex; align-items: center; justify-content: space-between;
}
.nav-item.active { background: rgba(0, 245, 212, 0.12); color: #00f5d4; }
.nav-badge {
  min-width: 18px; height: 18px; padding: 0 5px;
  border-radius: 9px; background: #00f5d4; color: #050b18;
  font-size: 0.65rem; font-weight: 700;
}
.logout-btn {
  margin-top: auto;
  background: rgba(239, 68, 68, 0.12);
  color: #fca5a5;
  justify-content: center;
}

.main { padding: 2rem; overflow: hidden; display: flex; flex-direction: column; min-height: 100vh; }

.hero {
  max-width: 680px; padding: 2rem; border-radius: 24px;
  background: linear-gradient(135deg, rgba(0, 245, 212, 0.08), rgba(99, 102, 241, 0.06));
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.eyebrow { margin: 0 0 0.5rem; color: rgba(255, 255, 255, 0.55); text-transform: uppercase; letter-spacing: 0.12em; font-size: 0.75rem; }
h1 { margin: 0; font-family: 'Syne', sans-serif; font-size: clamp(2rem, 4vw, 3.2rem); }
.lead { margin: 0.8rem 0 0; max-width: 56ch; color: rgba(255, 255, 255, 0.62); line-height: 1.7; }

.quick-links { display: flex; flex-wrap: wrap; gap: 0.8rem; margin-top: 1.2rem; }
.quick-link {
  padding: 0.55rem 1rem; border-radius: 10px;
  background: rgba(0, 245, 212, 0.1); border: 1px solid rgba(0, 245, 212, 0.25);
  color: #00f5d4; font-size: 0.88rem; text-decoration: none; cursor: pointer;
}

.view-panel { padding: 1rem 0; }
.panel-title { font-family: 'Syne', sans-serif; font-size: 1.4rem; margin-bottom: 0.5rem; }
.panel-sub { color: rgba(255,255,255,0.5); margin-bottom: 1rem; }

.view-full { flex: 1; padding: 0; overflow: hidden; min-height: calc(100vh - 4rem); }
.view-chat { display: flex; flex-direction: column; }

@media (max-width: 900px) {
  .student-root { grid-template-columns: 1fr; }
  .sidebar { border-right: 0; border-bottom: 1px solid rgba(255, 255, 255, 0.06); }
}
</style>
