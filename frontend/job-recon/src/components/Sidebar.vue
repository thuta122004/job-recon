<script setup>
import { useRoute } from 'vue-router';
const route = useRoute();
</script>

<template>
        <aside class="hidden md:flex w-64 flex-col bg-white border-r border-gray-200">
            <div class="h-16 flex items-center px-6 border-b border-gray-100 gap-3">
                <div class="h-20 flex items-center px-7">
                    <div class="relative flex items-center">
                        <div class="absolute -left-2 h-10 w-10 bg-indigo-100/50 rounded-full blur-xl"></div>
                        
                        <i class="fa-solid fa-file-waveform text-indigo-600 text-xl relative z-10 mr-3"></i>
                        
                        <span class="relative z-10 text-xl font-extrabold text-indigo-600 tracking-tighter">
                            Job<span class="font-light text-gray-400">Recon</span>
                        </span>
                    </div>
                </div>
            </div>
        <nav class="flex-1 px-4 py-6 space-y-1">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">Main</div>

            <router-link to="/roles" class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                active-class="bg-indigo-50 text-indigo-700 font-semibold">
                <i class="fa-solid fa-shield-halved w-5 mr-3"></i> Access Roles
            </router-link>

            <router-link to="/skills" class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                active-class="bg-indigo-50 text-indigo-700 font-semibold">
                <i class="fa-solid fa-lightbulb w-5 mr-3"></i> Competency Library
            </router-link>

            <router-link to="/users" class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                active-class="bg-indigo-50 text-indigo-700 font-semibold">
                <i class="fa-solid fa-users-gear w-5 mr-3"></i> User Accounts
            </router-link>

            <router-link to="/job-seeker-profiles" class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                active-class="bg-indigo-50 text-indigo-700 font-semibold">
                <i class="fa-solid fa-address-card w-5 mr-3"></i> Talent Directory
            </router-link>
            
            </nav>
    </aside>
</template>