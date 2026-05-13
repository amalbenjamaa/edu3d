<template>
  <div class="min-h-screen bg-zinc-950 text-white flex flex-col">

    <!-- Topbar Étudiant -->
    <header class="h-16 bg-zinc-900 border-b border-zinc-800 flex items-center px-6 gap-4">

      <!-- Logo -->
      <span class="text-xl font-bold tracking-tight mr-4">
        <span class="text-emerald-400">edu</span>3d
      </span>

      <!-- Navigation horizontale -->
      <nav class="flex items-center gap-1 flex-1">
        <TopNavItem href="/student/dashboard" label="Accueil" />
        <TopNavItem href="/student/courses"   label="Catalogue" />
        <TopNavItem href="/student/my-courses" label="Mes cours" />
      </nav>

      <!-- Avatar + déconnexion -->
      <div class="flex items-center gap-3">
        <div class="text-sm text-zinc-400">{{ auth.user?.name }}</div>
        <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center text-sm font-semibold">
          {{ auth.user?.name?.[0]?.toUpperCase() }}
        </div>
        <button @click="auth.logout()"
          class="text-xs text-zinc-500 hover:text-red-400 transition-colors px-2 py-1 rounded hover:bg-red-500/10">
          ←
        </button>
      </div>
    </header>

    <!-- Main content -->
    <main class="flex-1 overflow-auto">
      <slot />
    </main>

  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/useAuthStore'
import TopNavItem from '@/Components/TopNavItem.vue'
const auth = useAuthStore()
</script>