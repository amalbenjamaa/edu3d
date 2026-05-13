<template>
  <div class="p-8 text-neutral-200">
    <h1 class="text-xl font-semibold">Étudiant — Slide {{ slideId }} (classe {{ classroomId }})</h1>
    <p class="mt-2 text-sm text-neutral-400">Page placeholder (étape 4) — intégration Three.js à l’étape 5.</p>
  </div>
</template>

<script setup>
defineProps({
  classroomId: [String, Number],
  slideId: [String, Number],
})
</script>
