<script setup>
import ChatViewPanel from '@/Components/chatView.vue'

defineProps({
  isTeacher: { type: Boolean, default: false },
})
</script>

<template>
  <ChatViewPanel :is-teacher="isTeacher" />
</template>
