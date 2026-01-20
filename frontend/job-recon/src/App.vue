<script setup>
import { RouterView, useRoute } from 'vue-router'
import Sidebar from '@/components/Sidebar.vue'
import { computed } from 'vue'

const route = useRoute()

const parentLabel = computed(() => route.meta.parent || 'Dashboard')
const currentLabel = computed(() => route.meta.label || 'Overview')
</script>

<template>
  <div class="flex h-screen bg-gray-50 text-gray-800 antialiased">
    <Sidebar />

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
      <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8">
        <div class="flex items-center text-sm text-gray-500 capitalize">
           <span>{{ parentLabel }}</span>
           <i class="fa-solid fa-chevron-right text-[10px] mx-2"></i>
           <span class="text-indigo-600 font-medium">{{ currentLabel }}</span>
        </div>

        <div class="flex items-center gap-4">
           <div class="h-10 w-10 bg-indigo-600 rounded-full flex items-center justify-center text-white text-xs">
              T
           </div>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto p-8">
        <RouterView />
      </main>
    </div>
  </div>
</template>