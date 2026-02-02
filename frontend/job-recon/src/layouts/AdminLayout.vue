<script setup>
import { useRoute, RouterView } from 'vue-router';

const route = useRoute();
</script>

<template>
    <div class="flex h-screen bg-gray-50">
        <aside class="hidden md:flex w-64 flex-col bg-white border-r border-gray-200">
            <div class="h-16 flex items-center px-6 border-b border-gray-100">
                <div class="relative flex items-center">
                    <div class="absolute -left-4 h-14 w-14 bg-indigo-100/40 rounded-full blur-xl"></div>
                    
                    <div class="relative z-10 flex-shrink-0">
                        <img src="@/assets/logo.svg" 
                            alt="JobRecon Logo" 
                            class="h-12 w-12 object-contain"
                            style="filter: invert(48%) sepia(13%) saturate(583%) hue-rotate(182deg) brightness(92%) contrast(88%);" />
                    </div>
                    
                    <span class="relative z-10 text-xl font-extrabold text-indigo-600 tracking-tighter -ml-2.5">
                        Job<span class="font-light text-gray-400">Recon</span>
                    </span>
                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">Main</div>

                <router-link :to="{ name: 'admin-dashboard' }" 
                    class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                    active-class="bg-indigo-50 text-indigo-700 font-semibold">
                    <i class="fa-solid fa-chart-pie w-5 mr-3"></i> Dashboard Overview
                </router-link>

                <router-link :to="{ name: 'roles' }" 
                    class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                    active-class="bg-indigo-50 text-indigo-700 font-semibold">
                    <i class="fa-solid fa-shield-halved w-5 mr-3"></i> Access Roles
                </router-link>

                <router-link :to="{ name: 'skills' }" 
                    class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                    active-class="bg-indigo-50 text-indigo-700 font-semibold">
                    <i class="fa-solid fa-lightbulb w-5 mr-3"></i> Competency Library
                </router-link>

                <router-link :to="{ name: 'users' }" 
                    class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                    active-class="bg-indigo-50 text-indigo-700 font-semibold">
                    <i class="fa-solid fa-users-gear w-5 mr-3"></i> User Accounts
                </router-link>

                <router-link :to="{ name: 'job-seeker-profiles' }" 
                    class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                    active-class="bg-indigo-50 text-indigo-700 font-semibold">
                    <i class="fa-solid fa-address-card w-5 mr-3"></i> Talent Directory
                </router-link>
                
                <router-link :to="{ name: 'job-categories' }" 
                    class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                    active-class="bg-indigo-50 text-indigo-700 font-semibold">
                    <i class="fa-solid fa-layer-group w-5 mr-3"></i> Job Categories
                </router-link>

                <router-link :to="{ name: 'employer-profiles' }" 
                    class="flex items-center px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg transition-all"
                    active-class="bg-indigo-50 text-indigo-700 font-semibold">
                    <i class="fa-solid fa-user-check w-5 mr-3"></i> Employers
                </router-link>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white border-b border-gray-200 px-8 flex items-center justify-between z-40">
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest leading-none mb-1">
                        {{ route.meta.parent || 'System Management' }}
                    </span>
                    <h1 class="text-lg font-bold text-gray-900 leading-none">
                        {{ route.meta.label || 'Admin Dashboard' }}
                    </h1>
                </div>

                <div class="flex items-center gap-4">
                    <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold">
                        H
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto bg-gray-50/50 p-8">
                <RouterView />
            </main>
        </div>
    </div>
</template>