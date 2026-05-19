<template>
  <div class="slides-root">

    <!-- ── SIDEBAR GAUCHE : liste des classes ── -->
    <aside class="classes-panel">
      <!-- Admin Back Button -->
      <div v-if="auth?.user?.role === 'admin'" class="admin-back-btn-wrap" style="padding: 0.8rem; border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(99,102,241,0.04);">
        <a href="/admin/dashboard" class="btn-admin-back" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 0.55rem 0.9rem; background: linear-gradient(135deg, #6366f1, #4f46e5); border: none; border-radius: 9px; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.78rem; font-weight: 600; text-decoration: none; cursor: pointer; text-align: center; width: 100%; transition: all 0.2s; box-shadow: 0 4px 12px rgba(99,102,241,0.2);">
          ⬅️ Retour Tableau Admin
        </a>
      </div>

      <div class="panel-header">
        <h2>Mes Classes</h2>
        <span class="badge">{{ classrooms.length }}</span>
      </div>
      <div class="panel-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="classSearch" placeholder="Filtrer..."/>
      </div>
      <div class="classes-list">
        <div v-for="cls in filteredClassrooms" :key="cls.id"
          :class="['class-item', { active: selectedClassroom?.id === cls.id }]"
          @click="selectClassroom(cls)">
          <div class="class-item-icon">🏛️</div>
          <div class="class-item-info">
            <div class="class-item-name">{{ cls.name }}</div>
            <div class="class-item-course">{{ cls.course?.title || '—' }}</div>
          </div>
          <div class="class-item-count">{{ cls.slides_count ?? 0 }}</div>
        </div>
        <div class="empty-hint" v-if="!filteredClassrooms.length">Aucune classe trouvée</div>
      </div>
    </aside>

    <!-- ── ZONE PRINCIPALE ── -->
    <main class="main-area">

      <!-- État vide : aucune classe sélectionnée -->
      <div v-if="!selectedClassroom" class="empty-state-full">
        <div class="empty-state-icon">🎞️</div>
        <h3>Sélectionnez une classe</h3>
        <p>Choisissez une classe dans le panneau gauche pour gérer ses slides 3D</p>
      </div>

      <template v-else>
        <!-- Topbar -->
        <div class="main-topbar">
          <div class="main-topbar-left">
            <div class="classroom-title">
              <span class="classroom-name">{{ selectedClassroom.name }}</span>
              <span class="classroom-course">{{ selectedClassroom.course?.title }}</span>
            </div>
          </div>
          <div class="main-topbar-right">
            <span class="slides-count">{{ slides.length }} slide{{ slides.length !== 1 ? 's' : '' }}</span>
            <button class="btn-new-slide" @click="openEditor(null)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
              </svg>
              Nouvelle slide
            </button>
          </div>
        </div>

        <!-- Grille de slides -->
        <div class="slides-grid" v-if="slides.length">
          <div v-for="slide in slides" :key="slide.id"
            :class="['slide-card', { selected: previewSlide?.id === slide.id }]">
            <!-- Miniature Three.js -->
            <div class="slide-thumb" @click="previewSlide = slide">
              <MiniScene :slide="slide" :key="'mini-'+slide.id"/>
              <div class="slide-thumb-overlay">
                <button class="thumb-btn" @click.stop="openEditor(slide)" title="Modifier">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button class="thumb-btn danger" @click.stop="deleteSlide(slide)" title="Supprimer">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3,6 5,6 21,6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                </button>
              </div>
              <div class="slide-order-badge">#{{ slide.order }}</div>
            </div>
            <div class="slide-card-footer">
              <span class="slide-title">{{ slide.title }}</span>
              <span class="slide-type-badge" :class="slide.type">{{ typeLabel(slide.type) }}</span>
            </div>
          </div>

          <!-- Carte "+ Ajouter" -->
          <div class="slide-card add-card" @click="openEditor(null)">
            <div class="add-card-inner">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="32" height="32">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
              </svg>
              <span>Nouvelle slide</span>
            </div>
          </div>
        </div>

        <!-- État vide (pas de slides) -->
        <div v-else class="empty-state-full">
          <div class="empty-state-icon">✨</div>
          <h3>Aucune slide dans cette classe</h3>
          <p>Créez votre première slide 3D interactive</p>
          <button class="btn-new-slide large" @click="openEditor(null)">
            + Créer la première slide
          </button>
        </div>
      </template>
    </main>

    <!-- ── PRÉVISUALISATION PLEIN ÉCRAN ── -->
    <transition name="preview">
      <div class="preview-overlay" v-if="previewSlide" @click.self="previewSlide = null">
        <div class="preview-panel">
          <div class="preview-header">
            <div class="preview-title">
              <span>{{ previewSlide.title }}</span>
              <span class="slide-type-badge" :class="previewSlide.type">{{ typeLabel(previewSlide.type) }}</span>
            </div>
            <div class="preview-actions">
              <button class="preview-btn" @click="openEditor(previewSlide)">✏️ Modifier</button>
              <button class="preview-close" @click="previewSlide = null">✕</button>
            </div>
          </div>
          <div class="preview-canvas-wrap">
            <FullScene :slide="previewSlide" :key="'full-'+previewSlide.id"/>
          </div>
          <div class="preview-footer">
            <div class="preview-meta">
              <span>{{ previewSlide.content?.length ?? 0 }} objet(s) 3D</span>
              <span v-if="previewSlide.duration > 0">{{ previewSlide.duration }}s</span>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── ÉDITEUR DE SLIDE ── -->
    <transition name="editor">
      <div class="editor-overlay" v-if="editorOpen">
        <div class="editor-panel">

          <!-- Header éditeur -->
          <div class="editor-header">
            <div class="editor-header-left">
              <span class="editor-title">{{ editingSlide ? 'Modifier la slide' : 'Nouvelle slide 3D' }}</span>
            </div>
            <div class="editor-header-right">
              <button class="btn-cancel-editor" @click="closeEditor">Annuler</button>
              <button class="btn-save-editor" @click="saveSlide" :disabled="saving">
                {{ saving ? 'Enregistrement...' : '💾 Enregistrer' }}
              </button>
            </div>
          </div>

          <div class="editor-body">

            <!-- Colonne gauche : métadonnées + objets 3D -->
            <div class="editor-left">

              <!-- Métadonnées -->
              <div class="editor-section">
                <div class="section-title">Informations</div>
                <div class="field-row">
                  <label>Titre *</label>
                  <input v-model="form.title" placeholder="Titre de la slide" class="editor-input"/>
                </div>
                <div class="field-row">
                  <label>Type</label>
                  <select v-model="form.type" class="editor-select">
                    <option value="mixed">Mixte</option>
                    <option value="text3d">Texte 3D</option>
                    <option value="shape3d">Formes 3D</option>
                    <option value="image3d">Image 3D</option>
                    <option value="model3d">Modèle 3D</option>
                  </select>
                </div>
                <div class="field-row">
                  <label>Durée (sec)</label>
                  <input type="number" v-model.number="form.duration" min="0" class="editor-input small" placeholder="0 = libre"/>
                </div>
              </div>

              <!-- Caméra -->
              <div class="editor-section">
                <div class="section-title">📷 Caméra</div>
                <div class="field-row-3">
                  <div>
                    <label>X</label>
                    <input type="number" v-model.number="form.camera.position[0]" step="0.5" class="editor-input small" @input="refreshPreview"/>
                  </div>
                  <div>
                    <label>Y</label>
                    <input type="number" v-model.number="form.camera.position[1]" step="0.5" class="editor-input small" @input="refreshPreview"/>
                  </div>
                  <div>
                    <label>Z</label>
                    <input type="number" v-model.number="form.camera.position[2]" step="0.5" class="editor-input small" @input="refreshPreview"/>
                  </div>
                </div>
                <div class="field-row">
                  <label>FOV</label>
                  <input type="range" v-model.number="form.camera.fov" min="30" max="120" class="editor-range" @input="refreshPreview"/>
                  <span class="range-val">{{ form.camera.fov }}°</span>
                </div>
              </div>

              <!-- Liste des objets 3D -->
              <div class="editor-section flex1">
                <div class="section-title-row">
                  <div class="section-title">🧊 Objets 3D</div>
                  <div class="add-obj-btns">
                    <button class="btn-add-obj" v-for="t in objectTypes" :key="t.val" @click="addObject(t.val)" :title="'Ajouter '+t.label">
                      {{ t.icon }}
                    </button>
                  </div>
                </div>

                <div class="objects-list">
                  <div v-for="(obj, idx) in form.content" :key="obj.id"
                    :class="['obj-item', { selected: selectedObjIdx === idx }]"
                    @click="selectedObjIdx = idx">
                    <span class="obj-type-icon">{{ objIcon(obj.type) }}</span>
                    <span class="obj-label">{{ obj.text || obj.type }}</span>
                    <button class="obj-remove" @click.stop="removeObject(idx)">✕</button>
                  </div>
                  <div class="empty-hint" v-if="!form.content.length">
                    Cliquez sur un bouton ci-dessus pour ajouter un objet
                  </div>
                </div>

                <!-- Propriétés de l'objet sélectionné -->
                <div class="obj-props" v-if="selectedObj">
                  <div class="obj-props-title">Propriétés — {{ selectedObj.type }}</div>

                  <!-- Texte (pour text3d) -->
                  <div class="field-row" v-if="selectedObj.type === 'text3d'">
                    <label>Texte</label>
                    <textarea v-model="selectedObj.text" class="editor-input" style="min-height: 80px; resize: vertical;" placeholder="Votre texte 3D (Appuyez sur Entrée pour aller à la ligne)" @input="refreshPreview"></textarea>
                  </div>

                  <!-- URL (gltf / image) -->
                  <div class="field-row" v-if="['gltf','image'].includes(selectedObj.type)">
                    <label>URL</label>
                    <input v-model="selectedObj.url" class="editor-input" placeholder="https://..." @input="refreshPreview"/>
                  </div>

                  <!-- Position -->
                  <div class="prop-group-label">Position</div>
                  <div class="field-row-3">
                    <div><label>X</label><input type="number" v-model.number="selectedObj.position[0]" step="0.1" class="editor-input small" @input="refreshPreview"/></div>
                    <div><label>Y</label><input type="number" v-model.number="selectedObj.position[1]" step="0.1" class="editor-input small" @input="refreshPreview"/></div>
                    <div><label>Z</label><input type="number" v-model.number="selectedObj.position[2]" step="0.1" class="editor-input small" @input="refreshPreview"/></div>
                  </div>

                  <!-- Rotation -->
                  <div class="prop-group-label">Rotation (rad)</div>
                  <div class="field-row-3">
                    <div><label>X</label><input type="number" v-model.number="selectedObj.rotation[0]" step="0.1" class="editor-input small" @input="refreshPreview"/></div>
                    <div><label>Y</label><input type="number" v-model.number="selectedObj.rotation[1]" step="0.1" class="editor-input small" @input="refreshPreview"/></div>
                    <div><label>Z</label><input type="number" v-model.number="selectedObj.rotation[2]" step="0.1" class="editor-input small" @input="refreshPreview"/></div>
                  </div>

                  <!-- Scale -->
                  <div class="prop-group-label">Échelle</div>
                  <div class="field-row-3">
                    <div><label>X</label><input type="number" v-model.number="selectedObj.scale[0]" step="0.1" min="0.1" class="editor-input small" @input="refreshPreview"/></div>
                    <div><label>Y</label><input type="number" v-model.number="selectedObj.scale[1]" step="0.1" min="0.1" class="editor-input small" @input="refreshPreview"/></div>
                    <div><label>Z</label><input type="number" v-model.number="selectedObj.scale[2]" step="0.1" min="0.1" class="editor-input small" @input="refreshPreview"/></div>
                  </div>

                  <!-- Couleur + Opacité -->
                  <div class="field-row-2">
                    <div>
                      <label>Couleur</label>
                      <div class="color-row">
                        <input type="color" v-model="selectedObj.color" class="color-picker" @input="refreshPreview"/>
                        <span class="color-val">{{ selectedObj.color }}</span>
                      </div>
                    </div>
                    <div>
                      <label>Opacité</label>
                      <input type="range" v-model.number="selectedObj.opacity" min="0.1" max="1" step="0.05" class="editor-range" @input="refreshPreview"/>
                      <span class="range-val">{{ Math.round(selectedObj.opacity * 100) }}%</span>
                    </div>
                  </div>

                  <div class="field-row">
                    <label class="checkbox-label">
                      <input type="checkbox" v-model="selectedObj.castShadow" @change="refreshPreview"/>
                      <span>Projette une ombre</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Colonne droite : aperçu Three.js en temps réel -->
            <div class="editor-right">
              <div class="preview-label">Aperçu temps réel</div>
              <div class="editor-canvas-wrap">
                <EditorScene ref="editorSceneRef" :slide="liveSlide" :key="sceneKey"/>
              </div>
              <div class="editor-preview-hint">
                Cliquez sur les contrôles caméra pour orbiter • Molette = zoom
              </div>
            </div>
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
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import axios from 'axios'
import MiniScene from '@/Components/three/MiniScene.vue'
import FullScene from '@/Components/three/FullScene.vue'
import EditorScene from '@/Components/three/EditorScene.vue'

// ── Props ─────────────────────────────────────────────────────────────────────
defineProps({ auth: Object })

// ── État global ───────────────────────────────────────────────────────────────
const classrooms      = ref([])
const selectedClassroom = ref(null)
const slides          = ref([])
const classSearch     = ref('')
const previewSlide    = ref(null)
const toast           = ref({ show: false, msg: '', type: 'success' })

// ── Éditeur ───────────────────────────────────────────────────────────────────
const editorOpen    = ref(false)
const editingSlide  = ref(null)
const saving        = ref(false)
const selectedObjIdx = ref(null)
const sceneKey      = ref(0)
const editorSceneRef = ref(null)

const defaultForm = () => ({
  title:    '',
  type:     'mixed',
  duration: 0,
  content:  [],
  camera:   { position: [5, 5, 5], target: [0, 0, 0], fov: 60 },
})

const form = ref(defaultForm())

const selectedObj = computed(() =>
  selectedObjIdx.value !== null ? form.value.content[selectedObjIdx.value] : null
)

const liveSlide = computed(() => ({
  ...form.value,
  classroom_id: selectedClassroom.value?.id,
}))

// ── Types d'objets ────────────────────────────────────────────────────────────
const objectTypes = [
  { val: 'box',    label: 'Cube',    icon: '🟦' },
  { val: 'sphere', label: 'Sphère',  icon: '🔵' },
  { val: 'plane',  label: 'Plan',    icon: '⬜' },
  { val: 'text3d', label: 'Texte',   icon: '🔤' },
  { val: 'gltf',   label: 'GLTF',   icon: '🤖' },
]

function objIcon(type) {
  return { box: '🟦', sphere: '🔵', plane: '⬜', text3d: '🔤', gltf: '🤖', image: '🖼️' }[type] ?? '❓'
}

// ── Computed ──────────────────────────────────────────────────────────────────
const filteredClassrooms = computed(() => {
  if (!classSearch.value) return classrooms.value
  const q = classSearch.value.toLowerCase()
  return classrooms.value.filter(c =>
    c.name?.toLowerCase().includes(q) || c.course?.title?.toLowerCase().includes(q)
  )
})

function typeLabel(t) {
  return { text3d: 'Texte', shape3d: 'Formes', image3d: 'Image', model3d: 'Modèle', mixed: 'Mixte' }[t] ?? t
}

// ── API ───────────────────────────────────────────────────────────────────────
async function loadClassrooms() {
  try {
    const { data } = await axios.get('/api/classrooms')
    classrooms.value = (data.data || data).map(c => ({ ...c, slides_count: c.slides_count ?? 0 }))
  } catch { showToast('Erreur chargement des classes', 'error') }
}

async function selectClassroom(cls) {
  selectedClassroom.value = cls
  previewSlide.value = null
  slides.value = []
  try {
    const { data } = await axios.get(`/api/classrooms/${cls.id}/slides`)
    slides.value = (data.data || data).sort((a, b) => a.order - b.order)
  } catch { showToast('Erreur chargement des slides', 'error') }
}

async function saveSlide() {
  if (!form.value.title.trim()) { showToast('Le titre est requis', 'error'); return }
  saving.value = true
  try {
    const payload = {
      classroom_id: selectedClassroom.value.id,
      title:        form.value.title,
      type:         form.value.type,
      duration:     form.value.duration,
      content:      form.value.content,
      camera:       form.value.camera,
    }
    if (editingSlide.value) {
      await axios.put(`/api/slides/${editingSlide.value.id}`, payload)
      showToast('Slide mise à jour ✨')
    } else {
      await axios.post('/api/slides', payload)
      showToast('Slide créée avec succès 🎉')
    }
    closeEditor()
    await selectClassroom(selectedClassroom.value)
  } catch (e) {
    const errors = e?.response?.data?.errors
    const firstError = errors ? Object.values(errors)[0]?.[0] : null
    showToast(firstError || e?.response?.data?.message || 'Erreur lors de la sauvegarde', 'error')
  } finally {
    saving.value = false
  }
}

async function deleteSlide(slide) {
  if (!confirm(`Supprimer "${slide.title}" ?`)) return
  try {
    await axios.delete(`/api/slides/${slide.id}`)
    showToast('Slide supprimée')
    if (previewSlide.value?.id === slide.id) previewSlide.value = null
    await selectClassroom(selectedClassroom.value)
  } catch { showToast('Erreur suppression', 'error') }
}

// ── Éditeur ───────────────────────────────────────────────────────────────────
function openEditor(slide) {
  editingSlide.value = slide
  if (slide) {
    form.value = {
      title:    slide.title,
      type:     slide.type,
      duration: slide.duration ?? 0,
      content:  JSON.parse(JSON.stringify(slide.content ?? [])),
      camera:   JSON.parse(JSON.stringify(slide.camera ?? { position: [5,5,5], target: [0,0,0], fov: 60 })),
    }
  } else {
    form.value = defaultForm()
  }
  selectedObjIdx.value = null
  sceneKey.value++
  editorOpen.value = true
  previewSlide.value = null
}

function closeEditor() {
  editorOpen.value = false
  editingSlide.value = null
  selectedObjIdx.value = null
}

function addObject(type) {
  const newObj = {
    id:        'obj_' + Date.now(),
    type,
    text:      type === 'text3d' ? 'Texte 3D' : undefined,
    url:       ['gltf','image'].includes(type) ? '' : undefined,
    position:  [0, 0, 0],
    rotation:  [0, 0, 0],
    scale:     [1, 1, 1],
    color:     '#6366f1',
    opacity:   1.0,
    castShadow: true,
  }
  form.value.content.push(newObj)
  selectedObjIdx.value = form.value.content.length - 1
  refreshPreview()
}

function removeObject(idx) {
  form.value.content.splice(idx, 1)
  if (selectedObjIdx.value >= form.value.content.length) {
    selectedObjIdx.value = form.value.content.length - 1
  }
  refreshPreview()
}

function refreshPreview() {
  // Forcer le re-render de EditorScene via un watcher réactif
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  toast.value = { show: true, msg, type }
  setTimeout(() => toast.value.show = false, 3500)
}

onMounted(async () => {
  await loadClassrooms()
  const classroomId = new URLSearchParams(window.location.search).get('classroom')
  if (!classroomId) return
  const cls = classrooms.value.find(c => String(c.id) === String(classroomId))
  if (cls) await selectClassroom(cls)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');
* { box-sizing: border-box; margin: 0; padding: 0; }

.slides-root {
  display: grid;
  grid-template-columns: 260px 1fr;
  min-height: 100vh;
  background: #050b18;
  font-family: 'DM Sans', sans-serif;
  color: #fff;
}

/* ── CLASSES PANEL ── */
.classes-panel {
  border-right: 1px solid rgba(255,255,255,0.06);
  background: rgba(255,255,255,0.02);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.4rem 1.2rem 0.8rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.panel-header h2 { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700; color: #fff; }
.badge { background: rgba(167,139,250,0.15); color: #a78bfa; font-size: 0.72rem; padding: 2px 8px; border-radius: 20px; }
.panel-search {
  display: flex; align-items: center; gap: 8px;
  margin: 0.8rem;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 10px;
  padding: 0.5rem 0.8rem;
}
.panel-search input { background: none; border: none; color: #fff; font-size: 0.84rem; font-family: 'DM Sans', sans-serif; outline: none; width: 100%; }
.panel-search input::placeholder { color: rgba(255,255,255,0.25); }
.classes-list { flex: 1; overflow-y: auto; padding: 0 0.5rem 1rem; }
.class-item {
  display: flex; align-items: center; gap: 10px;
  padding: 0.7rem 0.8rem;
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s;
  margin-bottom: 3px;
}
.class-item:hover { background: rgba(255,255,255,0.04); }
.class-item.active { background: rgba(167,139,250,0.1); border: 1px solid rgba(167,139,250,0.2); }
.class-item-icon { font-size: 1.1rem; }
.class-item-info { flex: 1; overflow: hidden; }
.class-item-name { font-size: 0.84rem; color: #fff; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.class-item-course { font-size: 0.72rem; color: rgba(255,255,255,0.35); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.class-item-count { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.4); font-size: 0.72rem; padding: 2px 7px; border-radius: 20px; white-space: nowrap; }

/* ── MAIN AREA ── */
.main-area {
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}
.main-topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.2rem 2rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  background: rgba(255,255,255,0.02);
  flex-shrink: 0;
}
.classroom-title { display: flex; flex-direction: column; }
.classroom-name { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; color: #fff; }
.classroom-course { font-size: 0.78rem; color: rgba(255,255,255,0.35); margin-top: 2px; }
.main-topbar-right { display: flex; align-items: center; gap: 1rem; }
.slides-count { font-size: 0.82rem; color: rgba(255,255,255,0.35); }
.btn-new-slide {
  display: flex; align-items: center; gap: 7px;
  padding: 0.6rem 1.1rem;
  background: linear-gradient(135deg, #a78bfa, #7c3aed);
  border: none; border-radius: 10px;
  color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.85rem; font-weight: 600;
  cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
}
.btn-new-slide:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(167,139,250,0.3); }
.btn-new-slide.large { padding: 0.8rem 1.6rem; font-size: 0.95rem; margin-top: 1rem; }

/* ── SLIDES GRID ── */
.slides-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1.2rem;
  padding: 2rem;
}
.slide-card {
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.06);
  background: rgba(255,255,255,0.03);
  overflow: hidden;
  transition: border-color 0.2s, transform 0.2s;
  cursor: pointer;
}
.slide-card:hover { border-color: rgba(167,139,250,0.3); transform: translateY(-2px); }
.slide-card.selected { border-color: #a78bfa; }
.slide-thumb {
  position: relative;
  height: 150px;
  background: #0a0a14;
  overflow: hidden;
}
.slide-thumb-overlay {
  position: absolute; inset: 0;
  background: rgba(0,0,0,0);
  display: flex; align-items: center; justify-content: center; gap: 8px;
  opacity: 0;
  transition: all 0.2s;
}
.slide-card:hover .slide-thumb-overlay { background: rgba(0,0,0,0.5); opacity: 1; }
.thumb-btn {
  width: 34px; height: 34px;
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: 8px;
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(8px);
  color: #fff; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.thumb-btn:hover { background: rgba(167,139,250,0.4); border-color: #a78bfa; }
.thumb-btn.danger:hover { background: rgba(239,68,68,0.4); border-color: #f87171; }
.slide-order-badge {
  position: absolute; top: 8px; left: 8px;
  background: rgba(0,0,0,0.6); color: rgba(255,255,255,0.6);
  font-size: 0.7rem; padding: 2px 7px; border-radius: 20px;
}
.slide-card-footer {
  padding: 0.8rem 1rem;
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.slide-title { font-size: 0.84rem; color: #fff; font-weight: 500; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.slide-type-badge {
  font-size: 0.68rem; padding: 2px 7px; border-radius: 20px; white-space: nowrap; font-weight: 600;
}
.slide-type-badge.text3d  { background: rgba(59,130,246,0.15); color: #60a5fa; }
.slide-type-badge.shape3d { background: rgba(168,85,247,0.15);  color: #c084fc; }
.slide-type-badge.image3d { background: rgba(245,158,11,0.15);  color: #fbbf24; }
.slide-type-badge.model3d { background: rgba(34,197,94,0.15);   color: #4ade80; }
.slide-type-badge.mixed   { background: rgba(167,139,250,0.15); color: #a78bfa; }

/* Carte ajouter */
.add-card {
  border: 2px dashed rgba(255,255,255,0.1) !important;
  background: transparent !important;
  display: flex; align-items: center; justify-content: center;
  min-height: 200px;
}
.add-card:hover { border-color: rgba(167,139,250,0.4) !important; transform: none !important; }
.add-card-inner {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  color: rgba(255,255,255,0.25);
  transition: color 0.2s;
}
.add-card:hover .add-card-inner { color: rgba(167,139,250,0.6); }
.add-card-inner span { font-size: 0.84rem; }

/* ── EMPTY STATE ── */
.empty-state-full {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.8rem; padding: 3rem;
  text-align: center;
}
.empty-state-icon { font-size: 3rem; }
.empty-state-full h3 { font-family: 'Syne', sans-serif; font-size: 1.1rem; color: #fff; }
.empty-state-full p { font-size: 0.88rem; color: rgba(255,255,255,0.35); max-width: 320px; line-height: 1.6; }
.empty-hint { font-size: 0.8rem; color: rgba(255,255,255,0.25); padding: 0.8rem; text-align: center; }

/* ── PREVIEW OVERLAY ── */
.preview-overlay {
  position: fixed; inset: 0; z-index: 50;
  background: rgba(0,0,0,0.8); backdrop-filter: blur(12px);
  display: flex; align-items: center; justify-content: center;
  padding: 2rem;
}
.preview-panel {
  width: 100%; max-width: 900px;
  background: #0d1b2e;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 24px;
  overflow: hidden;
  display: flex; flex-direction: column;
  max-height: 90vh;
}
.preview-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.2rem 1.6rem;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.preview-title { display: flex; align-items: center; gap: 10px; }
.preview-title span { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 600; color: #fff; }
.preview-actions { display: flex; align-items: center; gap: 8px; }
.preview-btn {
  padding: 0.5rem 1rem;
  background: rgba(167,139,250,0.1); border: 1px solid rgba(167,139,250,0.3);
  border-radius: 8px; color: #a78bfa;
  font-size: 0.82rem; cursor: pointer; transition: all 0.2s;
}
.preview-btn:hover { background: rgba(167,139,250,0.2); }
.preview-close {
  width: 32px; height: 32px;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px; color: rgba(255,255,255,0.5);
  font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.preview-canvas-wrap { flex: 1; min-height: 400px; background: #050b18; }
.preview-footer {
  padding: 0.8rem 1.6rem;
  border-top: 1px solid rgba(255,255,255,0.06);
}
.preview-meta { display: flex; gap: 1.5rem; font-size: 0.78rem; color: rgba(255,255,255,0.35); }

/* ── ÉDITEUR OVERLAY ── */
.editor-overlay {
  position: fixed; inset: 0; z-index: 100;
  background: #050b18;
  display: flex; flex-direction: column;
}
.editor-panel { display: flex; flex-direction: column; height: 100%; }
.editor-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.5rem;
  background: rgba(255,255,255,0.02);
  border-bottom: 1px solid rgba(255,255,255,0.07);
  flex-shrink: 0;
}
.editor-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: #fff; }
.editor-header-right { display: flex; gap: 10px; }
.btn-cancel-editor {
  padding: 0.55rem 1.1rem;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px; color: rgba(255,255,255,0.6);
  font-size: 0.85rem; cursor: pointer;
}
.btn-save-editor {
  padding: 0.55rem 1.4rem;
  background: linear-gradient(135deg, #a78bfa, #7c3aed);
  border: none; border-radius: 10px; color: #fff;
  font-size: 0.85rem; font-weight: 600; cursor: pointer;
  transition: opacity 0.2s;
}
.btn-save-editor:disabled { opacity: 0.5; cursor: not-allowed; }

.editor-body {
  display: grid;
  grid-template-columns: 340px 1fr;
  flex: 1;
  overflow: hidden;
}

/* Colonne gauche de l'éditeur */
.editor-left {
  border-right: 1px solid rgba(255,255,255,0.06);
  display: flex; flex-direction: column;
  overflow-y: auto;
  background: rgba(255,255,255,0.02);
}
.editor-section {
  padding: 1rem 1.2rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.editor-section.flex1 { flex: 1; display: flex; flex-direction: column; }
.section-title { font-size: 0.75rem; font-weight: 600; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.8rem; }
.section-title-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.8rem; }
.add-obj-btns { display: flex; gap: 4px; }
.btn-add-obj {
  width: 28px; height: 28px;
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 7px; cursor: pointer;
  font-size: 0.85rem;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.btn-add-obj:hover { background: rgba(167,139,250,0.15); border-color: rgba(167,139,250,0.3); }

/* Champs de l'éditeur */
.field-row { display: flex; flex-direction: column; gap: 5px; margin-bottom: 0.7rem; }
.field-row label { font-size: 0.75rem; color: rgba(255,255,255,0.4); }
.field-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; margin-bottom: 0.6rem; }
.field-row-3 label { font-size: 0.72rem; color: rgba(255,255,255,0.35); margin-bottom: 3px; display: block; }
.field-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 0.6rem; }
.field-row-2 label { font-size: 0.75rem; color: rgba(255,255,255,0.4); display: block; margin-bottom: 3px; }
.editor-input {
  padding: 0.5rem 0.8rem;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px; color: #fff;
  font-family: 'DM Sans', sans-serif; font-size: 0.84rem; outline: none;
  width: 100%;
}
.editor-input:focus { border-color: rgba(167,139,250,0.4); }
.editor-input.small { padding: 0.45rem 0.6rem; }
.editor-select {
  padding: 0.5rem 0.8rem;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px; color: #fff;
  font-family: 'DM Sans', sans-serif; font-size: 0.84rem; outline: none;
  width: 100%;
}
.editor-select option { background: #0d1b2e; }
.editor-range { width: 100%; accent-color: #a78bfa; }
.range-val { font-size: 0.72rem; color: rgba(255,255,255,0.4); }

/* Objects list */
.objects-list { display: flex; flex-direction: column; gap: 4px; margin-bottom: 0.8rem; min-height: 60px; }
.obj-item {
  display: flex; align-items: center; gap: 8px;
  padding: 0.5rem 0.8rem;
  border-radius: 8px;
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
  cursor: pointer; transition: all 0.2s;
}
.obj-item:hover { background: rgba(255,255,255,0.06); }
.obj-item.selected { background: rgba(167,139,250,0.1); border-color: rgba(167,139,250,0.3); }
.obj-type-icon { font-size: 0.9rem; }
.obj-label { flex: 1; font-size: 0.82rem; color: rgba(255,255,255,0.7); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.obj-remove { background: none; border: none; color: rgba(255,255,255,0.25); cursor: pointer; font-size: 0.75rem; transition: color 0.2s; flex-shrink: 0; }
.obj-remove:hover { color: #f87171; }

/* Object properties */
.obj-props {
  border-top: 1px solid rgba(255,255,255,0.06);
  padding-top: 0.8rem;
  flex: 1;
  overflow-y: auto;
}
.obj-props-title { font-size: 0.72rem; font-weight: 600; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.8rem; }
.prop-group-label { font-size: 0.72rem; color: rgba(255,255,255,0.3); margin-bottom: 5px; font-weight: 500; }
.color-row { display: flex; align-items: center; gap: 8px; }
.color-picker { width: 36px; height: 30px; border: none; border-radius: 6px; cursor: pointer; background: none; padding: 0; }
.color-val { font-size: 0.75rem; color: rgba(255,255,255,0.4); font-family: monospace; }
.checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.82rem; color: rgba(255,255,255,0.6); }
.checkbox-label input { accent-color: #a78bfa; }

/* Colonne droite éditeur */
.editor-right {
  display: flex; flex-direction: column;
  overflow: hidden;
}
.preview-label {
  padding: 0.6rem 1.2rem;
  font-size: 0.72rem; color: rgba(255,255,255,0.3);
  text-transform: uppercase; letter-spacing: 0.08em;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  background: rgba(255,255,255,0.01);
}
.editor-canvas-wrap { flex: 1; background: #030712; overflow: hidden; }
.editor-preview-hint {
  padding: 0.5rem 1.2rem;
  font-size: 0.72rem; color: rgba(255,255,255,0.2);
  border-top: 1px solid rgba(255,255,255,0.04);
  text-align: center;
}

/* ── TOAST ── */
.toast-global {
  position: fixed; bottom: 2rem; right: 2rem; z-index: 9999;
  padding: 0.85rem 1.4rem; border-radius: 12px;
  font-size: 0.88rem; display: flex; align-items: center; gap: 8px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.4);
}
.toast-global.success { background: rgba(0,245,212,0.12); border: 1px solid rgba(0,245,212,0.3); color: #00f5d4; }
.toast-global.error   { background: rgba(239,68,68,0.12);  border: 1px solid rgba(239,68,68,0.3);  color: #f87171; }

/* ── TRANSITIONS ── */
.preview-enter-active, .preview-leave-active { transition: all 0.3s ease; }
.preview-enter-from, .preview-leave-to { opacity: 0; }
.editor-enter-active, .editor-leave-active { transition: all 0.25s ease; }
.editor-enter-from, .editor-leave-to { opacity: 0; transform: translateY(20px); }
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(20px); }
</style>