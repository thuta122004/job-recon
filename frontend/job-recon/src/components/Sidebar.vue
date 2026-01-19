<script setup>
import { useRoute } from 'vue-router';
const route = useRoute();
</script>

<template>
    <aside class="hidden md:flex w-64 flex-col bg-white border-r border-gray-200">
        <div class="h-16 flex items-center px-6 border-b border-gray-100">
            <span class="text-xl font-bold text-indigo-700 tracking-tight italic">JOB RECON</span>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-1">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">Main</div>
            
            <router-link to="/" class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                active-class="bg-indigo-50 text-indigo-700 font-semibold">
                <i class="fa-solid fa-chart-pie w-5 mr-3"></i> Dashboard
            </router-link>

            <router-link to="/roles" class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                active-class="bg-indigo-50 text-indigo-700 font-semibold">
                <i class="fa-solid fa-shield-halved w-5 mr-3"></i> Roles
            </router-link>

            <router-link to="/users" class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                active-class="bg-indigo-50 text-indigo-700 font-semibold">
                <i class="fa-solid fa-user w-5 mr-3"></i> Users
            </router-link>
            
            </nav>
    </aside>
</template>