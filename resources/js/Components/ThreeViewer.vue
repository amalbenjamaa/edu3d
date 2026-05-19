<template>
  <canvas ref="canvasRef" class="three-viewer-canvas" />
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import * as THREE from 'three'
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js'

const props = defineProps({
  modelUrl:   { type: String, default: '' },
  autoRotate: { type: Boolean, default: true },
})

const canvasRef = ref(null)
let renderer, scene, camera, controls, animationId, modelRoot

function init() {
  const canvas = canvasRef.value
  const parent = canvas?.parentElement
  if (!canvas || !parent) return

  const W = parent.clientWidth || 400
  const H = parent.clientHeight || 300

  scene = new THREE.Scene()
  scene.background = new THREE.Color(0x060918)

  camera = new THREE.PerspectiveCamera(50, W / H, 0.1, 200)
  camera.position.set(0, 1.5, 4)

  renderer = new THREE.WebGLRenderer({ canvas, antialias: true })
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2))
  renderer.setSize(W, H)

  controls = new OrbitControls(camera, canvas)
  controls.enableDamping = true
  controls.autoRotate = props.autoRotate
  controls.autoRotateSpeed = 1.2

  scene.add(new THREE.AmbientLight(0xffffff, 0.6))
  const dir = new THREE.DirectionalLight(0xffffff, 1.2)
  dir.position.set(5, 8, 5)
  scene.add(dir)

  loadModel(props.modelUrl)
  animate()
  window.addEventListener('resize', onResize)
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

function loadModel(url) {
  if (modelRoot) {
    scene.remove(modelRoot)
    modelRoot = null
  }
  if (!url) return

  const loader = new GLTFLoader()
  loader.load(sanitizeGltfUrl(url), (gltf) => {
    modelRoot = gltf.scene
    
    // Auto-centrer et auto-cadrer le modèle dans l'aperçu
    const box = new THREE.Box3().setFromObject(modelRoot)
    const center = box.getCenter(new THREE.Vector3())
    const size = box.getSize(new THREE.Vector3())
    
    modelRoot.position.x += (modelRoot.position.x - center.x)
    modelRoot.position.y += (modelRoot.position.y - center.y) - 0.2 // petit offset vers le bas
    modelRoot.position.z += (modelRoot.position.z - center.z)
    
    // Mettre à l'échelle pour s'adapter à la caméra
    const maxDim = Math.max(size.x, size.y, size.z)
    if (maxDim > 0) {
      const scale = 2.2 / maxDim
      modelRoot.scale.set(scale, scale, scale)
    }
    
    scene.add(modelRoot)
  })
}

function animate() {
  animationId = requestAnimationFrame(animate)
  controls?.update()
  renderer?.render(scene, camera)
}

function onResize() {
  const parent = canvasRef.value?.parentElement
  if (!parent || !renderer) return
  const W = parent.clientWidth
  const H = parent.clientHeight
  camera.aspect = W / H
  camera.updateProjectionMatrix()
  renderer.setSize(W, H)
}

function dispose() {
  cancelAnimationFrame(animationId)
  controls?.dispose()
  renderer?.dispose()
  window.removeEventListener('resize', onResize)
}

watch(() => props.modelUrl, (url) => {
  if (scene) loadModel(url)
})

watch(() => props.autoRotate, (v) => {
  if (controls) controls.autoRotate = v
})

onMounted(init)
onBeforeUnmount(dispose)
</script>

<style scoped>
.three-viewer-canvas {
  width: 100%;
  height: 100%;
  display: block;
}
</style>
