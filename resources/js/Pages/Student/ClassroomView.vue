<template>
  <div class="p-8 text-neutral-200">
    <h1 class="text-xl font-semibold">Étudiant — Classe #{{ classroomId }}</h1>
    <p class="mt-2 text-sm text-neutral-400">Page placeholder (étape 4).</p>
  </div>
</template>

<script setup>
defineProps({
  classroomId: [String, Number],
})
</script>
