<template>
  <div class="min-h-screen bg-gray-950 text-white flex">

    <!-- Sidebar Admin -->
    <aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col">

      <!-- Logo -->
      <div class="px-6 py-5 border-b border-gray-800">
        <span class="text-xl font-bold tracking-tight">
          <span class="text-indigo-400">edu</span>3d
        </span>
        <span class="ml-2 text-xs bg-red-500/20 text-red-400 px-2 py-0.5 rounded-full">Admin</span>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-1">
        <NavItem href="/admin/dashboard"  icon="📊" label="Dashboard" />
        <NavItem href="/admin/users"      icon="👥" label="Utilisateurs" />
        <NavItem href="/admin/courses"    icon="📚" label="Cours" />
        <NavItem href="/admin/classrooms" icon="🏫" label="Classes" />
      </nav>

      <!-- User info + logout -->
      <div class="px-4 py-4 border-t border-gray-800">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-sm font-semibold">
            {{ auth.user?.name?.[0]?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ auth.user?.name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</p>
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
import { useAuthStore } from '@/stores/useAuthStore'
import NavItem from '@/Components/NavItem.vue'
const auth = useAuthStore()
</script>