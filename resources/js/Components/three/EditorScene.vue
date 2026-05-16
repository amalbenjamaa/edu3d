<!-- resources/js/Components/three/EditorScene.vue -->
<template>
  <canvas ref="canvasRef" style="width:100%;height:100%;display:block;cursor:grab;"/>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import * as THREE from 'three'

const props = defineProps({
  slide: { type: Object, required: true },
})

const canvasRef = ref(null)
let renderer, scene, camera, animId
let isDragging = false, lastX = 0, lastY = 0
let azimuth = Math.PI / 4, polar = Math.PI / 4, radius = 8

function init() {
  const canvas = canvasRef.value
  if (!canvas || !canvas.parentElement) return

  const W = canvas.parentElement.clientWidth  || 600
  const H = canvas.parentElement.clientHeight || 400

  scene = new THREE.Scene()
  scene.background = new THREE.Color(0x030712)

  // Grille de référence
  const grid = new THREE.GridHelper(20, 20, 0x1a2035, 0x0d1525)
  scene.add(grid)

  // Axes helper
  const axes = new THREE.AxesHelper(2)
  scene.add(axes)

  const cam = props.slide.camera
  camera = new THREE.PerspectiveCamera(cam?.fov ?? 60, W / H, 0.1, 500)

  if (cam?.position) {
    const pos = cam.position
    const tgt = cam.target ?? [0,0,0]
    const dx = pos[0] - tgt[0], dy = pos[1] - tgt[1], dz = pos[2] - tgt[2]
    radius  = Math.sqrt(dx*dx + dy*dy + dz*dz)
    azimuth = Math.atan2(dx, dz)
    polar   = Math.acos(dy / radius)
  }
  updateCamera()

  renderer = new THREE.WebGLRenderer({ canvas, antialias: true })
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2))
  renderer.setSize(W, H, false)
  renderer.shadowMap.enabled = true
  renderer.shadowMap.type = THREE.PCFSoftShadowMap

  // Lumières
  scene.add(new THREE.AmbientLight(0xffffff, 0.4))
  const dir = new THREE.DirectionalLight(0xffffff, 1.5)
  dir.position.set(8, 12, 8)
  dir.castShadow = true
  dir.shadow.mapSize.width  = 2048
  dir.shadow.mapSize.height = 2048
  scene.add(dir)
  scene.add(new THREE.HemisphereLight(0x1a1a2e, 0x0a0d1a, 0.6))

  // Sol fantôme
  const floor = new THREE.Mesh(
    new THREE.PlaneGeometry(30, 30),
    new THREE.MeshStandardMaterial({ color: 0x080d1c, roughness: 1, transparent: true, opacity: 0.8 })
  )
  floor.rotation.x = -Math.PI / 2
  floor.position.y = -0.01
  floor.receiveShadow = true
  scene.add(floor)

  loadObjects()
  addMouseControls(canvas)

  function animate() {
    animId = requestAnimationFrame(animate)
    renderer.render(scene, camera)
  }
  animate()
}

function updateCamera() {
  const target = props.slide.camera?.target ?? [0,0,0]
  const x = radius * Math.sin(polar) * Math.sin(azimuth)
  const y = radius * Math.cos(polar)
  const z = radius * Math.sin(polar) * Math.cos(azimuth)
  camera.position.set(
    target[0] + x,
    target[1] + y,
    target[2] + z
  )
  camera.lookAt(target[0], target[1], target[2])
}

function addMouseControls(canvas) {
  canvas.addEventListener('mousedown', e => {
    isDragging = true; lastX = e.clientX; lastY = e.clientY
    canvas.style.cursor = 'grabbing'
  })
  window.addEventListener('mouseup', () => {
    isDragging = false
    if (canvas) canvas.style.cursor = 'grab'
  })
  window.addEventListener('mousemove', e => {
    if (!isDragging) return
    const dx = e.clientX - lastX
    const dy = e.clientY - lastY
    azimuth -= dx * 0.005
    polar = Math.max(0.1, Math.min(Math.PI - 0.1, polar + dy * 0.005))
    lastX = e.clientX; lastY = e.clientY
    updateCamera()
  })
  canvas.addEventListener('wheel', e => {
    e.preventDefault()
    radius = Math.max(1, Math.min(50, radius + e.deltaY * 0.01))
    updateCamera()
  }, { passive: false })
}

function loadObjects() {
  // Supprimer anciens objets (garder grille, axes, lumières, sol)
  const toRemove = []
  scene?.traverse(o => {
    if (o.userData.isSlideObj) toRemove.push(o)
  })
  toRemove.forEach(o => {
    scene.remove(o)
    o.geometry?.dispose()
    o.material?.dispose()
  })

  const content = props.slide.content ?? []
  if (!content.length) {
    // Placeholder
    const ghost = new THREE.Mesh(
      new THREE.BoxGeometry(2, 2, 2),
      new THREE.MeshStandardMaterial({ color: 0x6366f1, wireframe: true, transparent: true, opacity: 0.5 })
    )
    ghost.userData.isSlideObj = true
    scene?.add(ghost)
    return
  }

  content.forEach(obj => {
    const mesh = buildMesh(obj)
    if (mesh) {
      mesh.userData.isSlideObj = true
      scene?.add(mesh)
    }
  })
}

function buildMesh(obj) {
  const color = new THREE.Color(obj.color ?? '#6366f1')
  const mat = new THREE.MeshStandardMaterial({
    color,
    transparent: (obj.opacity ?? 1) < 1,
    opacity: obj.opacity ?? 1,
    roughness: 0.4,
    metalness: 0.15,
  })

  let geo
  switch (obj.type) {
    case 'box':
      geo = new THREE.BoxGeometry(1, 1, 1)
      break
    case 'sphere':
      geo = new THREE.SphereGeometry(0.6, 32, 32)
      break
    case 'plane':
      geo = new THREE.PlaneGeometry(2, 2)
      break
    case 'text3d':
      // Représentation visuelle du texte comme une plaque 3D
      geo = new THREE.BoxGeometry(Math.max(1, (obj.text?.length ?? 4) * 0.2), 0.4, 0.15)
      mat.color.set(obj.color ?? '#ffffff')
      break
    case 'gltf':
      geo = new THREE.BoxGeometry(1, 1, 1)
      mat.color.set(0x22c55e)
      mat.wireframe = true
      break
    case 'image':
      geo = new THREE.PlaneGeometry(2, 1.5)
      mat.color.set(0xf59e0b)
      break
    default:
      geo = new THREE.BoxGeometry(1, 1, 1)
  }

  const mesh = new THREE.Mesh(geo, mat)
  mesh.position.set(...(obj.position ?? [0,0,0]))
  mesh.rotation.set(...(obj.rotation ?? [0,0,0]))
  mesh.scale.set(...(obj.scale ?? [1,1,1]))
  mesh.castShadow    = obj.castShadow ?? true
  mesh.receiveShadow = true
  return mesh
}

function dispose() {
  cancelAnimationFrame(animId)
  renderer?.dispose()
  scene?.traverse(o => { if (o.isMesh) { o.geometry?.dispose(); o.material?.dispose() } })
  scene = null
}

onMounted(init)
onBeforeUnmount(dispose)
watch(() => props.slide, () => { loadObjects() }, { deep: true })
</script>