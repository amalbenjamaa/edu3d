<!-- resources/js/Components/three/MiniScene.vue -->
<template>
  <canvas ref="canvasRef" style="width:100%;height:100%;display:block;"/>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import * as THREE from 'three'

const props = defineProps({
  slide: { type: Object, required: true },
})

const canvasRef = ref(null)
let renderer, scene, camera, animId

function init() {
  const canvas = canvasRef.value
  if (!canvas) return
  const W = canvas.clientWidth  || 80
  const H = canvas.clientHeight || 50

  scene = new THREE.Scene()
  scene.background = new THREE.Color(0x060914)

  const cam = props.slide.camera
  camera = new THREE.PerspectiveCamera(cam?.fov ?? 60, W / H, 0.1, 200)
  camera.position.set(...(cam?.position ?? [4, 3, 4]))
  camera.lookAt(...(cam?.target ?? [0, 0, 0]))

  renderer = new THREE.WebGLRenderer({ canvas, antialias: true })
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2))
  renderer.setSize(W, H, false)
  renderer.shadowMap.enabled = true

  scene.add(new THREE.AmbientLight(0xffffff, 0.5))
  const dir = new THREE.DirectionalLight(0xffffff, 1.2)
  dir.position.set(5, 8, 5)
  dir.castShadow = true
  scene.add(dir)

  loadObjects()

  let t = 0
  function animate() {
    animId = requestAnimationFrame(animate)
    t += 0.008
    scene.rotation.y = t
    renderer.render(scene, camera)
  }
  animate()
}

function loadObjects() {
  const content = props.slide.content ?? []
  if (!content.length) {
    scene?.add(new THREE.Mesh(
      new THREE.BoxGeometry(1.5, 1.5, 1.5),
      new THREE.MeshStandardMaterial({ color: 0x6366f1, wireframe: true })
    ))
    return
  }
  content.forEach(obj => {
    const mesh = buildMesh(obj)
    if (mesh) scene?.add(mesh)
  })
}

function buildMesh(obj) {
  const mat = new THREE.MeshStandardMaterial({
    color: new THREE.Color(obj.color ?? '#6366f1'),
    transparent: (obj.opacity ?? 1) < 1,
    opacity: obj.opacity ?? 1,
  })
  let geo
  switch (obj.type) {
    case 'box':    geo = new THREE.BoxGeometry(1, 1, 1); break
    case 'sphere': geo = new THREE.SphereGeometry(0.6, 16, 16); break
    case 'plane':  geo = new THREE.PlaneGeometry(2, 2); break
    case 'text3d': geo = new THREE.BoxGeometry(1.4, 0.25, 0.1); mat.color.set(0xffffff); break
    default:       geo = new THREE.BoxGeometry(0.8, 0.8, 0.8)
  }
  const mesh = new THREE.Mesh(geo, mat)
  mesh.position.set(...(obj.position ?? [0,0,0]))
  mesh.rotation.set(...(obj.rotation ?? [0,0,0]))
  mesh.scale.set(...(obj.scale ?? [1,1,1]))
  mesh.castShadow = mesh.receiveShadow = true
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
watch(() => props.slide, () => { dispose(); init() }, { deep: true })
</script>
