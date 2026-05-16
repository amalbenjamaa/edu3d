<template>
  <div class="editor-root">

      <!-- LEFT: Object panel -->
      <aside class="panel panel-left">
        <div class="panel-header">
          <h3>Slides</h3>
          <button class="btn-icon-sm" @click="addSlide" title="Nouvelle slide">+</button>
        </div>

        <div class="slides-list">
          <div
            v-for="(slide, i) in slides"
            :key="slide.id ?? i"
            :class="['slide-item', { active: selectedSlideIndex === i }]"
            @click="selectSlide(i)"
          >
            <span class="slide-num">#{{ i + 1 }}</span>
            <span class="slide-name">{{ slide.title || 'Sans titre' }}</span>
            <button class="del-btn" @click.stop="deleteSlide(i)" title="Supprimer">✕</button>
          </div>
          <div class="empty-msg" v-if="!slides.length">
            Ajoutez une première slide →
          </div>
        </div>

        <!-- Add objects -->
        <div class="panel-section">
          <div class="panel-section-title">Ajouter un objet 3D</div>
          <div class="obj-grid">
            <button v-for="t in objectTypes" :key="t.type"
              class="obj-type-btn"
              @click="addObject(t.type)"
              :title="t.label">
              <span class="obj-icon">{{ t.icon }}</span>
              <span class="obj-label">{{ t.label }}</span>
            </button>
          </div>
        </div>

        <!-- Selected object properties -->
        <div class="panel-section" v-if="selectedObj">
          <div class="panel-section-title">Propriétés</div>
          <div class="prop-group">
            <label>Type</label>
            <span class="prop-val">{{ selectedObj.type }}</span>
          </div>
          <div class="prop-group" v-if="selectedObj.type === 'text3d'">
            <label>Texte</label>
            <input class="prop-input" v-model="selectedObj.text" @change="refreshScene"/>
          </div>
          <div class="prop-group">
            <label>Couleur</label>
            <div class="color-row">
              <input type="color" class="color-picker"
                :value="selectedObj.color ?? '#6366f1'"
                @input="e => { selectedObj.color = e.target.value; refreshScene() }"/>
              <span class="color-hex">{{ selectedObj.color ?? '#6366f1' }}</span>
            </div>
          </div>
          <div class="prop-group">
            <label>Position X / Y / Z</label>
            <div class="xyz-row">
              <input type="number" step="0.1" class="xyz-input"
                v-model.number="selectedObj.position[0]" @change="refreshScene"/>
              <input type="number" step="0.1" class="xyz-input"
                v-model.number="selectedObj.position[1]" @change="refreshScene"/>
              <input type="number" step="0.1" class="xyz-input"
                v-model.number="selectedObj.position[2]" @change="refreshScene"/>
            </div>
          </div>
          <div class="prop-group">
            <label>Échelle X / Y / Z</label>
            <div class="xyz-row">
              <input type="number" step="0.1" min="0.01" class="xyz-input"
                v-model.number="selectedObj.scale[0]" @change="refreshScene"/>
              <input type="number" step="0.1" min="0.01" class="xyz-input"
                v-model.number="selectedObj.scale[1]" @change="refreshScene"/>
              <input type="number" step="0.1" min="0.01" class="xyz-input"
                v-model.number="selectedObj.scale[2]" @change="refreshScene"/>
            </div>
          </div>
          <div class="prop-group">
            <label>Opacité</label>
            <input type="range" min="0" max="1" step="0.05" class="range-input"
              v-model.number="selectedObj.opacity" @input="refreshScene"/>
            <span class="range-val">{{ Math.round((selectedObj.opacity ?? 1) * 100) }}%</span>
          </div>
          <button class="btn-danger-sm" @click="deleteObject">🗑 Supprimer cet objet</button>
        </div>
      </aside>

      <!-- CENTER: 3D Viewer -->
      <div class="viewer-center">
        <!-- Slide title editable -->
        <div class="slide-title-bar" v-if="currentSlide">
          <input
            class="slide-title-input"
            v-model="currentSlide.title"
            placeholder="Titre de la slide…"
            @change="markDirty"
          />
          <span class="slide-type-badge">{{ currentSlide.type ?? 'mixed' }}</span>
        </div>

        <!-- ThreeScene viewer -->
        <div class="viewer-frame" v-if="slides.length">
          <ThreeScene
            ref="threeRef"
            :slides="[currentSlide].filter(Boolean)"
            :initial-index="0"
            :editable="true"
            :key="sceneKey"
          />
        </div>
        <div class="viewer-placeholder" v-else>
          <div class="placeholder-icon">🧊</div>
          <p>Créez une slide pour commencer à éditer en 3D</p>
          <button class="btn-primary" @click="addSlide">+ Nouvelle slide</button>
        </div>

        <!-- Save bar -->
        <div class="save-bar" v-if="dirty">
          <span class="dirty-indicator">● Modifications non sauvegardées</span>
          <button class="btn-save" @click="saveAll" :disabled="saving">
            <span v-if="!saving">💾 Sauvegarder</span>
            <div v-else class="spin-sm"></div>
          </button>
        </div>
      </div>

      <!-- RIGHT: Object list & camera -->
      <aside class="panel panel-right">
        <div class="panel-header">
          <h3>Objets</h3>
        </div>

        <div class="obj-list" v-if="currentSlide">
          <div
            v-for="(obj, i) in currentSlide.content"
            :key="obj.id ?? i"
            :class="['obj-item', { active: selectedObjIndex === i }]"
            @click="selectedObjIndex = i"
          >
            <span class="obj-item-icon">{{ getObjIcon(obj.type) }}</span>
            <span class="obj-item-name">{{ obj.type }}{{ obj.text ? ` "${obj.text}"` : '' }}</span>
          </div>
          <div class="empty-msg" v-if="!currentSlide?.content?.length">
            Aucun objet — ajoutez-en un
          </div>
        </div>

        <!-- Camera -->
        <div class="panel-section" v-if="currentSlide">
          <div class="panel-section-title">Caméra</div>
          <div class="prop-group">
            <label>Position X / Y / Z</label>
            <div class="xyz-row">
              <input type="number" step="0.5" class="xyz-input"
                v-model.number="camPos[0]" @change="applyCam"/>
              <input type="number" step="0.5" class="xyz-input"
                v-model.number="camPos[1]" @change="applyCam"/>
              <input type="number" step="0.5" class="xyz-input"
                v-model.number="camPos[2]" @change="applyCam"/>
            </div>
          </div>
          <div class="prop-group">
            <label>Cible X / Y / Z</label>
            <div class="xyz-row">
              <input type="number" step="0.5" class="xyz-input"
                v-model.number="camTarget[0]" @change="applyCam"/>
              <input type="number" step="0.5" class="xyz-input"
                v-model.number="camTarget[1]" @change="applyCam"/>
              <input type="number" step="0.5" class="xyz-input"
                v-model.number="camTarget[2]" @change="applyCam"/>
            </div>
          </div>
          <div class="prop-group">
            <label>FOV</label>
            <input type="number" step="1" min="20" max="120" class="xyz-input" style="width:80px"
              v-model.number="camFov" @change="applyCam"/>
          </div>
        </div>

        <!-- Slide type -->
        <div class="panel-section" v-if="currentSlide">
          <div class="panel-section-title">Type de slide</div>
          <select class="prop-input" v-model="currentSlide.type" @change="markDirty">
            <option value="mixed">Mixte</option>
            <option value="text3d">Texte 3D</option>
            <option value="shape3d">Formes 3D</option>
            <option value="image3d">Image 3D</option>
            <option value="model3d">Modèle 3D</option>
          </select>
        </div>
      </aside>

      <!-- Toast -->
      <transition name="toast">
        <div class="toast" v-if="toast.show" :class="toast.type">
          {{ toast.type === 'success' ? '✅' : '❌' }} {{ toast.msg }}
        </div>
      </transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import ThreeScene from '@/Components/ThreeScene.vue'
import axios from 'axios'

const props = defineProps({
  classroomId: [String, Number],
})

// ─── State ────────────────────────────────────────────────────────────────────
const slides             = ref([])
const selectedSlideIndex = ref(0)
const selectedObjIndex   = ref(null)
const threeRef           = ref(null)
const sceneKey           = ref(0)
const dirty              = ref(false)
const saving             = ref(false)
const toast              = ref({ show: false, msg: '', type: 'success' })

const camPos    = ref([5, 4, 8])
const camTarget = ref([0, 0, 0])
const camFov    = ref(60)

// ─── Computed ─────────────────────────────────────────────────────────────────
const currentSlide = computed(() => slides.value[selectedSlideIndex.value] ?? null)
const selectedObj  = computed(() =>
  selectedObjIndex.value !== null
    ? currentSlide.value?.content?.[selectedObjIndex.value] ?? null
    : null
)

const objectTypes = [
  { type: 'box',      icon: '⬜', label: 'Cube' },
  { type: 'sphere',   icon: '🔵', label: 'Sphère' },
  { type: 'cylinder', icon: '🔷', label: 'Cylindre' },
  { type: 'torus',    icon: '🍩', label: 'Tore' },
  { type: 'cone',     icon: '🔺', label: 'Cône' },
  { type: 'plane',    icon: '▬',  label: 'Plan' },
  { type: 'text3d',   icon: 'T',  label: 'Texte 3D' },
]

function getObjIcon(type) {
  return objectTypes.find(t => t.type === type)?.icon ?? '●'
}

// ─── Load slides ──────────────────────────────────────────────────────────────
async function loadSlides() {
  try {
    const { data } = await axios.get(`/api/classrooms/${props.classroomId}/slides`)
    slides.value = (data.data ?? data).map(s => ({
      ...s,
      content: Array.isArray(s.content) ? s.content : [],
      camera:  s.camera ?? { position: [5, 4, 8], target: [0, 0, 0], fov: 60 },
    }))
    if (slides.value.length) syncCamInputs()
  } catch {
    showToast('Erreur chargement des slides', 'error')
  }
}

// ─── Slide management ─────────────────────────────────────────────────────────
function addSlide() {
  const newSlide = {
    id:           null, // null = not yet saved
    classroom_id: props.classroomId,
    title:        `Slide ${slides.value.length + 1}`,
    type:         'mixed',
    content:      [],
    camera:       { position: [5, 4, 8], target: [0, 0, 0], fov: 60 },
    order:        slides.value.length + 1,
  }
  slides.value.push(newSlide)
  selectedSlideIndex.value = slides.value.length - 1
  selectedObjIndex.value   = null
  syncCamInputs()
  markDirty()
  refreshScene()
}

function selectSlide(i) {
  selectedSlideIndex.value = i
  selectedObjIndex.value   = null
  syncCamInputs()
  refreshScene()
}

async function deleteSlide(i) {
  const slide = slides.value[i]
  if (slide.id) {
    try { await axios.delete(`/api/slides/${slide.id}`) }
    catch { showToast('Erreur suppression slide', 'error'); return }
  }
  slides.value.splice(i, 1)
  if (selectedSlideIndex.value >= slides.value.length) {
    selectedSlideIndex.value = Math.max(0, slides.value.length - 1)
  }
  selectedObjIndex.value = null
  refreshScene()
  showToast('Slide supprimée')
}

// ─── Object management ────────────────────────────────────────────────────────
function addObject(type) {
  if (!currentSlide.value) return
  const newObj = {
    id:        `obj_${Date.now()}`,
    type,
    position:  [0, 0, 0],
    rotation:  [0, 0, 0],
    scale:     [1, 1, 1],
    color:     '#6366f1',
    opacity:   1,
    castShadow: true,
    ...(type === 'text3d' ? { text: 'Texte 3D', size: 0.4 } : {}),
    ...(type === 'gltf'   ? { url: '' } : {}),
    ...(type === 'image'  ? { url: '' } : {}),
  }
  currentSlide.value.content.push(newObj)
  selectedObjIndex.value = currentSlide.value.content.length - 1
  markDirty()
  refreshScene()
}

function deleteObject() {
  if (selectedObjIndex.value === null || !currentSlide.value) return
  currentSlide.value.content.splice(selectedObjIndex.value, 1)
  selectedObjIndex.value = null
  markDirty()
  refreshScene()
}

// ─── Camera ───────────────────────────────────────────────────────────────────
function syncCamInputs() {
  const cam = currentSlide.value?.camera
  if (!cam) return
  camPos.value    = [...(cam.position ?? [5, 4, 8])]
  camTarget.value = [...(cam.target   ?? [0, 0, 0])]
  camFov.value    = cam.fov ?? 60
}

function applyCam() {
  if (!currentSlide.value) return
  currentSlide.value.camera = {
    position: [...camPos.value],
    target:   [...camTarget.value],
    fov:      camFov.value,
  }
  markDirty()
  refreshScene()
}

// ─── Scene refresh ────────────────────────────────────────────────────────────
function refreshScene() {
  sceneKey.value++
}

function markDirty() {
  dirty.value = true
}

// ─── Save ─────────────────────────────────────────────────────────────────────
async function saveAll() {
  saving.value = true
  try {
    for (const slide of slides.value) {
      const payload = {
        classroom_id: props.classroomId,
        title:        slide.title,
        type:         slide.type,
        content:      slide.content,
        camera:       slide.camera,
        order:        slide.order ?? 1,
        duration:     slide.duration ?? 0,
      }

      if (slide.id) {
        // Update existing
        await axios.put(`/api/slides/${slide.id}`, payload)
      } else {
        // Create new — but content validation requires objects
        // If no content, send minimal valid content
        if (!payload.content.length) {
          payload.content = [{
            id: 'placeholder',
            type: 'box',
            position: [0, -10, 0], // hidden below scene
            rotation: [0, 0, 0],
            scale: [0.001, 0.001, 0.001],
            color: '#000000',
          }]
        }
        const { data } = await axios.post('/api/slides', payload)
        slide.id = data.slide?.id ?? null
      }
    }

    dirty.value  = false
    showToast('Slides sauvegardées !')
  } catch (e) {
    showToast(
      e.response?.data?.message || 'Erreur sauvegarde',
      'error'
    )
  } finally {
    saving.value = false
  }
}

// ─── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  toast.value = { show: true, msg, type }
  setTimeout(() => toast.value.show = false, 3000)
}

// ─── Watch slide change to sync camera inputs ─────────────────────────────────
watch(selectedSlideIndex, () => {
  syncCamInputs()
  selectedObjIndex.value = null
})

onMounted(() => loadSlides())
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@600;700&family=DM+Sans:wght@400;500&display=swap');

* { box-sizing: border-box; margin: 0; padding: 0; }

.editor-root {
  display: grid;
  grid-template-columns: 260px 1fr 240px;
  min-height: calc(100vh - 1rem);
  height: 100%;
  background: #050b18;
  font-family: 'DM Sans', sans-serif;
  color: #fff;
  position: relative;
}

/* ── Panels ── */
.panel {
  background: rgba(255,255,255,0.03);
  border-color: rgba(255,255,255,0.06);
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  height: 100%;
}
.panel-left  { border-right: 1px solid rgba(255,255,255,0.06); }
.panel-right { border-left:  1px solid rgba(255,255,255,0.06); }

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.1rem 0.8rem;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  flex-shrink: 0;
}
.panel-header h3 {
  font-family: 'Syne', sans-serif;
  font-size: 0.88rem;
  font-weight: 600;
  color: rgba(255,255,255,0.7);
}

.btn-icon-sm {
  width: 26px; height: 26px;
  border-radius: 7px;
  border: 1px solid rgba(0,245,212,0.3);
  background: rgba(0,245,212,0.08);
  color: #00f5d4;
  font-size: 1.1rem;
  line-height: 1;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.btn-icon-sm:hover { background: rgba(0,245,212,0.18); }

.panel-section {
  padding: 0.9rem 1.1rem;
  border-top: 1px solid rgba(255,255,255,0.05);
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}
.panel-section-title {
  font-size: 0.7rem;
  font-weight: 600;
  color: rgba(255,255,255,0.3);
  text-transform: uppercase;
  letter-spacing: 0.09em;
  margin-bottom: 2px;
}

/* ── Slide list ── */
.slides-list {
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 3px;
  flex-shrink: 0;
}

.slide-item {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 0.55rem 0.7rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
  border: 1px solid transparent;
}
.slide-item:hover { background: rgba(255,255,255,0.04); }
.slide-item.active {
  background: rgba(0,245,212,0.08);
  border-color: rgba(0,245,212,0.2);
}
.slide-num {
  font-size: 0.68rem;
  color: rgba(255,255,255,0.3);
  min-width: 20px;
}
.slide-name {
  font-size: 0.82rem;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: rgba(255,255,255,0.7);
}
.slide-item.active .slide-name { color: #00f5d4; }
.del-btn {
  background: none; border: none; color: rgba(255,255,255,0.2);
  font-size: 0.75rem; cursor: pointer; padding: 2px 4px;
  border-radius: 4px; opacity: 0; transition: opacity 0.15s;
}
.slide-item:hover .del-btn { opacity: 1; }
.del-btn:hover { color: #f87171; background: rgba(239,68,68,0.1); }

/* ── Object type buttons ── */
.obj-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 5px;
}
.obj-type-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding: 0.5rem 0.3rem;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  color: rgba(255,255,255,0.55);
}
.obj-type-btn:hover {
  border-color: rgba(0,245,212,0.3);
  color: #00f5d4;
  background: rgba(0,245,212,0.06);
}
.obj-icon { font-size: 1.1rem; line-height: 1; }
.obj-label { font-size: 0.65rem; color: inherit; }

/* ── Properties ── */
.prop-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.prop-group label {
  font-size: 0.72rem;
  color: rgba(255,255,255,0.35);
  font-weight: 500;
}
.prop-val {
  font-size: 0.82rem;
  color: rgba(255,255,255,0.6);
  background: rgba(255,255,255,0.04);
  padding: 4px 8px;
  border-radius: 6px;
}
.prop-input {
  width: 100%;
  padding: 0.45rem 0.7rem;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.09);
  border-radius: 8px;
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.83rem;
  outline: none;
}
.prop-input:focus { border-color: rgba(0,245,212,0.4); }
select.prop-input option { background: #0d1b2e; }

.color-row { display: flex; align-items: center; gap: 8px; }
.color-picker {
  width: 36px; height: 28px;
  padding: 2px; border: 1px solid rgba(255,255,255,0.1);
  border-radius: 6px; background: transparent; cursor: pointer;
}
.color-hex { font-size: 0.78rem; color: rgba(255,255,255,0.4); font-family: monospace; }

.xyz-row { display: flex; gap: 4px; }
.xyz-input {
  flex: 1;
  padding: 0.35rem 0.4rem;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.09);
  border-radius: 6px;
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.78rem;
  outline: none;
  min-width: 0;
  text-align: center;
}
.xyz-input:focus { border-color: rgba(0,245,212,0.4); }

.range-input { width: 100%; accent-color: #00f5d4; }
.range-val { font-size: 0.75rem; color: rgba(255,255,255,0.35); }

.btn-danger-sm {
  padding: 0.4rem 0.8rem;
  background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.2);
  border-radius: 7px;
  color: #f87171;
  font-size: 0.78rem;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}
.btn-danger-sm:hover { background: rgba(239,68,68,0.2); }

/* ── Viewer center ── */
.viewer-center {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.slide-title-bar {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  padding: 0.6rem 1rem;
  background: rgba(255,255,255,0.02);
  border-bottom: 1px solid rgba(255,255,255,0.05);
  flex-shrink: 0;
}

.slide-title-input {
  flex: 1;
  background: none;
  border: none;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  color: #fff;
  font-family: 'Syne', sans-serif;
  font-size: 0.95rem;
  font-weight: 600;
  outline: none;
  padding: 4px 0;
  transition: border-color 0.2s;
}
.slide-title-input:focus { border-bottom-color: #00f5d4; }
.slide-title-input::placeholder { color: rgba(255,255,255,0.2); }

.slide-type-badge {
  font-size: 0.7rem;
  padding: 2px 8px;
  border-radius: 20px;
  background: rgba(99,102,241,0.12);
  color: #818cf8;
  border: 1px solid rgba(99,102,241,0.2);
  white-space: nowrap;
}

.viewer-frame {
  flex: 1;
  min-height: 0;
  position: relative;
}

.viewer-placeholder {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  color: rgba(255,255,255,0.3);
}
.placeholder-icon { font-size: 3rem; }
.viewer-placeholder p { font-size: 0.88rem; }

.btn-primary {
  padding: 0.55rem 1.2rem;
  background: linear-gradient(135deg, #00f5d4, #00c4aa);
  border: none;
  border-radius: 9px;
  color: #050b18;
  font-family: 'Syne', sans-serif;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
}

.save-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.6rem 1rem;
  background: rgba(245,158,11,0.07);
  border-top: 1px solid rgba(245,158,11,0.2);
  flex-shrink: 0;
}
.dirty-indicator { font-size: 0.8rem; color: #fbbf24; }
.btn-save {
  display: flex; align-items: center; justify-content: center;
  padding: 0.45rem 1.1rem;
  background: linear-gradient(135deg, #00f5d4, #00c4aa);
  border: none; border-radius: 8px;
  color: #050b18; font-family: 'Syne', sans-serif;
  font-weight: 700; font-size: 0.82rem;
  cursor: pointer; min-width: 110px;
}
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }
.spin-sm {
  width: 16px; height: 16px;
  border: 2px solid rgba(0,0,0,0.2);
  border-top-color: #050b18;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Object list (right) ── */
.obj-list {
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.obj-item {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 0.5rem 0.7rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
  border: 1px solid transparent;
}
.obj-item:hover { background: rgba(255,255,255,0.04); }
.obj-item.active {
  background: rgba(99,102,241,0.08);
  border-color: rgba(99,102,241,0.2);
}
.obj-item-icon { font-size: 0.9rem; }
.obj-item-name {
  font-size: 0.78rem;
  color: rgba(255,255,255,0.55);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.obj-item.active .obj-item-name { color: #818cf8; }

.empty-msg {
  font-size: 0.75rem;
  color: rgba(255,255,255,0.2);
  text-align: center;
  padding: 1rem 0.5rem;
}

/* ── Toast ── */
.toast {
  position: fixed;
  bottom: 1.5rem; right: 1.5rem;
  padding: 0.7rem 1.1rem;
  border-radius: 10px;
  font-size: 0.83rem;
  z-index: 200;
}
.toast.success { background: rgba(0,245,212,0.1); border: 1px solid rgba(0,245,212,0.3); color: #00f5d4; }
.toast.error   { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }

@media (max-width: 1100px) {
  .editor-root { grid-template-columns: 220px 1fr 200px; }
}
</style>