<template>
  <StudentLayout>
    <div class="slide-view-root">

      <!-- Loading state -->
      <div class="loading-state" v-if="loading">
        <div class="loader-ring"></div>
        <span>Chargement des slides…</span>
      </div>

      <!-- Error state -->
      <div class="error-state" v-else-if="error">
        <p>{{ error }}</p>
        <button @click="loadSlides">Réessayer</button>
      </div>

      <!-- Empty state -->
      <div class="empty-state" v-else-if="!slides.length">
        <p>Aucune slide disponible pour cette classe.</p>
      </div>

      <!-- 3D Viewer -->
      <div class="viewer-wrapper" v-else>
        <ThreeScene
          :slides="slides"
          :initial-index="startIndex"
          :on-close="() => router.visit('/student/dashboard')"
          @slide-change="onSlideChange"
          @progress="onProgress"
        />
      </div>

    </div>
  </StudentLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import ThreeScene from '@/Components/ThreeScene.vue'
import axios from 'axios'

const props = defineProps({
  classroomId: [String, Number],
  slideId:     [String, Number],
})

const slides     = ref([])
const loading    = ref(true)
const error      = ref(null)
const startIndex = ref(0)

async function loadSlides() {
  loading.value = true
  error.value   = null
  try {
    const { data } = await axios.get(`/api/classrooms/${props.classroomId}/slides`)
    slides.value = data.data ?? data

    // Find the slide to start on
    if (props.slideId) {
      const idx = slides.value.findIndex(s => String(s.id) === String(props.slideId))
      startIndex.value = idx >= 0 ? idx : 0
    }
  } catch (e) {
    error.value = 'Impossible de charger les slides. Vérifiez votre inscription.'
  } finally {
    loading.value = false
  }
}

async function onProgress({ slideId, index }) {
  // Update enrollment progress via API
  try {
    // Find enrollment for this classroom
    const { data: enrollData } = await axios.get('/api/enrollments')
    const enrollments = enrollData.data ?? enrollData
    const enrollment = enrollments.find(e =>
      String(e.classroom?.id) === String(props.classroomId) ||
      String(e.classroom_id)  === String(props.classroomId)
    )
    if (enrollment) {
      await axios.put(`/api/enrollments/${enrollment.id}/progress`, {
        slide_id: slideId
      })
    }
  } catch { /* silent — don't break 3D experience */ }
}

function onSlideChange(index) {
  // Update URL without reloading (optional)
  const slide = slides.value[index]
  if (slide) {
    history.replaceState(
      {},
      '',
      `/student/classrooms/${props.classroomId}/slides/${slide.id}`
    )
  }
}

onMounted(() => loadSlides())
</script>

<style scoped>
.slide-view-root {
  height: calc(100vh - 64px); /* 64px = StudentLayout topbar height */
  display: flex;
  align-items: center;
  justify-content: center;
  background: #050b18;
}

.viewer-wrapper {
  width: 100%;
  height: 100%;
}

.loading-state,
.error-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  color: rgba(255,255,255,0.5);
  font-size: 0.9rem;
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

.error-state button {
  padding: 0.5rem 1.2rem;
  background: rgba(0,245,212,0.1);
  border: 1px solid rgba(0,245,212,0.3);
  border-radius: 8px;
  color: #00f5d4;
  cursor: pointer;
  font-size: 0.85rem;
}
</style>