<template>
  <transition name="modal">
    <div class="overlay" v-if="show" @click.self="$emit('close')">
      <div class="editor-root">

        <!-- ── HEADER ── -->
        <div class="editor-header">
          <div class="header-left">
            <div class="header-icon">🗂️</div>
            <div>
              <h2 class="header-title">Éditeur de Slides 3D</h2>
              <p class="header-sub">{{ course?.title }} · {{ slides.length }} slide(s)</p>
            </div>
          </div>
          <div class="header-right">
            <button class="btn-add-slide" @click="openAddForm">
              <span>＋</span> Nouvelle slide
            </button>
            <button class="close-btn" @click="$emit('close')">✕</button>
          </div>
        </div>

        <!-- ── BODY ── -->
        <div class="editor-body">

          <!-- COLONNE GAUCHE : liste des slides -->
          <div class="slides-panel">
            <div class="slides-panel-header">
              <span>Slides ({{ slides.length }})</span>
            </div>

            <div class="slides-list" v-if="slides.length">
              <div
                v-for="(slide, i) in slides"
                :key="slide.id"
                :class="['slide-item', { active: activeSlide?.id === slide.id }]"
                @click="selectSlide(slide)"
              >
                <div class="slide-item-num">#{{ slide.position ?? i }}</div>
                <div class="slide-item-info">
                  <div class="slide-item-title">{{ slide.title }}</div>
                  <div class="slide-item-preview">
                    {{ slide.contentText?.substring(0, 40) || 'Aucun contenu' }}
                  </div>
                </div>
                <div class="slide-item-badges">
                  <span class="badge-3d" v-if="slide.object3dUrl">🧊</span>
                </div>
                <div class="slide-item-actions" @click.stop>
                  <button class="act-btn edit" @click="openEditForm(slide)" title="Modifier">✏️</button>
                  <button class="act-btn del" @click="confirmDelete(slide)" title="Supprimer">🗑️</button>
                </div>
              </div>
            </div>

            <div class="slides-empty" v-else>
              <div style="font-size:2.5rem">🗂️</div>
              <p>Aucune slide</p>
              <button class="btn-add-slide small" @click="openAddForm">+ Créer la première slide</button>
            </div>

            <!-- Ordre des slides -->
            <div class="reorder-hint" v-if="slides.length > 1">
              💡 Cliquez sur une slide pour la prévisualiser
            </div>
          </div>

          <!-- COLONNE CENTRE : préview 3D -->
          <div class="preview-panel">
            <div class="preview-header">
              <div class="preview-title" v-if="activeSlide">
                <span class="preview-badge">#{{ activeSlide.position ?? 0 }}</span>
                {{ activeSlide.title }}
              </div>
              <div class="preview-title muted" v-else>← Sélectionnez une slide</div>
              <div class="preview-nav" v-if="slides.length > 0">
                <button class="nav-arrow" :disabled="!prevSlide" @click="goToPrev">‹</button>
                <span class="nav-count">{{ activeIndex + 1 }} / {{ slides.length }}</span>
                <button class="nav-arrow" :disabled="!nextSlide" @click="goToNext">›</button>
              </div>
            </div>

            <!-- Viewer 3D -->
            <div class="viewer-area">
              <ThreeViewer
                v-if="activeSlide?.object3dUrl"
                :key="activeSlide.id"
                :modelUrl="activeSlide.object3dUrl"
                :autoRotate="true"
              />
              <div class="viewer-placeholder" v-else>
                <svg viewBox="0 0 120 120" width="90" height="90" style="opacity:0.2">
                  <polygon points="60,8 110,88 10,88" fill="none" stroke="#a78bfa" stroke-width="2"/>
                  <polygon points="60,25 95,82 25,82" fill="rgba(167,139,250,0.1)" stroke="rgba(167,139,250,0.3)" stroke-width="1.5"/>
                  <circle cx="60" cy="55" r="7" fill="rgba(167,139,250,0.3)" stroke="#a78bfa" stroke-width="1.5"/>
                </svg>
                <p>{{ activeSlide ? 'Pas de modèle 3D pour cette slide' : 'Sélectionnez une slide' }}</p>
              </div>
            </div>

            <!-- Infos slide active -->
            <div class="slide-info-bar" v-if="activeSlide">
              <div class="info-content">
                <p class="info-text">{{ activeSlide.contentText || 'Aucun contenu textuel' }}</p>
              </div>
              <div class="info-dots">
                <span
                  v-for="(s, i) in slides"
                  :key="s.id"
                  :class="['dot', { active: s.id === activeSlide?.id }]"
                  @click="selectSlide(s)"
                ></span>
              </div>
            </div>
          </div>

          <!-- COLONNE DROITE : formulaire ajout/édition -->
          <transition name="form-slide">
            <div class="form-panel" v-if="formOpen">
              <div class="form-panel-header">
                <h3>{{ editMode ? '✏️ Modifier la slide' : '＋ Nouvelle slide' }}</h3>
                <button class="close-form-btn" @click="closeForm">✕</button>
              </div>

              <div class="form-body">

                <div class="field-group">
                  <label>Titre <span class="req">*</span></label>
                  <input v-model="form.title" class="f-input" placeholder="Ex: Introduction aux formes 3D"/>
                </div>

                <div class="field-group">
                  <label>Position (ordre)</label>
                  <input v-model.number="form.position" type="number" min="0" class="f-input small" placeholder="0"/>
                </div>

                <div class="field-group">
                  <label>Contenu textuel</label>
                  <textarea v-model="form.contentText" class="f-textarea" rows="4" placeholder="Description, explications, notes..."></textarea>
                </div>

                <!-- Modèle 3D -->
                <div class="field-group">
                  <label>Modèle 3D (.glb / .gltf)</label>
                  <div class="model-tabs">
                    <button :class="['mtab', { active: modelMode === 'url' }]" @click="modelMode = 'url'">🔗 URL</button>
                    <button :class="['mtab', { active: modelMode === 'demo' }]" @click="modelMode = 'demo'">⚡ Démo</button>
                    <button :class="['mtab', { active: modelMode === 'none' }]" @click="modelMode = 'none'; form.object3dUrl = ''">✕ Aucun</button>
                  </div>

                  <div v-if="modelMode === 'url'" class="url-wrap">
                    <input
                      v-model="form.object3dUrl"
                      class="f-input"
                      placeholder="https://exemple.com/model.glb"
                    />
                    <div class="url-preview" v-if="form.object3dUrl">
                      <span class="url-check">✅ URL définie</span>
                    </div>
                  </div>

                  <div v-if="modelMode === 'demo'" class="demo-grid">
                    <button
                      v-for="m in demoModels"
                      :key="m.name"
                      :class="['demo-btn', { active: form.object3dUrl === m.url }]"
                      @click="form.object3dUrl = m.url"
                    >
                      {{ m.icon }} {{ m.name }}
                    </button>
                  </div>
                </div>

                <!-- Mini preview dans le formulaire -->
                <div class="mini-viewer-wrap" v-if="form.object3dUrl">
                  <div class="mini-viewer-label">Aperçu</div>
                  <div class="mini-viewer">
                    <ThreeViewer :key="form.object3dUrl" :modelUrl="form.object3dUrl" :autoRotate="true"/>
                  </div>
                </div>

              </div>

              <div class="form-footer">
                <button class="btn-cancel" @click="closeForm">Annuler</button>
                <button class="btn-save" @click="saveSlide" :disabled="isLoading || !form.title">
                  <span v-if="!isLoading">{{ editMode ? '💾 Sauvegarder' : '✚ Créer la slide' }}</span>
                  <div v-else class="spin"></div>
                </button>
              </div>
            </div>
          </transition>

        </div>

        <!-- MODAL DELETE CONFIRM -->
        <transition name="fade">
          <div class="delete-overlay" v-if="deleteTarget" @click.self="deleteTarget = null">
            <div class="delete-box">
              <div style="font-size:2.5rem;margin-bottom:0.8rem">🗑️</div>
              <h4>Supprimer cette slide ?</h4>
              <p>« {{ deleteTarget?.title }} » sera supprimée définitivement.</p>
              <div class="delete-actions">
                <button class="btn-cancel" @click="deleteTarget = null">Annuler</button>
                <button class="btn-del-confirm" @click="doDelete" :disabled="isLoading">
                  <span v-if="!isLoading">Supprimer</span>
                  <div v-else class="spin"></div>
                </button>
              </div>
            </div>
          </div>
        </transition>

        <!-- TOAST -->
        <transition name="toast">
          <div class="toast" v-if="toast.show" :class="toast.type">
            {{ toast.type === 'success' ? '✅' : '❌' }} {{ toast.msg }}
          </div>
        </transition>

      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import api from '@/services/api.js'
import ThreeViewer from '@/Components/ThreeViewer.vue'

const props = defineProps({
  show:   { type: Boolean, default: false },
  course: { type: Object,  default: null  },
})
const emit = defineEmits(['close', 'updated'])

// ── State ─────────────────────────────────────────────────
const slides       = ref([])
const classroomId  = ref(null)
const activeSlide  = ref(null)
const isLoading   = ref(false)
const formOpen    = ref(false)
const editMode    = ref(false)
const editId      = ref(null)
const deleteTarget = ref(null)
const modelMode   = ref('url')
const toast       = ref({ show: false, msg: '', type: 'success' })

const form = ref({
  title:       '',
  contentText: '',
  object3dUrl: '',
  position:    0,
})

const demoModels = [
  { name: 'Canard',      icon: '🦆', url: 'https://raw.githubusercontent.com/KhronosGroup/glTF-Sample-Models/master/2.0/Duck/glTF-Binary/Duck.glb' },
  { name: 'Casque',      icon: '⛑️', url: 'https://raw.githubusercontent.com/KhronosGroup/glTF-Sample-Models/master/2.0/DamagedHelmet/glTF-Binary/DamagedHelmet.glb' },
  { name: 'Bouteille',   icon: '🍶', url: 'https://raw.githubusercontent.com/KhronosGroup/glTF-Sample-Models/master/2.0/WaterBottle/glTF-Binary/WaterBottle.glb' },
  { name: 'Flamant',     icon: '🦩', url: 'https://threejs.org/examples/models/gltf/Flamingo.glb' },
  { name: 'Cheval',      icon: '🐴', url: 'https://threejs.org/examples/models/gltf/Horse.glb' },
  { name: 'Robot',       icon: '🤖', url: 'https://threejs.org/examples/models/gltf/RobotExpressive/RobotExpressive.glb' },
  { name: 'Boîte',       icon: '📦', url: 'https://raw.githubusercontent.com/KhronosGroup/glTF-Sample-Models/master/2.0/Box/glTF-Binary/Box.glb' },
]

// ── Computed ──────────────────────────────────────────────
const activeIndex = computed(() =>
  slides.value.findIndex(s => s.id === activeSlide.value?.id)
)
const prevSlide = computed(() =>
  activeIndex.value > 0 ? slides.value[activeIndex.value - 1] : null
)
const nextSlide = computed(() =>
  activeIndex.value < slides.value.length - 1 ? slides.value[activeIndex.value + 1] : null
)

// ── Watchers ──────────────────────────────────────────────
watch(() => props.show, async (val) => {
  if (val && props.course) {
    await loadSlides()
    formOpen.value = false
  }
})

watch(() => props.course, async (c) => {
  if (c && props.show) await loadSlides()
})

// ── API ───────────────────────────────────────────────────
function mapSlide(s) {
  const gltf = (s.content || []).find(o => o.type === 'gltf')
  return {
    id: s.id,
    title: s.title,
    position: s.order,
    contentText: formContentPreview(s),
    object3dUrl: gltf?.url || null,
    _raw: s,
  }
}

function formContentPreview(s) {
  const parts = (s.content || []).map(o => o.text || o.type)
  return parts.length ? parts.join(' · ') : ''
}

function buildContent() {
  const items = []
  if (form.value.contentText?.trim()) {
    items.push({
      id: `text_${Date.now()}`,
      type: 'text3d',
      text: form.value.contentText.trim(),
      position: [0, 1.5, 0],
      rotation: [0, 0, 0],
      scale: [0.5, 0.5, 0.5],
      color: '#ffffff',
    })
  }
  if (form.value.object3dUrl) {
    items.push({
      id: `gltf_${Date.now()}`,
      type: 'gltf',
      url: form.value.object3dUrl,
      position: [0, 0, 0],
      rotation: [0, 0, 0],
      scale: [1, 1, 1],
    })
  }
  if (!items.length) {
    items.push({
      id: 'placeholder',
      type: 'box',
      position: [0, 0, 0],
      rotation: [0, 0, 0],
      scale: [1, 1, 1],
      color: '#6366f1',
    })
  }
  return items
}

async function resolveClassroom() {
  if (!props.course?.id) return null
  const { data } = await api.get(`/courses/${props.course.id}/classrooms`)
  const list = data.data ?? data
  return list[0]?.id ?? null
}

async function loadSlides() {
  if (!props.course?.id) return
  try {
    classroomId.value = await resolveClassroom()
    if (!classroomId.value) {
      slides.value = []
      return
    }
    const { data } = await api.get(`/classrooms/${classroomId.value}/slides`)
    const list = (data.data ?? data).map(mapSlide)
    slides.value = list.sort((a, b) => (a.position ?? 0) - (b.position ?? 0))
    activeSlide.value = slides.value[0] ?? null
  } catch {
    slides.value = []
  }
}

async function saveSlide() {
  if (!form.value.title.trim()) return
  if (!classroomId.value) {
    showToast('Créez d\'abord une classe pour ce cours', 'error')
    return
  }
  isLoading.value = true
  try {
    const payload = {
      classroom_id: classroomId.value,
      title: form.value.title,
      type: 'mixed',
      content: buildContent(),
      order: form.value.position ?? slides.value.length + 1,
      duration: 0,
    }
    if (editMode.value) {
      await api.put(`/slides/${editId.value}`, payload)
      showToast('Slide modifiée ✅')
    } else {
      const { data } = await api.post('/slides', payload)
      editId.value = data.slide?.id ?? null
      showToast('Slide créée ✅')
    }
    closeForm()
    await loadSlides()
    emit('updated')
  } catch {
    showToast('Erreur lors de la sauvegarde', 'error')
  } finally {
    isLoading.value = false
  }
}

async function doDelete() {
  if (!deleteTarget.value) return
  isLoading.value = true
  try {
    await api.delete(`/slides/${deleteTarget.value.id}`)
    showToast('Slide supprimée')
    if (activeSlide.value?.id === deleteTarget.value.id) activeSlide.value = null
    deleteTarget.value = null
    await loadSlides()
    emit('updated')
  } catch {
    showToast('Erreur suppression', 'error')
  } finally {
    isLoading.value = false
  }
}

// ── UI ────────────────────────────────────────────────────
function selectSlide(slide) {
  activeSlide.value = null
  setTimeout(() => { activeSlide.value = slide }, 60)
}

function openAddForm() {
  editMode.value  = false
  editId.value    = null
  modelMode.value = 'url'
  form.value = {
    title:       '',
    contentText: '',
    object3dUrl: '',
    position:    slides.value.length,
  }
  formOpen.value = true
}

function openEditForm(slide) {
  editMode.value  = true
  editId.value    = slide.id
  modelMode.value = slide.object3dUrl ? 'url' : 'none'
  form.value = {
    title:       slide.title,
    contentText: slide.contentText || '',
    object3dUrl: slide.object3dUrl || '',
    position:    slide.position ?? 0,
  }
  formOpen.value = true
}

function closeForm() {
  formOpen.value = false
}

function confirmDelete(slide) {
  deleteTarget.value = slide
}

function goToPrev() { if (prevSlide.value) selectSlide(prevSlide.value) }
function goToNext() { if (nextSlide.value) selectSlide(nextSlide.value) }

function showToast(msg, type = 'success') {
  toast.value = { show: true, msg, type }
  setTimeout(() => toast.value.show = false, 3000)
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');
* { box-sizing: border-box; margin: 0; padding: 0; }

/* ── OVERLAY ── */
.overlay {
  position: fixed; inset: 0;
  background: rgba(6, 9, 26, 0.92);
  backdrop-filter: blur(12px);
  display: flex; align-items: center; justify-content: center;
  z-index: 200; padding: 1rem;
}

/* ── ROOT ── */
.editor-root {
  width: 100%; max-width: 1300px; height: 90vh;
  background: #0d1228;
  border: 1px solid rgba(167, 139, 250, 0.15);
  border-radius: 22px;
  display: flex; flex-direction: column;
  overflow: hidden;
  box-shadow: 0 40px 100px rgba(0,0,0,0.6);
  animation: popIn 0.35s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes popIn {
  from { opacity: 0; transform: scale(0.92) translateY(24px); }
  to   { opacity: 1; transform: none; }
}

/* ── HEADER ── */
.editor-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.1rem 1.6rem;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  background: rgba(167,139,250,0.04);
  flex-shrink: 0;
}
.header-left { display: flex; align-items: center; gap: 12px; }
.header-icon { font-size: 1.8rem; }
.header-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: #fff; }
.header-sub   { font-size: 0.76rem; color: rgba(255,255,255,0.35); margin-top: 2px; }
.header-right { display: flex; align-items: center; gap: 10px; }

.btn-add-slide {
  display: flex; align-items: center; gap: 6px;
  padding: 0.55rem 1.1rem;
  background: linear-gradient(135deg, #a78bfa, #7c3aed);
  border: none; border-radius: 10px;
  color: #fff; font-family: 'Syne', sans-serif; font-size: 0.84rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s;
}
.btn-add-slide:hover { box-shadow: 0 6px 20px rgba(167,139,250,0.35); transform: translateY(-1px); }
.btn-add-slide.small { padding: 0.45rem 0.9rem; font-size: 0.78rem; margin-top: 0.8rem; }

.close-btn {
  width: 30px; height: 30px;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  border-radius: 50%; color: rgba(255,255,255,0.4); cursor: pointer; font-size: 0.9rem;
  display: flex; align-items: center; justify-content: center; transition: all 0.2s;
}
.close-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }

/* ── BODY ── */
.editor-body {
  display: grid;
  grid-template-columns: 240px 1fr;
  flex: 1; overflow: hidden;
  transition: grid-template-columns 0.3s ease;
}
.editor-body:has(.form-panel) {
  grid-template-columns: 240px 1fr 340px;
}

/* ── SLIDES PANEL ── */
.slides-panel {
  border-right: 1px solid rgba(255,255,255,0.06);
  display: flex; flex-direction: column;
  background: rgba(255,255,255,0.015);
  overflow: hidden;
}
.slides-panel-header {
  padding: 0.8rem 1rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  font-size: 0.78rem; font-weight: 600; color: rgba(255,255,255,0.4);
  text-transform: uppercase; letter-spacing: 0.06em;
}
.slides-list {
  flex: 1; overflow-y: auto; padding: 0.5rem;
  display: flex; flex-direction: column; gap: 4px;
}
.slides-empty {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: 8px; padding: 1.5rem; text-align: center;
  color: rgba(255,255,255,0.25); font-size: 0.84rem;
}
.reorder-hint {
  padding: 0.6rem 1rem;
  font-size: 0.72rem; color: rgba(255,255,255,0.2);
  border-top: 1px solid rgba(255,255,255,0.04); text-align: center;
}

/* Slide item */
.slide-item {
  display: flex; align-items: center; gap: 8px;
  padding: 0.65rem 0.7rem;
  border-radius: 9px; cursor: pointer;
  border: 1px solid transparent; transition: all 0.2s;
  position: relative;
}
.slide-item:hover { background: rgba(167,139,250,0.06); border-color: rgba(167,139,250,0.1); }
.slide-item.active { background: rgba(167,139,250,0.1); border-color: rgba(167,139,250,0.3); }
.slide-item-num {
  font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.25);
  font-family: 'Syne', sans-serif; min-width: 18px;
}
.slide-item-info { flex: 1; min-width: 0; }
.slide-item-title { font-size: 0.82rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.slide-item-preview { font-size: 0.7rem; color: rgba(255,255,255,0.3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
.slide-item-badges { display: flex; gap: 3px; }
.badge-3d { font-size: 0.75rem; }
.slide-item-actions {
  display: flex; gap: 3px; opacity: 0; transition: opacity 0.2s;
}
.slide-item:hover .slide-item-actions { opacity: 1; }
.act-btn {
  width: 24px; height: 24px; border: none; border-radius: 5px;
  font-size: 0.72rem; cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.act-btn.edit { background: rgba(167,139,250,0.12); }
.act-btn.del  { background: rgba(239,68,68,0.12); }
.act-btn:hover { transform: scale(1.15); }

/* ── PREVIEW PANEL ── */
.preview-panel {
  display: flex; flex-direction: column;
  background: #060918; overflow: hidden;
}
.preview-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.8rem 1.2rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  background: rgba(255,255,255,0.02); flex-shrink: 0;
}
.preview-title {
  font-family: 'Syne', sans-serif; font-size: 0.88rem; font-weight: 600;
  color: #fff; display: flex; align-items: center; gap: 8px;
}
.preview-title.muted { color: rgba(255,255,255,0.25); font-weight: 400; }
.preview-badge {
  background: rgba(167,139,250,0.15); color: #a78bfa;
  font-size: 0.7rem; padding: 2px 7px; border-radius: 6px;
}
.preview-nav { display: flex; align-items: center; gap: 8px; }
.nav-arrow {
  width: 28px; height: 28px;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  border-radius: 7px; color: rgba(255,255,255,0.6); cursor: pointer;
  font-size: 1.1rem; display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.nav-arrow:hover:not(:disabled) { background: rgba(167,139,250,0.15); color: #a78bfa; border-color: rgba(167,139,250,0.3); }
.nav-arrow:disabled { opacity: 0.3; cursor: not-allowed; }
.nav-count { font-size: 0.75rem; color: rgba(255,255,255,0.35); min-width: 40px; text-align: center; }

.viewer-area { flex: 1; min-height: 0; position: relative; }
.viewer-placeholder {
  height: 100%; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 12px;
  color: rgba(255,255,255,0.2); font-size: 0.84rem;
}

.slide-info-bar {
  border-top: 1px solid rgba(255,255,255,0.05);
  padding: 0.8rem 1.2rem; flex-shrink: 0;
  background: rgba(6,9,26,0.8);
}
.info-text { font-size: 0.82rem; color: rgba(255,255,255,0.45); line-height: 1.6; margin-bottom: 0.6rem; }
.info-dots { display: flex; gap: 5px; justify-content: center; flex-wrap: wrap; }
.dot { width: 7px; height: 7px; border-radius: 50%; background: rgba(255,255,255,0.15); cursor: pointer; transition: all 0.2s; }
.dot.active { background: #a78bfa; transform: scale(1.3); }

/* ── FORM PANEL ── */
.form-panel {
  border-left: 1px solid rgba(255,255,255,0.06);
  display: flex; flex-direction: column;
  background: rgba(255,255,255,0.02);
  overflow: hidden;
}
.form-panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.9rem 1.1rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.form-panel-header h3 { font-family: 'Syne', sans-serif; font-size: 0.88rem; font-weight: 600; color: #fff; }
.close-form-btn {
  width: 24px; height: 24px; background: none; border: none;
  color: rgba(255,255,255,0.3); cursor: pointer; font-size: 0.9rem;
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.close-form-btn:hover { background: rgba(255,255,255,0.07); color: #fff; }

.form-body { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 0.9rem; }

.field-group { display: flex; flex-direction: column; gap: 5px; }
.field-group label { font-size: 0.75rem; color: rgba(255,255,255,0.45); font-weight: 500; }
.req { color: #f87171; }

.f-input {
  width: 100%; padding: 0.6rem 0.85rem;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.09);
  border-radius: 9px; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.85rem;
  outline: none; transition: border-color 0.2s;
}
.f-input:focus { border-color: rgba(167,139,250,0.4); }
.f-input::placeholder { color: rgba(255,255,255,0.2); }
.f-input.small { max-width: 100px; }

.f-textarea {
  width: 100%; padding: 0.6rem 0.85rem; resize: vertical;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.09);
  border-radius: 9px; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.85rem;
  outline: none; transition: border-color 0.2s;
}
.f-textarea:focus { border-color: rgba(167,139,250,0.4); }
.f-textarea::placeholder { color: rgba(255,255,255,0.2); }

/* Model tabs */
.model-tabs { display: flex; gap: 4px; background: rgba(255,255,255,0.04); border-radius: 8px; padding: 3px; margin-bottom: 8px; }
.mtab {
  flex: 1; padding: 0.4rem; background: transparent; border: none; border-radius: 6px;
  color: rgba(255,255,255,0.35); font-size: 0.75rem; cursor: pointer; transition: all 0.2s; font-family: 'DM Sans', sans-serif;
}
.mtab:hover { color: #fff; }
.mtab.active { background: rgba(167,139,250,0.15); color: #c4b5fd; }

.url-wrap { display: flex; flex-direction: column; gap: 6px; }
.url-check { font-size: 0.72rem; color: #34d399; }

.demo-grid { display: flex; flex-wrap: wrap; gap: 5px; }
.demo-btn {
  padding: 5px 10px; font-size: 0.74rem;
  background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px; color: rgba(255,255,255,0.5); cursor: pointer; transition: all 0.2s;
}
.demo-btn:hover { border-color: rgba(167,139,250,0.3); color: #c4b5fd; }
.demo-btn.active { border-color: #a78bfa; background: rgba(167,139,250,0.12); color: #a78bfa; }

.mini-viewer-wrap { display: flex; flex-direction: column; gap: 5px; }
.mini-viewer-label { font-size: 0.72rem; color: rgba(255,255,255,0.3); }
.mini-viewer { height: 140px; border-radius: 10px; overflow: hidden; border: 1px solid rgba(167,139,250,0.12); background: #060918; }

.form-footer {
  display: flex; gap: 8px; justify-content: flex-end;
  padding: 0.9rem 1rem; border-top: 1px solid rgba(255,255,255,0.05); flex-shrink: 0;
}

.btn-cancel {
  padding: 0.55rem 1rem;
  background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.09);
  border-radius: 9px; color: rgba(255,255,255,0.5);
  font-family: 'DM Sans', sans-serif; font-size: 0.85rem; cursor: pointer; transition: all 0.2s;
}
.btn-cancel:hover { background: rgba(255,255,255,0.08); color: #fff; }

.btn-save {
  padding: 0.55rem 1.2rem;
  background: linear-gradient(135deg, #a78bfa, #7c3aed);
  border: none; border-radius: 9px; color: #fff;
  font-family: 'Syne', sans-serif; font-size: 0.85rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; min-width: 110px;
  display: flex; align-items: center; justify-content: center;
}
.btn-save:hover:not(:disabled) { box-shadow: 0 4px 16px rgba(167,139,250,0.3); }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── DELETE OVERLAY ── */
.delete-overlay {
  position: absolute; inset: 0;
  background: rgba(6,9,26,0.85); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center; z-index: 10;
}
.delete-box {
  background: #0e1228; border: 1px solid rgba(239,68,68,0.2);
  border-radius: 16px; padding: 2rem; text-align: center; max-width: 320px;
  animation: popIn 0.3s ease;
}
.delete-box h4 { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; margin-bottom: 8px; }
.delete-box p  { font-size: 0.82rem; color: rgba(255,255,255,0.45); margin-bottom: 1.2rem; }
.delete-actions { display: flex; gap: 8px; justify-content: center; }
.btn-del-confirm {
  padding: 0.55rem 1.2rem;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  border: none; border-radius: 9px; color: #fff;
  font-family: 'Syne', sans-serif; font-size: 0.85rem; font-weight: 600;
  cursor: pointer; min-width: 90px; display: flex; align-items: center; justify-content: center;
}

/* ── TOAST ── */
.toast {
  position: absolute; bottom: 1.2rem; right: 1.2rem;
  padding: 0.65rem 1.1rem; border-radius: 10px; font-size: 0.82rem; z-index: 20;
}
.toast.success { background: rgba(167,139,250,0.12); border: 1px solid rgba(167,139,250,0.25); color: #c4b5fd; }
.toast.error   { background: rgba(239,68,68,0.12);   border: 1px solid rgba(239,68,68,0.25);   color: #f87171; }

/* ── SPINNER ── */
.spin { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.2); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── TRANSITIONS ── */
.modal-enter-active,.modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from,.modal-leave-to { opacity: 0; }
.fade-enter-active,.fade-leave-active { transition: all 0.25s ease; }
.fade-enter-from,.fade-leave-to { opacity: 0; }
.form-slide-enter-active,.form-slide-leave-active { transition: all 0.3s ease; overflow: hidden; }
.form-slide-enter-from,.form-slide-leave-to { opacity: 0; transform: translateX(20px); }
.toast-enter-active,.toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from,.toast-leave-to { opacity: 0; transform: translateY(8px); }
</style>