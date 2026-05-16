<template>
  <StudentLayout>
    <div class="classroom-view">

      <!-- Header -->
      <div class="page-header">
        <button class="back-btn" @click="router.visit('/student/dashboard')">← Retour</button>
        <div class="header-info">
          <h1 class="page-title">{{ classroom?.name ?? 'Classe' }}</h1>
          <p class="page-sub">{{ classroom?.course?.title ?? '' }}</p>
        </div>
        <!-- Progress -->
        <div class="progress-chip" v-if="enrollment">
          <div class="progress-ring-wrap">
            <svg viewBox="0 0 36 36" width="40" height="40">
              <circle cx="18" cy="18" r="15" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="3"/>
              <circle cx="18" cy="18" r="15" fill="none" stroke="#00f5d4" stroke-width="3"
                :stroke-dasharray="`${enrollment.progress * 0.94} 94`"
                stroke-dashoffset="23" stroke-linecap="round"/>
            </svg>
            <span class="progress-pct">{{ enrollment.progress }}%</span>
          </div>
          <span class="progress-label">Progression</span>
        </div>
      </div>

      <!-- Loading -->
      <div class="loading-state" v-if="loading">
        <div class="loader-ring"></div>
        <span>Chargement…</span>
      </div>

      <!-- Slide grid -->
      <div class="slides-grid" v-else-if="slides.length">
        <div
          v-for="(slide, i) in slides"
          :key="slide.id"
          class="slide-card"
          @click="openViewer(i)"
        >
          <!-- Mini 3D preview -->
          <div class="card-preview">
            <MiniScene :slide="slide" />
            <span class="slide-badge">{{ i + 1 }}</span>
            <div class="card-overlay">
              <span class="play-btn">▶ Voir en 3D</span>
            </div>
          </div>
          <div class="card-info">
            <h3 class="card-title">{{ slide.title }}</h3>
            <div class="card-meta">
              <span class="type-badge">{{ typeLabel(slide.type) }}</span>
              <span class="obj-count">{{ slide.content?.length ?? 0 }} objet(s)</span>
            </div>
          </div>
        </div>
      </div>

      <div class="empty-state" v-else>
        <p>Aucune slide disponible pour cette classe.</p>
      </div>

      <!-- Full 3D viewer modal -->
      <Teleport to="body">
        <transition name="viewer-modal">
          <div class="viewer-modal" v-if="viewerOpen">
            <ThreeScene
              :slides="slides"
              :initial-index="viewerIndex"
              :on-close="closeViewer"
              @progress="onProgress"
            />
          </div>
        </transition>
      </Teleport>

    </div>
  </StudentLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import ThreeScene from '@/Components/ThreeScene.vue'
import MiniScene from '@/Components/three/MiniScene.vue'
import axios from 'axios'

const props = defineProps({
  classroomId: [String, Number],
})

const classroom   = ref(null)
const slides      = ref([])
const enrollment  = ref(null)
const loading     = ref(true)
const viewerOpen  = ref(false)
const viewerIndex = ref(0)

async function loadData() {
  loading.value = true
  try {
    const [slidesRes, enrollRes] = await Promise.all([
      axios.get(`/api/classrooms/${props.classroomId}/slides`),
      axios.get('/api/enrollments'),
    ])

    slides.value = slidesRes.data.data ?? slidesRes.data

    const enrollments = enrollRes.data.data ?? enrollRes.data
    enrollment.value  = enrollments.find(e =>
      String(e.classroom?.id ?? e.classroom_id) === String(props.classroomId)
    ) ?? null

    // Load classroom info via first slide or enrollment
    if (enrollment.value?.classroom) {
      classroom.value = enrollment.value.classroom
    }
  } catch {
    // silent
  } finally {
    loading.value = false
  }
}

function openViewer(index) {
  const slide = slides.value[index]
  if (!slide) return
  router.visit(`/student/classrooms/${props.classroomId}/slides/${slide.id}`)
}

function closeViewer() {
  viewerOpen.value = false
  document.body.style.overflow = ''
}

async function onProgress({ slideId }) {
  if (!enrollment.value) return
  try {
    const res = await axios.put(`/api/enrollments/${enrollment.value.id}/progress`, {
      slide_id: slideId,
    })
    if (enrollment.value) {
      enrollment.value.progress = res.data.enrollment?.progress ?? enrollment.value.progress
    }
  } catch { /* silent */ }
}

function typeLabel(type) {
  return { text3d: 'Texte 3D', shape3d: 'Formes', image3d: 'Image', model3d: 'Modèle', mixed: 'Mixte' }[type] ?? type
}

onMounted(() => loadData())
</script>

<style scoped>
.classroom-view {
  padding: 2rem;
  min-height: calc(100vh - 64px);
  background: #050b18;
  color: #fff;
  font-family: 'DM Sans', sans-serif;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 1.2rem;
  margin-bottom: 2rem;
}

.back-btn {
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.09);
  border-radius: 9px;
  color: rgba(255,255,255,0.6);
  font-family: 'DM Sans', sans-serif;
  font-size: 0.83rem;
  padding: 0.45rem 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.back-btn:hover { color: #fff; background: rgba(255,255,255,0.09); }

.header-info { flex: 1; }
.page-title {
  font-family: 'Syne', sans-serif;
  font-size: 1.4rem;
  font-weight: 700;
  color: #fff;
}
.page-sub { font-size: 0.83rem; color: rgba(255,255,255,0.4); margin-top: 2px; }

.progress-chip {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.progress-ring-wrap {
  position: relative;
  width: 40px; height: 40px;
  display: flex; align-items: center; justify-content: center;
}
.progress-pct {
  position: absolute;
  font-size: 0.62rem;
  font-weight: 700;
  color: #00f5d4;
}
.progress-label { font-size: 0.68rem; color: rgba(255,255,255,0.35); }

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 4rem 0;
  color: rgba(255,255,255,0.4);
  font-size: 0.88rem;
}
.loader-ring {
  width: 40px; height: 40px;
  border: 3px solid rgba(255,255,255,0.08);
  border-top-color: #00f5d4;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.slides-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.2rem;
}

.slide-card {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 16px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.25s;
}
.slide-card:hover {
  border-color: rgba(0,245,212,0.3);
  transform: translateY(-3px);
  box-shadow: 0 12px 32px rgba(0,245,212,0.08);
}

.card-preview {
  position: relative;
  height: 180px;
  background: #0a0f1e;
  overflow: hidden;
}

.slide-badge {
  position: absolute;
  top: 8px; left: 8px;
  background: rgba(0,0,0,0.6);
  color: rgba(255,255,255,0.7);
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 20px;
  backdrop-filter: blur(4px);
}

.card-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,245,212,0);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.25s;
}
.slide-card:hover .card-overlay { background: rgba(0,245,212,0.06); }

.play-btn {
  background: rgba(0,0,0,0.65);
  color: #fff;
  font-size: 0.82rem;
  padding: 0.5rem 1.1rem;
  border-radius: 20px;
  backdrop-filter: blur(8px);
  opacity: 0;
  transition: opacity 0.25s;
  border: 1px solid rgba(255,255,255,0.15);
}
.slide-card:hover .play-btn { opacity: 1; }

.card-info { padding: 0.9rem 1rem; }
.card-title {
  font-family: 'Syne', sans-serif;
  font-size: 0.92rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.card-meta { display: flex; align-items: center; gap: 8px; }
.type-badge {
  font-size: 0.68rem;
  padding: 2px 7px;
  border-radius: 6px;
  background: rgba(99,102,241,0.12);
  color: #818cf8;
}
.obj-count { font-size: 0.72rem; color: rgba(255,255,255,0.3); }

.empty-state {
  text-align: center;
  padding: 4rem;
  color: rgba(255,255,255,0.3);
  font-size: 0.88rem;
}

/* Full viewer modal */
.viewer-modal {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: #050b18;
}

.viewer-modal-enter-active, .viewer-modal-leave-active {
  transition: opacity 0.3s ease;
}
.viewer-modal-enter-from, .viewer-modal-leave-to {
  opacity: 0;
}
</style>