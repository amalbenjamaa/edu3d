<template>
  <div class="three-scene-root" ref="containerRef">
    <canvas ref="canvasRef" class="three-canvas" />

    <!-- HUD top -->
    <div class="hud-top">
      <div class="slide-info">
        <span class="slide-title">{{ currentSlide?.title }}</span>
        <span class="slide-counter">{{ currentIndex + 1 }} / {{ slides.length }}</span>
      </div>
      <div class="hud-actions">
        <button class="hud-btn" @click="toggleFullscreen" title="Plein écran">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <path v-if="!isFullscreen" d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
            <path v-else d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/>
          </svg>
        </button>
        <button class="hud-btn" @click="resetCamera" title="Réinitialiser caméra">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.5"/>
          </svg>
        </button>
        <button v-if="onClose" class="hud-btn hud-close" @click="onClose" title="Fermer">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="progress-track">
      <div class="progress-fill" :style="{ width: progressPct + '%' }"></div>
    </div>

    <!-- Navigation arrows -->
    <button class="nav-arrow nav-prev" @click="prevSlide" :disabled="currentIndex === 0">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="22" height="22">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <button class="nav-arrow nav-next" @click="nextSlide" :disabled="currentIndex === slides.length - 1">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="22" height="22">
        <polyline points="9 18 15 12 9 6"/>
      </svg>
    </button>

    <!-- Slide thumbnails strip -->
    <div class="thumbs-strip" v-if="slides.length > 1">
      <button v-for="(slide, i) in slides" :key="slide.id"
        :class="['thumb', { active: i === currentIndex }]"
        @click="goToSlide(i)">
        <span class="thumb-num">{{ i + 1 }}</span>
        <span class="thumb-title">{{ slide.title }}</span>
      </button>
    </div>

    <!-- Loading overlay -->
    <transition name="fade">
      <div class="loading-overlay" v-if="isLoading">
        <div class="loader-ring"></div>
        <span>Chargement de la scène…</span>
      </div>
    </transition>

    <!-- Transition overlay for slide changes -->
    <div class="transition-overlay" ref="transitionOverlayRef"></div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import * as THREE from 'three'
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js'
import { FontLoader } from 'three/examples/jsm/loaders/FontLoader.js'
import { TextGeometry } from 'three/examples/jsm/geometries/TextGeometry.js'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js'

const props = defineProps({
  slides:       { type: Array,   required: true },
  initialIndex: { type: Number,  default: 0 },
  onClose:      { type: Function, default: null },
  editable:     { type: Boolean, default: false },
})

const emit = defineEmits(['slideChange', 'progress'])

// ─── Refs ─────────────────────────────────────────────────────────────────────
const containerRef       = ref(null)
const canvasRef          = ref(null)
const transitionOverlayRef = ref(null)
const currentIndex       = ref(props.initialIndex)
const isLoading          = ref(true)
const isFullscreen       = ref(false)

// ─── Three.js objects ─────────────────────────────────────────────────────────
let renderer, scene, camera, controls, animationId
let font = null

// ─── Computed ─────────────────────────────────────────────────────────────────
const currentSlide = computed(() => props.slides[currentIndex.value] ?? null)
const progressPct  = computed(() =>
  props.slides.length > 1
    ? (currentIndex.value / (props.slides.length - 1)) * 100
    : 100
)

// ─── Init Three.js ────────────────────────────────────────────────────────────
function initThree() {
  const canvas    = canvasRef.value
  const container = containerRef.value
  const W = container.clientWidth
  const H = container.clientHeight

  // Scene
  scene = new THREE.Scene()
  scene.background = new THREE.Color(0x050b18)
  scene.fog = new THREE.FogExp2(0x050b18, 0.035)

  // Camera
  camera = new THREE.PerspectiveCamera(60, W / H, 0.1, 200)
  camera.position.set(5, 4, 8)

  // Renderer
  renderer = new THREE.WebGLRenderer({ canvas, antialias: true })
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2))
  renderer.setSize(W, H)
  renderer.shadowMap.enabled = true
  renderer.shadowMap.type    = THREE.PCFSoftShadowMap
  renderer.toneMapping       = THREE.ACESFilmicToneMapping
  renderer.toneMappingExposure = 1.2

  // OrbitControls
  controls = new OrbitControls(camera, canvas)
  controls.enableDamping    = true
  controls.dampingFactor    = 0.05
  controls.minDistance      = 1
  controls.maxDistance      = 50
  controls.enablePan        = true
  controls.autoRotate       = false
  controls.autoRotateSpeed  = 0.5

  // Lights
  setupLights()

  // Load font for text3d
  loadFont().then(() => {
    loadSlide(currentIndex.value)
  })

  // Animate
  animate()

  // Resize
  window.addEventListener('resize', onResize)
}

function setupLights() {
  // Ambient
  const ambient = new THREE.AmbientLight(0x334466, 0.6)
  scene.add(ambient)

  // Key light
  const key = new THREE.DirectionalLight(0xffffff, 1.5)
  key.position.set(10, 15, 10)
  key.castShadow = true
  key.shadow.mapSize.set(2048, 2048)
  key.shadow.camera.near = 0.1
  key.shadow.camera.far  = 60
  key.shadow.camera.left = key.shadow.camera.bottom = -20
  key.shadow.camera.right = key.shadow.camera.top   = 20
  scene.add(key)

  // Fill light
  const fill = new THREE.DirectionalLight(0x6688cc, 0.4)
  fill.position.set(-10, 5, -5)
  scene.add(fill)

  // Rim light
  const rim = new THREE.DirectionalLight(0x00f5d4, 0.3)
  rim.position.set(0, -5, -10)
  scene.add(rim)

  // Ground plane (receives shadows)
  const groundGeo = new THREE.PlaneGeometry(100, 100)
  const groundMat = new THREE.MeshStandardMaterial({
    color: 0x050b18,
    roughness: 0.9,
    metalness: 0.1,
  })
  const ground = new THREE.Mesh(groundGeo, groundMat)
  ground.rotation.x = -Math.PI / 2
  ground.position.y = -4
  ground.receiveShadow = true
  scene.add(ground)

  // Grid
  const grid = new THREE.GridHelper(30, 30, 0x1a2a4a, 0x0d1a2e)
  grid.position.y = -4
  grid.material.opacity = 0.5
  grid.material.transparent = true
  scene.add(grid)
}

function loadFont() {
  return new Promise((resolve) => {
    const loader = new FontLoader()
    loader.load(
      'https://threejs.org/examples/fonts/helvetiker_regular.typeface.json',
      (loadedFont) => { font = loadedFont; resolve() },
      undefined,
      () => resolve() // fail silently
    )
  })
}

// ─── Load a slide ─────────────────────────────────────────────────────────────
async function loadSlide(index) {
  isLoading.value = true

  const slide = props.slides[index]
  if (!slide) { isLoading.value = false; return }

  // Clear old scene objects (keep lights + ground)
  clearSceneObjects()

  // Set camera from slide data
  const cam = slide.camera
  if (cam) {
    const [px, py, pz] = cam.position ?? [5, 4, 8]
    const [tx, ty, tz] = cam.target   ?? [0, 0, 0]
    camera.position.set(px, py, pz)
    if (cam.fov) camera.fov = cam.fov
    camera.updateProjectionMatrix()
    controls.target.set(tx, ty, tz)
    controls.update()
  } else {
    camera.position.set(5, 4, 8)
    controls.target.set(0, 0, 0)
    controls.update()
  }

  // Build objects
  const content = slide.content ?? []
  for (const obj of content) {
    const mesh = await buildObject(obj)
    if (mesh) scene.add(mesh)
  }

  // Fallback
  if (content.length === 0) {
    const fallback = new THREE.Mesh(
      new THREE.IcosahedronGeometry(1.5, 1),
      new THREE.MeshStandardMaterial({ color: 0x6366f1, wireframe: true })
    )
    scene.add(fallback)
  }

  isLoading.value = false
  emit('slideChange', index)
  emit('progress', { slideId: slide.id, index })
}

async function buildObject(obj) {
  const pos = obj.position ?? [0, 0, 0]
  const rot = obj.rotation ?? [0, 0, 0]
  const scl = obj.scale    ?? [1, 1, 1]
  const col = new THREE.Color(obj.color ?? '#6366f1')
  const opacity = obj.opacity ?? 1

  const mat = new THREE.MeshStandardMaterial({
    color: col,
    roughness: 0.4,
    metalness: 0.3,
    transparent: opacity < 1,
    opacity,
  })

  let mesh = null

  switch (obj.type) {
    case 'box':
      mesh = new THREE.Mesh(new THREE.BoxGeometry(1, 1, 1), mat)
      break

    case 'sphere':
      mesh = new THREE.Mesh(new THREE.SphereGeometry(0.7, 32, 32), mat)
      break

    case 'plane':
      mesh = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), mat)
      break

    case 'cylinder':
      mesh = new THREE.Mesh(new THREE.CylinderGeometry(0.5, 0.5, 1, 32), mat)
      break

    case 'torus':
      mesh = new THREE.Mesh(new THREE.TorusGeometry(0.7, 0.25, 16, 100), mat)
      break

    case 'cone':
      mesh = new THREE.Mesh(new THREE.ConeGeometry(0.7, 1.5, 32), mat)
      break

    case 'text3d':
      if (font && obj.text) {
        const parsedText = String(obj.text).replace(/\\n/g, '\n')
        const geo = new TextGeometry(parsedText, {
          font,
          size:           obj.size ?? 0.4,
          depth:          0.08,
          curveSegments:  8,
          bevelEnabled:   true,
          bevelThickness: 0.02,
          bevelSize:      0.01,
        })
        geo.center()
        const textMat = new THREE.MeshStandardMaterial({
          color: col,
          roughness: 0.3,
          metalness: 0.4,
        })
        mesh = new THREE.Mesh(geo, textMat)
      } else {
        // Fallback: flat box as placeholder
        mesh = new THREE.Mesh(
          new THREE.BoxGeometry(2, 0.4, 0.1),
          new THREE.MeshStandardMaterial({ color: col })
        )
      }
      break

    case 'gltf':
      if (obj.url) {
        mesh = await loadGLTF(obj.url)
      }
      break

    case 'image':
      if (obj.url) {
        mesh = await loadImagePlane(obj.url, obj)
      }
      break

    default:
      mesh = new THREE.Mesh(
        new THREE.BoxGeometry(1, 1, 1),
        new THREE.MeshStandardMaterial({ color: col, wireframe: true })
      )
  }

  if (mesh) {
    mesh.position.set(...pos)
    mesh.rotation.set(...rot)
    mesh.scale.set(...scl)
    if (obj.castShadow !== false) mesh.castShadow = true
    mesh.receiveShadow = true
    mesh.userData = { objData: obj }

    // Add subtle entrance animation
    mesh.userData.entrance = { startY: pos[1] - 1.5, targetY: pos[1], t: 0 }
    mesh.position.y = pos[1] - 1.5
  }

  return mesh
}

function sanitizeGltfUrl(url) {
  if (!url) return url
  let u = url.trim()
  if (u.includes('github.com/') && u.includes('/blob/')) {
    u = u.replace('github.com', 'raw.githubusercontent.com').replace('/blob/', '/')
  }
  if (u.includes('gitlab.com/') && u.includes('/blob/')) {
    u = u.replace('/blob/', '/raw/')
  }
  return u
}

function loadGLTF(url) {
  return new Promise((resolve) => {
    const loader = new GLTFLoader()
    loader.load(
      sanitizeGltfUrl(url),
      (gltf) => {
        const model = gltf.scene
        model.traverse(child => {
          if (child.isMesh) { child.castShadow = true; child.receiveShadow = true }
        })
        resolve(model)
      },
      undefined,
      () => resolve(null)
    )
  })
}

function loadImagePlane(url, obj) {
  return new Promise((resolve) => {
    new THREE.TextureLoader().load(
      url,
      (texture) => {
        const w = obj.width ?? 3
        const h = obj.height ?? 2
        const geo = new THREE.PlaneGeometry(w, h)
        const mat = new THREE.MeshStandardMaterial({ map: texture, side: THREE.DoubleSide })
        resolve(new THREE.Mesh(geo, mat))
      },
      undefined,
      () => resolve(null)
    )
  })
}

function clearSceneObjects() {
  // Keep lights, ground, grid — remove everything else
  const toRemove = []
  scene.traverse(obj => {
    if (obj.userData?.objData || obj.userData?.entrance) toRemove.push(obj)
  })
  toRemove.forEach(obj => {
    scene.remove(obj)
    if (obj.geometry) obj.geometry.dispose()
    if (obj.material) {
      if (Array.isArray(obj.material)) obj.material.forEach(m => m.dispose())
      else obj.material.dispose()
    }
  })
}

// ─── Animation loop ───────────────────────────────────────────────────────────
function animate() {
  animationId = requestAnimationFrame(animate)

  // Entrance animation for new objects
  scene.traverse(obj => {
    if (obj.userData?.entrance) {
      const e = obj.userData.entrance
      if (e.t < 1) {
        e.t = Math.min(e.t + 0.04, 1)
        const ease = 1 - Math.pow(1 - e.t, 3) // easeOutCubic
        obj.position.y = e.startY + (e.targetY - e.startY) * ease
      }
    }
  })

  controls.update()
  renderer.render(scene, camera)
}

// ─── Navigation ───────────────────────────────────────────────────────────────
async function goToSlide(index) {
  if (index < 0 || index >= props.slides.length || index === currentIndex.value) return

  // Fade transition
  await fadeTransition()
  currentIndex.value = index
  await loadSlide(index)
}

function nextSlide() { goToSlide(currentIndex.value + 1) }
function prevSlide()  { goToSlide(currentIndex.value - 1) }

function fadeTransition() {
  return new Promise(resolve => {
    const overlay = transitionOverlayRef.value
    if (!overlay) { resolve(); return }
    overlay.style.transition = 'opacity 0.25s ease'
    overlay.style.opacity = '1'
    setTimeout(() => {
      overlay.style.opacity = '0'
      resolve()
    }, 260)
  })
}

// ─── Camera helpers ───────────────────────────────────────────────────────────
function resetCamera() {
  const cam = currentSlide.value?.camera
  if (cam) {
    camera.position.set(...(cam.position ?? [5, 4, 8]))
    controls.target.set(...(cam.target   ?? [0, 0, 0]))
  } else {
    camera.position.set(5, 4, 8)
    controls.target.set(0, 0, 0)
  }
  controls.update()
}

// ─── Resize ───────────────────────────────────────────────────────────────────
function onResize() {
  const container = containerRef.value
  if (!container || !renderer) return
  const W = container.clientWidth
  const H = container.clientHeight
  camera.aspect = W / H
  camera.updateProjectionMatrix()
  renderer.setSize(W, H)
}

// ─── Fullscreen ───────────────────────────────────────────────────────────────
function toggleFullscreen() {
  if (!document.fullscreenElement) {
    containerRef.value?.requestFullscreen()
    isFullscreen.value = true
  } else {
    document.exitFullscreen()
    isFullscreen.value = false
  }
}

// ─── Keyboard ─────────────────────────────────────────────────────────────────
function onKeydown(e) {
  if (e.key === 'ArrowRight' || e.key === 'ArrowDown') nextSlide()
  if (e.key === 'ArrowLeft'  || e.key === 'ArrowUp')   prevSlide()
  if (e.key === 'Escape' && props.onClose) props.onClose()
}

// ─── Dispose ──────────────────────────────────────────────────────────────────
function dispose() {
  cancelAnimationFrame(animationId)
  controls?.dispose()
  renderer?.dispose()
  scene?.traverse(obj => {
    if (obj.geometry) obj.geometry.dispose()
    if (obj.material) {
      if (Array.isArray(obj.material)) obj.material.forEach(m => m.dispose())
      else obj.material.dispose()
    }
  })
  window.removeEventListener('resize', onResize)
  window.removeEventListener('keydown', onKeydown)
}

// ─── Watch slides change ──────────────────────────────────────────────────────
watch(() => props.slides, () => loadSlide(currentIndex.value), { deep: true })

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(() => {
  initThree()
  window.addEventListener('keydown', onKeydown)
})
onBeforeUnmount(() => dispose())

// Expose for parent
defineExpose({ goToSlide, currentIndex })
</script>

<style scoped>
.three-scene-root {
  position: relative;
  width: 100%;
  height: 100%;
  min-height: 480px;
  background: #050b18;
  overflow: hidden;
  user-select: none;
}

.three-canvas {
  display: block;
  width: 100% !important;
  height: 100% !important;
}

/* HUD top */
.hud-top {
  position: absolute;
  top: 0; left: 0; right: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.8rem 1.2rem;
  background: linear-gradient(to bottom, rgba(5,11,24,0.85) 0%, transparent 100%);
  z-index: 10;
}

.slide-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.slide-title {
  font-family: 'Syne', sans-serif;
  font-size: 0.95rem;
  font-weight: 600;
  color: #fff;
}

.slide-counter {
  font-size: 0.78rem;
  color: rgba(255,255,255,0.45);
  background: rgba(255,255,255,0.08);
  padding: 2px 8px;
  border-radius: 20px;
}

.hud-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.hud-btn {
  width: 34px;
  height: 34px;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  color: rgba(255,255,255,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.hud-btn:hover { background: rgba(255,255,255,0.14); color: #fff; }
.hud-close:hover { background: rgba(239,68,68,0.2); color: #f87171; border-color: rgba(239,68,68,0.3); }

/* Progress */
.progress-track {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: rgba(255,255,255,0.08);
  z-index: 11;
}
.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #00f5d4, #6366f1);
  transition: width 0.5s ease;
}

/* Nav arrows */
.nav-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 48px;
  height: 48px;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 50%;
  color: rgba(255,255,255,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  z-index: 10;
}
.nav-arrow:hover:not(:disabled) {
  background: rgba(0,245,212,0.15);
  border-color: rgba(0,245,212,0.3);
  color: #00f5d4;
}
.nav-arrow:disabled { opacity: 0.2; cursor: not-allowed; }
.nav-prev { left: 1.2rem; }
.nav-next { right: 1.2rem; }

/* Thumbnails strip */
.thumbs-strip {
  position: absolute;
  bottom: 1rem;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 6px;
  background: rgba(5,11,24,0.75);
  backdrop-filter: blur(12px);
  padding: 6px 10px;
  border-radius: 30px;
  border: 1px solid rgba(255,255,255,0.08);
  z-index: 10;
  max-width: 80%;
  overflow-x: auto;
}

.thumb {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: 20px;
  border: none;
  background: transparent;
  color: rgba(255,255,255,0.4);
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.thumb:hover { color: #fff; background: rgba(255,255,255,0.07); }
.thumb.active {
  background: rgba(0,245,212,0.12);
  color: #00f5d4;
  border: 1px solid rgba(0,245,212,0.25);
}
.thumb-num {
  font-weight: 700;
  font-size: 0.7rem;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: rgba(255,255,255,0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}
.thumb.active .thumb-num { background: rgba(0,245,212,0.3); }
.thumb-title { max-width: 100px; overflow: hidden; text-overflow: ellipsis; }

/* Loading */
.loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(5,11,24,0.85);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  z-index: 20;
  color: rgba(255,255,255,0.6);
  font-size: 0.88rem;
}
.loader-ring {
  width: 44px;
  height: 44px;
  border: 3px solid rgba(255,255,255,0.1);
  border-top-color: #00f5d4;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Transition overlay */
.transition-overlay {
  position: absolute;
  inset: 0;
  background: #050b18;
  opacity: 0;
  pointer-events: none;
  z-index: 15;
  transition: opacity 0.25s ease;
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>