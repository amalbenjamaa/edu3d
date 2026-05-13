<template>
  <div
    class="group relative bg-gray-900 border border-gray-800 rounded-xl overflow-hidden cursor-pointer
           hover:border-indigo-500/50 hover:shadow-lg hover:shadow-indigo-500/10
           transition-all duration-300"
    @click="$emit('click', slide)">

    <!-- Aperçu 3D miniature (canvas Three.js) -->
    <div class="relative h-44 bg-gray-950 overflow-hidden">
      <canvas ref="canvasRef" class="w-full h-full" />

      <!-- Badge type -->
      <span class="absolute top-2 left-2 text-xs px-2 py-0.5 rounded-full font-medium"
        :class="typeBadgeClass">
        {{ typeLabel }}
      </span>

      <!-- Badge ordre -->
      <span class="absolute top-2 right-2 text-xs bg-black/60 text-gray-300 px-2 py-0.5 rounded-full">
        #{{ slide.order }}
      </span>

      <!-- Overlay hover -->
      <div class="absolute inset-0 bg-indigo-600/0 group-hover:bg-indigo-600/10 transition-colors duration-300 flex items-center justify-center">
        <span class="opacity-0 group-hover:opacity-100 transition-opacity text-white text-sm font-medium bg-black/60 px-3 py-1.5 rounded-lg">
          {{ actionLabel }}
        </span>
      </div>
    </div>

    <!-- Infos -->
    <div class="p-4">
      <h3 class="font-medium text-white text-sm truncate">{{ slide.title }}</h3>

      <div class="flex items-center justify-between mt-2">
        <span class="text-xs text-gray-500">
          {{ objectCount }} objet{{ objectCount > 1 ? 's' : '' }} 3D
        </span>
        <span v-if="slide.duration > 0" class="text-xs text-gray-500">
          {{ slide.duration }}s
        </span>
      </div>
    </div>

    <!-- Actions (visibles au hover, mode teacher) -->
    <div v-if="editable"
      class="absolute top-2 right-8 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
      <button @click.stop="$emit('edit', slide)"
        class="w-7 h-7 bg-black/70 hover:bg-indigo-600 rounded-lg text-xs transition-colors flex items-center justify-center"
        title="Modifier">✏️</button>
      <button @click.stop="$emit('delete', slide)"
        class="w-7 h-7 bg-black/70 hover:bg-red-600 rounded-lg text-xs transition-colors flex items-center justify-center"
        title="Supprimer">🗑️</button>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import * as THREE from 'three'

const props = defineProps({
  slide:    { type: Object, required: true },
  editable: { type: Boolean, default: false },   // mode enseignant
})

defineEmits(['click', 'edit', 'delete'])

// ─── Three.js miniature ───────────────────────────────────────────────────────
const canvasRef = ref(null)
let renderer, scene, camera, animationId

function initThree() {
  const canvas = canvasRef.value
  if (!canvas) return

  // Scène
  scene    = new THREE.Scene()
  scene.background = new THREE.Color(0x0a0a0f)

  // Caméra
  const cam = props.slide.camera
  camera = new THREE.PerspectiveCamera(cam?.fov ?? 60, canvas.clientWidth / canvas.clientHeight, 0.1, 100)
  camera.position.set(...(cam?.position ?? [4, 3, 4]))
  camera.lookAt(...(cam?.target   ?? [0, 0, 0]))

  // Renderer
  renderer = new THREE.WebGLRenderer({ canvas, antialias: true })
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2))
  renderer.setSize(canvas.clientWidth, canvas.clientHeight, false)
  renderer.shadowMap.enabled = true

  // Lumières
  scene.add(new THREE.AmbientLight(0xffffff, 0.4))
  const dir = new THREE.DirectionalLight(0xffffff, 1.2)
  dir.position.set(5, 8, 5)
  dir.castShadow = true
  scene.add(dir)

  // Charger les objets de la slide
  loadObjects()

  // Boucle d'animation (rotation douce)
  let t = 0
  function animate() {
    animationId = requestAnimationFrame(animate)
    t += 0.005
    scene.rotation.y = t
    renderer.render(scene, camera)
  }
  animate()
}

function loadObjects() {
  const content = props.slide.content ?? []

  content.forEach(obj => {
    let mesh

    const color    = new THREE.Color(obj.color ?? '#6366f1')
    const material = new THREE.MeshStandardMaterial({
      color,
      transparent: (obj.opacity ?? 1) < 1,
      opacity:     obj.opacity ?? 1,
    })

    switch (obj.type) {
      case 'box':
        mesh = new THREE.Mesh(new THREE.BoxGeometry(1, 1, 1), material)
        break
      case 'sphere':
        mesh = new THREE.Mesh(new THREE.SphereGeometry(0.7, 32, 32), material)
        break
      case 'plane':
        mesh = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), material)
        break
      case 'text3d':
        // Texte simplifié comme une sphère aplatie pour la miniature
        mesh = new THREE.Mesh(
          new THREE.BoxGeometry(1.5, 0.3, 0.1),
          new THREE.MeshStandardMaterial({ color: 0xffffff })
        )
        break
      default:
        mesh = new THREE.Mesh(new THREE.BoxGeometry(0.8, 0.8, 0.8), material)
    }

    if (mesh) {
      const pos = obj.position ?? [0, 0, 0]
      const rot = obj.rotation ?? [0, 0, 0]
      const scl = obj.scale    ?? [1, 1, 1]

      mesh.position.set(...pos)
      mesh.rotation.set(...rot)
      mesh.scale.set(...scl)
      mesh.castShadow    = obj.castShadow ?? true
      mesh.receiveShadow = true
      scene.add(mesh)
    }
  })

  // Fallback si aucun objet
  if (content.length === 0) {
    const geo = new THREE.BoxGeometry(1.5, 1.5, 1.5)
    const mat = new THREE.MeshStandardMaterial({ color: 0x6366f1, wireframe: true })
    scene.add(new THREE.Mesh(geo, mat))
  }
}

function dispose() {
  if (animationId) cancelAnimationFrame(animationId)
  renderer?.dispose()
  scene?.traverse(obj => {
    if (obj.isMesh) {
      obj.geometry?.dispose()
      obj.material?.dispose()
    }
  })
}

onMounted(() => initThree())
onBeforeUnmount(() => dispose())

// Recharger si la slide change
watch(() => props.slide, () => {
  dispose()
  scene = null
  initThree()
})

// ─── Computed ─────────────────────────────────────────────────────────────────
const objectCount = computed(() => props.slide.content?.length ?? 0)

const typeLabel = computed(() => ({
  text3d:  'Texte 3D',
  shape3d: 'Formes',
  image3d: 'Image 3D',
  model3d: 'Modèle',
  mixed:   'Mixte',
}[props.slide.type] ?? 'Slide'))

const typeBadgeClass = computed(() => ({
  text3d:  'bg-blue-500/20 text-blue-400',
  shape3d: 'bg-purple-500/20 text-purple-400',
  image3d: 'bg-amber-500/20 text-amber-400',
  model3d: 'bg-green-500/20 text-green-400',
  mixed:   'bg-indigo-500/20 text-indigo-400',
}[props.slide.type] ?? 'bg-gray-500/20 text-gray-400'))

const actionLabel = computed(() =>
  props.editable ? 'Modifier la slide' : 'Voir en 3D'
)
</script>