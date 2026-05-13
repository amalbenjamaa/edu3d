<template>
  <Link :href="href"
    class="px-4 py-2 rounded-lg text-sm transition-colors"
    :class="isActive
      ? 'bg-emerald-500/20 text-emerald-400 font-medium'
      : 'text-zinc-400 hover:text-white hover:bg-white/5'">
    {{ label }}
  </Link>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  href:  { type: String, required: true },
  label: { type: String, required: true },
})

const page     = usePage()
const isActive = computed(() => page.url.startsWith(props.href))
</script>