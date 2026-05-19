<!-- resources/js/Components/three/FullScene.vue -->
<template>
  <canvas ref="canvasRef" style="width:100%;height:100%;display:block;cursor:grab;"/>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import * as THREE from 'three'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js'
import { FontLoader } from 'three/examples/jsm/loaders/FontLoader.js'
import { TextGeometry } from 'three/examples/jsm/geometries/TextGeometry.js'

let font = null
const fontLoader = new FontLoader()
fontLoader.load('https://threejs.org/examples/fonts/helvetiker_regular.typeface.json', f => font = f)

const props = defineProps({
  slide: { type: Object, required: true },
})

const canvasRef = ref(null)
let renderer, scene, camera, animId
let isDragging = false, lastX = 0, lastY = 0
let azimuth = Math.PI / 4, polar = Math.PI / 3, radius = 8

function init() {
  const canvas = canvasRef.value
  if (!canvas || !canvas.parentElement) return
  const W = canvas.parentElement.clientWidth  || 900
  const H = canvas.parentElement.clientHeight || 480

  scene = new THREE.Scene()

  // Fond dégradé via couleur de scène
  scene.background = new THREE.Color(0x060914)
  scene.fog = new THREE.FogExp2(0x060914, 0.04)

  const cam = props.slide.camera
  camera = new THREE.PerspectiveCamera(cam?.fov ?? 60, W / H, 0.1, 500)

  if (cam?.position) {
    const pos = cam.position, tgt = cam.target ?? [0,0,0]
    const dx = pos[0]-tgt[0], dy = pos[1]-tgt[1], dz = pos[2]-tgt[2]
    radius  = Math.sqrt(dx*dx+dy*dy+dz*dz)
    azimuth = Math.atan2(dx, dz)
    polar   = Math.acos(Math.max(-1, Math.min(1, dy/radius)))
  }
  updateCamera()

  renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: false })
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2))
  renderer.setSize(W, H, false)
  renderer.shadowMap.enabled = true
  renderer.shadowMap.type = THREE.PCFSoftShadowMap
  renderer.toneMapping = THREE.ACESFilmicToneMapping
  renderer.toneMappingExposure = 1.2

  // Lumières
  scene.add(new THREE.AmbientLight(0xffffff, 0.3))
  const dir = new THREE.DirectionalLight(0xffffff, 2)
  dir.position.set(8, 12, 8)
  dir.castShadow = true
  dir.shadow.mapSize.width = dir.shadow.mapSize.height = 2048
  dir.shadow.camera.near = 0.1
  dir.shadow.camera.far  = 100
  dir.shadow.camera.left = dir.shadow.camera.bottom = -15
  dir.shadow.camera.right = dir.shadow.camera.top = 15
  scene.add(dir)

  // Lumière de remplissage colorée
  scene.add(new THREE.HemisphereLight(0x1a1a3e, 0x0a0d1a, 0.8))
  const fill = new THREE.PointLight(0x6366f1, 0.5, 30)
  fill.position.set(-5, 3, -5)
  scene.add(fill)

  // Sol avec reflection
  const floor = new THREE.Mesh(
    new THREE.PlaneGeometry(50, 50),
    new THREE.MeshStandardMaterial({ color: 0x080d1c, roughness: 0.9, metalness: 0.1 })
  )
  floor.rotation.x = -Math.PI / 2
  floor.position.y = -2
  floor.receiveShadow = true
  scene.add(floor)

  // Particules d'ambiance
  addParticles()

  loadObjects()
  addControls(canvas)

  function animate() {
    animId = requestAnimationFrame(animate)
    renderer.render(scene, camera)
  }
  animate()
}

function addParticles() {
  const count = 200
  const geo = new THREE.BufferGeometry()
  const pos = new Float32Array(count * 3)
  for (let i = 0; i < count * 3; i++) pos[i] = (Math.random() - 0.5) * 40
  geo.setAttribute('position', new THREE.BufferAttribute(pos, 3))
  const mat = new THREE.PointsMaterial({ color: 0x6366f1, size: 0.05, transparent: true, opacity: 0.6 })
  scene?.add(new THREE.Points(geo, mat))
}

function updateCamera() {
  const target = props.slide.camera?.target ?? [0,0,0]
  const x = radius * Math.sin(polar) * Math.sin(azimuth)
  const y = radius * Math.cos(polar)
  const z = radius * Math.sin(polar) * Math.cos(azimuth)
  camera.position.set(target[0]+x, target[1]+y, target[2]+z)
  camera.lookAt(target[0], target[1], target[2])
}

function addControls(canvas) {
  canvas.addEventListener('mousedown', e => { isDragging = true; lastX = e.clientX; lastY = e.clientY; canvas.style.cursor = 'grabbing' })
  window.addEventListener('mouseup', () => { isDragging = false; if (canvas) canvas.style.cursor = 'grab' })
  window.addEventListener('mousemove', e => {
    if (!isDragging) return
    azimuth -= (e.clientX - lastX) * 0.005
    polar = Math.max(0.05, Math.min(Math.PI - 0.05, polar + (e.clientY - lastY) * 0.005))
    lastX = e.clientX; lastY = e.clientY
    updateCamera()
  })
  canvas.addEventListener('wheel', e => {
    e.preventDefault()
    radius = Math.max(1.5, Math.min(50, radius + e.deltaY * 0.01))
    updateCamera()
  }, { passive: false })

  // Touch support
  let lastTouchDist = 0
  canvas.addEventListener('touchstart', e => {
    if (e.touches.length === 1) { isDragging = true; lastX = e.touches[0].clientX; lastY = e.touches[0].clientY }
    if (e.touches.length === 2) {
      const dx = e.touches[0].clientX - e.touches[1].clientX
      const dy = e.touches[0].clientY - e.touches[1].clientY
      lastTouchDist = Math.sqrt(dx*dx+dy*dy)
    }
  })
  canvas.addEventListener('touchend', () => { isDragging = false })
  canvas.addEventListener('touchmove', e => {
    e.preventDefault()
    if (e.touches.length === 1 && isDragging) {
      azimuth -= (e.touches[0].clientX - lastX) * 0.005
      polar = Math.max(0.05, Math.min(Math.PI - 0.05, polar + (e.touches[0].clientY - lastY) * 0.005))
      lastX = e.touches[0].clientX; lastY = e.touches[0].clientY
      updateCamera()
    }
    if (e.touches.length === 2) {
      const dx = e.touches[0].clientX - e.touches[1].clientX
      const dy = e.touches[0].clientY - e.touches[1].clientY
      const dist = Math.sqrt(dx*dx+dy*dy)
      radius = Math.max(1.5, Math.min(50, radius - (dist - lastTouchDist) * 0.02))
      lastTouchDist = dist
      updateCamera()
    }
  }, { passive: false })
}

function loadObjects() {
  const toRemove = []
  scene?.traverse(o => { if (o.userData.isSlideObj) toRemove.push(o) })
  toRemove.forEach(o => { scene.remove(o); o.geometry?.dispose(); o.material?.dispose() })

  const content = props.slide.content ?? []
  content.forEach(obj => {
    if (obj.type === 'gltf' && obj.url) {
      loadGltf(obj)
      return
    }
    const mesh = buildMesh(obj)
    if (mesh) { mesh.userData.isSlideObj = true; scene?.add(mesh) }
  })

  if (!content.length) {
    const m = new THREE.Mesh(
      new THREE.BoxGeometry(2, 2, 2),
      new THREE.MeshStandardMaterial({ color: 0x6366f1, wireframe: true })
    )
    m.userData.isSlideObj = true
    scene?.add(m)
  }
}

function buildMesh(obj) {
  const color = new THREE.Color(obj.color ?? '#6366f1')
  const mat = new THREE.MeshStandardMaterial({
    color, transparent: (obj.opacity ?? 1) < 1, opacity: obj.opacity ?? 1,
    roughness: 0.4, metalness: 0.1,
  })
  let geo
  switch (obj.type) {
    case 'box':    geo = new THREE.BoxGeometry(1, 1, 1); break
    case 'sphere': geo = new THREE.SphereGeometry(0.6, 64, 64); break
    case 'plane':  geo = new THREE.PlaneGeometry(2, 2); break
    case 'cylinder': geo = new THREE.CylinderGeometry(0.5, 0.5, 1.2, 32); break
    case 'torus': geo = new THREE.TorusGeometry(0.6, 0.2, 16, 48); break
    case 'cone': geo = new THREE.ConeGeometry(0.6, 1.2, 32); break
    case 'text3d':
      if (font && obj.text) {
        geo = new TextGeometry(String(obj.text).replace(/\\n/g, '\n'), {
          font,
          size: obj.size ?? 0.4,
          depth: 0.08,
          curveSegments: 8,
          bevelEnabled: true,
          bevelThickness: 0.02,
          bevelSize: 0.01,
        })
        geo.center()
        mat.color.set(obj.color ?? '#ffffff')
      } else {
        geo = new THREE.BoxGeometry(Math.max(1, (obj.text?.length ?? 4) * 0.22), 0.45, 0.15)
        mat.color.set(obj.color ?? '#ffffff')
      }
      break
    case 'image':  geo = new THREE.PlaneGeometry(2, 1.5); break
    default:       geo = new THREE.BoxGeometry(1, 1, 1)
  }
  const mesh = new THREE.Mesh(geo, mat)
  mesh.position.set(...(obj.position ?? [0,0,0]))
  mesh.rotation.set(...(obj.rotation ?? [0,0,0]))
  mesh.scale.set(...(obj.scale ?? [1,1,1]))
  mesh.castShadow = mesh.receiveShadow = true
  return mesh
}

function sanitizeGltfUrl(url) {
  if (!url) return url
  let u = url.trim()
  if (u.includes('github.com/')) {
    u = u.replace('github.com', 'raw.githubusercontent.com').replace('/blob/', '/').replace('/tree/', '/')
  }
  if (u.includes('gitlab.com/') && u.includes('/blob/')) {
    u = u.replace('/blob/', '/raw/')
  }
  return u
}

function loadGltf(obj) {
  const loader = new GLTFLoader()
  const sanitizedUrl = sanitizeGltfUrl(obj.url)
  loader.load(sanitizedUrl, (gltf) => {
    const model = gltf.scene
    model.position.set(...(obj.position ?? [0, 0, 0]))
    model.rotation.set(...(obj.rotation ?? [0, 0, 0]))
    model.scale.set(...(obj.scale ?? [1, 1, 1]))
    model.traverse((child) => {
      if (child.isMesh) {
        child.castShadow = true
        child.receiveShadow = true
      }
    })
    model.userData.isSlideObj = true
    scene?.add(model)
  })
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