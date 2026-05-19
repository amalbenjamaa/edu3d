<!-- resources/js/Pages/Student/SlideView.vue -->
<template>
  <div class="slideview-root">

    <!-- Chargement -->
    <div class="loading-screen" v-if="loading">
      <div class="loading-logo">edu<em>3D</em></div>
      <div class="loading-spinner"></div>
      <div class="loading-msg">Chargement de la scène 3D...</div>
    </div>

    <template v-else-if="slides.length">
      <!-- ── Scène 3D principale ── -->
      <div class="scene-fullscreen">
        <FullScene :slide="currentSlide" :key="'slide-'+currentSlide.id"/>
      </div>

      <!-- ── HUD Top ── -->
      <div class="hud-top">
        <button class="hud-back" @click="goBack" title="Retour à la classe">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>
          </svg>
        </button>
        <div class="hud-info">
          <span class="hud-class">{{ classroom?.name }}</span>
          <span class="hud-title">{{ currentSlide.title }}</span>
        </div>
        <div class="hud-progress-bar">
          <div class="hud-progress-fill" :style="{ width: progressPct + '%' }"></div>
        </div>
        <div class="hud-counter">{{ currentIdx + 1 }} / {{ slides.length }}</div>
      </div>

      <!-- ── HUD Bottom : navigation ── -->
      <div class="hud-bottom">
        <!-- Miniatures des slides -->
        <div class="slide-strip">
          <div v-for="(slide, idx) in slides" :key="slide.id"
            :class="['strip-item', { active: idx === currentIdx }]"
            @click="goTo(idx)">
            <div class="strip-thumb">
              <MiniScene :slide="slide" :key="'strip-'+slide.id"/>
            </div>
            <div class="strip-num">{{ idx + 1 }}</div>
          </div>
        </div>

        <!-- Contrôles navigation -->
        <div class="nav-controls">
          <button class="nav-btn" :disabled="currentIdx === 0" @click="prev">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20">
              <path d="M15 18l-6-6 6-6"/>
            </svg>
          </button>

          <div class="nav-center">
            <div class="nav-slide-title">{{ currentSlide.title }}</div>
            <div class="nav-slide-type">{{ typeLabel(currentSlide.type) }}</div>
          </div>

          <button class="nav-btn" :disabled="currentIdx === slides.length - 1" @click="next">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20">
              <path d="M9 18l6-6-6-6"/>
            </svg>
          </button>
        </div>

        <!-- Progression étudiant -->
        <div class="progress-section">
          <div class="progress-label">Progression</div>
          <div class="progress-track">
            <div class="progress-fill" :style="{ width: enrollment?.progress + '%' ?? '0%' }"></div>
          </div>
          <div class="progress-pct">{{ enrollment?.progress ?? 0 }}%</div>
        </div>
      </div>

      <!-- ── Overlay info objet (click sur la scène) ── -->
      <div class="slide-meta-badge" v-if="currentSlide.content?.length">
        <span>{{ currentSlide.content.length }} objet(s) · Drag pour orbiter · Scroll pour zoomer</span>
      </div>

      <!-- ── Badge durée ── -->
      <div class="duration-badge" v-if="currentSlide.duration > 0">
        <span>⏱ {{ currentSlide.duration }}s</span>
      </div>

      <!-- Transition vers slide suivante : auto si durée définie -->
      <div class="auto-progress" v-if="autoTimer > 0">
        <div class="auto-progress-bar" :style="{ width: autoTimerPct + '%' }"></div>
      </div>
    </template>

    <!-- Pas de slides -->
    <div class="empty-slideview" v-else-if="!loading">
      <div class="empty-icon">🎞️</div>
      <h3>{{ loadError || 'Aucune slide dans cette classe' }}</h3>
      <p v-if="loadError">Vérifiez que vous êtes inscrit à cette classe et reconnectez-vous si besoin.</p>
      <p v-else>Le contenu 3D sera bientôt disponible.</p>
      <button class="btn-back" @click="goBack">← Retour</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import FullScene from '@/Components/three/FullScene.vue'
import MiniScene from '@/Components/three/MiniScene.vue'

const props = defineProps({
  classroomId: [String, Number],
  slideId:     [String, Number],
  auth:        Object,
})

// ── État ──────────────────────────────────────────────────────────────────────
const loading     = ref(true)
const loadError   = ref('')
const slides      = ref([])
const classroom   = ref(null)
const enrollment  = ref(null)
const currentIdx  = ref(0)
const autoTimer   = ref(0)
const autoTimerPct = ref(100)
let timerInterval = null

// ── Computed ──────────────────────────────────────────────────────────────────
const currentSlide = computed(() => slides.value[currentIdx.value] ?? {})
const progressPct  = computed(() =>
  slides.value.length ? Math.round(((currentIdx.value) / slides.value.length) * 100) : 0
)

function typeLabel(t) {
  return { text3d: 'Texte 3D', shape3d: 'Formes 3D', image3d: 'Image 3D', model3d: 'Modèle', mixed: 'Mixte' }[t] ?? t
}

// ── Navigation ────────────────────────────────────────────────────────────────
async function goTo(idx) {
  if (idx < 0 || idx >= slides.value.length) return
  clearAutoTimer()
  currentIdx.value = idx
  await updateProgress()
  startAutoTimer()
}

function prev() { goTo(currentIdx.value - 1) }
function next() { goTo(currentIdx.value + 1) }
function goBack() { router.visit(route('student.dashboard')) }

// ── Clavier ───────────────────────────────────────────────────────────────────
function onKeyDown(e) {
  if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next()
  if (e.key === 'ArrowLeft'  || e.key === 'ArrowUp')   prev()
}

// ── Auto timer ────────────────────────────────────────────────────────────────
function startAutoTimer() {
  const duration = currentSlide.value?.duration ?? 0
  if (!duration) return
  autoTimer.value = duration
  autoTimerPct.value = 100
  const step = 100
  const decrement = 100 / (duration * (1000 / step))
  timerInterval = setInterval(() => {
    autoTimerPct.value -= decrement
    if (autoTimerPct.value <= 0) {
      clearAutoTimer()
      if (currentIdx.value < slides.value.length - 1) next()
    }
  }, step)
}
function clearAutoTimer() {
  clearInterval(timerInterval)
  autoTimer.value = 0
  autoTimerPct.value = 100
}

// ── API ───────────────────────────────────────────────────────────────────────
async function load() {
  loading.value = true
  loadError.value = ''
  try {
    // Charger les slides de la classe
    const [slidesRes, classRes] = await Promise.all([
      axios.get(`/api/classrooms/${props.classroomId}/slides`),
      axios.get(`/api/classrooms/${props.classroomId}`),
    ])
    slides.value = (slidesRes.data.data || slidesRes.data).sort((a, b) => a.order - b.order)
    classroom.value = classRes.data.classroom || classRes.data

    // Trouver l'index initial (si slideId fourni)
    if (props.slideId) {
      const idx = slides.value.findIndex(s => s.id == props.slideId)
      if (idx >= 0) currentIdx.value = idx
    }

    // Chercher l'inscription de l'étudiant
    try {
      const enrollRes = await axios.get('/api/enrollments')
      const enrollments = enrollRes.data.data || enrollRes.data
      enrollment.value = enrollments.find(e => e.classroom?.id == props.classroomId) ?? null
    } catch (_) {}

    await updateProgress()
    startAutoTimer()
  } catch (e) {
    console.error('Erreur chargement slides', e)
    const status = e.response?.status
    if (status === 401) loadError.value = 'Session expirée — reconnectez-vous'
    else if (status === 403) loadError.value = 'Accès refusé à cette classe'
    else loadError.value = e.response?.data?.message || 'Impossible de charger les slides'
    slides.value = []
  } finally {
    loading.value = false
  }
}

async function updateProgress() {
  const slide = currentSlide.value
  if (!slide?.id || !enrollment.value?.id) return
  try {
    const res = await axios.put(`/api/enrollments/${enrollment.value.id}/progress`, {
      slide_id: slide.id,
    })
    enrollment.value = res.data.enrollment
  } catch (_) {}
}

onMounted(() => {
  load()
  window.addEventListener('keydown', onKeyDown)
})
onBeforeUnmount(() => {
  clearAutoTimer()
  window.removeEventListener('keydown', onKeyDown)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');
* { box-sizing: border-box; margin: 0; padding: 0; }

.slideview-root {
  position: fixed; inset: 0;
  background: #030712;
  font-family: 'DM Sans', sans-serif;
  color: #fff;
  overflow: hidden;
}

/* ── LOADING ── */
.loading-screen {
  position: absolute; inset: 0; z-index: 50;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 1.5rem;
  background: #030712;
}
.loading-logo { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; }
.loading-logo em { color: #6366f1; font-style: normal; }
.loading-spinner {
  width: 40px; height: 40px;
  border: 2px solid rgba(255,255,255,0.1);
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
.loading-msg { font-size: 0.85rem; color: rgba(255,255,255,0.35); }

/* ── SCENE ── */
.scene-fullscreen {
  position: absolute; inset: 0;
}

/* ── HUD TOP ── */
.hud-top {
  position: absolute; top: 0; left: 0; right: 0;
  display: flex; align-items: center; gap: 12px;
  padding: 1rem 1.5rem;
  background: linear-gradient(to bottom, rgba(3,7,18,0.9) 0%, transparent 100%);
  pointer-events: auto;
  z-index: 10;
}
.hud-back {
  width: 36px; height: 36px;
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);
  border-radius: 10px; color: rgba(255,255,255,0.7);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.2s; flex-shrink: 0;
}
.hud-back:hover { background: rgba(255,255,255,0.12); color: #fff; }
.hud-info { flex: 1; overflow: hidden; }
.hud-class { display: block; font-size: 0.72rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.06em; }
.hud-title { display: block; font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.hud-progress-bar {
  width: 120px; height: 3px;
  background: rgba(255,255,255,0.1); border-radius: 2px;
  overflow: hidden; flex-shrink: 0;
}
.hud-progress-fill { height: 100%; background: #6366f1; border-radius: 2px; transition: width 0.5s ease; }
.hud-counter { font-size: 0.78rem; color: rgba(255,255,255,0.4); white-space: nowrap; flex-shrink: 0; }

/* ── HUD BOTTOM ── */
.hud-bottom {
  position: absolute; bottom: 0; left: 0; right: 0;
  background: linear-gradient(to top, rgba(3,7,18,0.95) 0%, transparent 100%);
  padding: 1.5rem;
  z-index: 10;
  display: flex; flex-direction: column; gap: 1rem;
}

/* Strip de slides */
.slide-strip {
  display: flex; gap: 8px;
  overflow-x: auto;
  padding-bottom: 4px;
  scrollbar-width: none;
}
.slide-strip::-webkit-scrollbar { display: none; }
.strip-item {
  flex-shrink: 0;
  width: 80px;
  cursor: pointer;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid rgba(255,255,255,0.08);
  transition: border-color 0.2s, transform 0.2s;
}
.strip-item:hover { transform: translateY(-2px); border-color: rgba(99,102,241,0.4); }
.strip-item.active { border-color: #6366f1; transform: translateY(-4px); }
.strip-thumb { height: 50px; background: #0a0d1a; overflow: hidden; }
.strip-num { text-align: center; font-size: 0.65rem; color: rgba(255,255,255,0.4); padding: 2px 0; background: rgba(0,0,0,0.4); }
.strip-item.active .strip-num { color: #6366f1; }

/* Contrôles nav */
.nav-controls {
  display: flex; align-items: center; gap: 1.5rem;
}
.nav-btn {
  width: 44px; height: 44px;
  background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
  border-radius: 50%; color: rgba(255,255,255,0.7);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.2s; flex-shrink: 0;
}
.nav-btn:hover:not(:disabled) { background: rgba(99,102,241,0.2); border-color: rgba(99,102,241,0.4); color: #fff; }
.nav-btn:disabled { opacity: 0.25; cursor: not-allowed; }
.nav-center { flex: 1; text-align: center; }
.nav-slide-title { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 600; color: #fff; }
.nav-slide-type  { font-size: 0.72rem; color: rgba(255,255,255,0.35); margin-top: 2px; }

/* Progress */
.progress-section { display: flex; align-items: center; gap: 10px; }
.progress-label { font-size: 0.72rem; color: rgba(255,255,255,0.35); white-space: nowrap; }
.progress-track { flex: 1; height: 4px; background: rgba(255,255,255,0.08); border-radius: 2px; overflow: hidden; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #00f5d4); border-radius: 2px; transition: width 0.8s ease; }
.progress-pct { font-size: 0.72rem; color: rgba(255,255,255,0.4); white-space: nowrap; }

/* Meta badge */
.slide-meta-badge {
  position: absolute; left: 50%; transform: translateX(-50%);
  top: 80px; z-index: 10;
  background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px; padding: 4px 14px;
  font-size: 0.72rem; color: rgba(255,255,255,0.4);
  pointer-events: none;
  animation: fadeInOut 4s ease forwards;
}
@keyframes fadeInOut {
  0%   { opacity: 0; transform: translateX(-50%) translateY(-5px); }
  15%  { opacity: 1; transform: translateX(-50%) translateY(0); }
  75%  { opacity: 1; }
  100% { opacity: 0; }
}

.duration-badge {
  position: absolute; top: 80px; right: 1.5rem; z-index: 10;
  background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px; padding: 4px 12px;
  font-size: 0.72rem; color: rgba(255,255,255,0.5);
}

/* Auto progress bar */
.auto-progress {
  position: absolute; bottom: 0; left: 0; right: 0; z-index: 20;
  height: 2px; background: rgba(255,255,255,0.05);
}
.auto-progress-bar { height: 100%; background: #6366f1; transition: width 0.1s linear; }

/* Empty */
.empty-slideview {
  position: absolute; inset: 0;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 1rem; text-align: center; padding: 2rem;
}
.empty-icon { font-size: 3rem; }
.empty-slideview h3 { font-family: 'Syne', sans-serif; font-size: 1.2rem; }
.empty-slideview p { color: rgba(255,255,255,0.4); font-size: 0.88rem; }
.btn-back {
  padding: 0.7rem 1.5rem;
  background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3);
  border-radius: 10px; color: #818cf8;
  font-size: 0.88rem; cursor: pointer; transition: all 0.2s;
}
.btn-back:hover { background: rgba(99,102,241,0.25); }

@keyframes spin { to { transform: rotate(360deg); } }
</style>