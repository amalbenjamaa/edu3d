
<template>
  <div class="min-h-screen bg-slate-950 text-white flex">

    <!-- Sidebar Enseignant -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col">

      <!-- Logo -->
      <div class="px-6 py-5 border-b border-slate-800">
        <span class="text-xl font-bold tracking-tight">
          <span class="text-violet-400">edu</span>3d
        </span>
        <span class="ml-2 text-xs bg-violet-500/20 text-violet-400 px-2 py-0.5 rounded-full">Enseignant</span>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-1">
        <NavItem href="/teacher/dashboard"  icon="🏠" label="Dashboard" />
        <NavItem href="/teacher/courses"    icon="📚" label="Mes cours" />
        <NavItem href="/teacher/classrooms" icon="🏫" label="Mes classes" />
        <NavItem href="/teacher/slides"     icon="🎞️" label="Mes slides" />
        <NavItem href="/teacher/students"   icon="🎓" label="Étudiants" />
      </nav>

      <!-- Raccourci création rapide -->
      <div class="px-4 py-3 border-t border-slate-800">
        <Link href="/teacher/courses/create"
          class="w-full flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-500 transition-colors text-white text-sm font-medium px-4 py-2 rounded-lg">
          + Nouveau cours
        </Link>
      </div>

      <!-- User info + logout -->
      <div class="px-4 py-4 border-t border-slate-800">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 rounded-full bg-violet-600 flex items-center justify-center text-sm font-semibold">
            {{ auth.user?.name?.[0]?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ auth.user?.name }}</p>
            <p class="text-xs text-slate-400 truncate">{{ auth.user?.email }}</p>
          </div>
        </div>
        <button @click="auth.logout()">Déconnexion</button>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-auto">
      <slot />
    </main>

  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/useAuthStore'
import NavItem from '@/Components/NavItem.vue'
const auth = useAuthStore()
</script>