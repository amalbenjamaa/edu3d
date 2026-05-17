<template>
  <TeacherLayout>
    <SlideEditorPanel :classroom-id="classroomId" />
  </TeacherLayout>
</template>

<script setup>
import TeacherLayout from '@/Layouts/TeacherLayout.vue'
import SlideEditorPanel from '@/Components/Slideeditor.vue'

defineProps({
  classroomId: [String, Number],
})
</script>
