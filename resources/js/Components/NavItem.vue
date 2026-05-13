<template>
  <Link :href="href"
    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors"
    :class="isActive
      ? 'bg-white/10 text-white font-medium'
      : 'text-gray-400 hover:text-white hover:bg-white/5'">
    <span class="text-base leading-none">{{ icon }}</span>
    <span>{{ label }}</span>
  </Link>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  href:  { type: String, required: true },
  icon:  { type: String, required: true },
  label: { type: String, required: true },
})

const page     = usePage()
const isActive = computed(() => page.url.startsWith(props.href))
</script>