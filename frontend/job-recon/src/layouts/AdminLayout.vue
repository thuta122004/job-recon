<script setup>
import { ref } from 'vue';
import { useRoute, useRouter, RouterView } from 'vue-router';
import { useToast } from "vue-toastification";
import api from '@/services/api';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const userName = localStorage.getItem('user_name') || 'Admin';
const userEmail = localStorage.getItem('user_email') || 'admin@jobrecon.com';
const userInitial = userName.charAt(0).toUpperCase();

const isLoggingOut = ref(false);
const showConfirmModal = ref(false);

const confirmConfig = ref({ 
    title: '', 
    message: '', 
    action: null, 
    type: 'danger', 
    icon: '' 
});

const requestLogout = () => {
    confirmConfig.value = {
        title: 'Sign Out?',
        message: 'Are you sure you want to end your current session? You will need to log back in to access the system.',
        type: 'danger',
        icon: 'fa-right-from-bracket',
        action: executeLogout
    };
    showConfirmModal.value = true;
};

const executeLogout = async () => {
    isLoggingOut.value = true;
    try {
        await api.post('/logout');
    } catch (error) {
        console.error("Logout request failed, cleaning up local storage anyway.", error);
    } finally {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_role');
        localStorage.removeItem('user_name');
        
        showConfirmModal.value = false;
        toast.success("Successfully Signed Out");

        setTimeout(() => {
            router.push({ name: 'login' });
        }, 1000);
    }
};
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
                    <i class="fa-solid fa-user-check w-5 mr-3"></i> Company Directory
                </router-link>
            </nav>

            <div class="p-4 border-t border-gray-100">
                <p class="px-4 text-[10px] text-gray-400 font-bold uppercase tracking-[0.15em] mb-2">
                    System Administrator
                </p>
                
                <button @click="requestLogout" 
                    class="w-full flex items-center px-4 py-3 text-rose-500 hover:bg-rose-50 rounded-lg transition-all font-medium">
                    <i class="fa-solid fa-right-from-bracket w-5 mr-3"></i> Sign Out
                </button>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white border-b border-gray-200 px-8 flex items-center justify-between z-40">
                <div class="flex flex-col text-left">
                    <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest leading-none mb-1">
                        {{ route.meta.parent || 'System Management' }}
                    </span>
                    <h1 class="text-lg font-bold text-gray-900 leading-none">
                        {{ route.meta.label || 'Admin Dashboard' }}
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-right mr-2 hidden sm:block">
                        <p class="text-sm font-bold text-gray-900 leading-none">{{ userName }}</p>
                        <p class="text-[10px] text-indigo-500 font-medium mt-0.5 leading-none">{{ userEmail }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-indigo-100">
                        {{ userInitial }}
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto bg-gray-50/50 p-8">
                <RouterView />
            </main>
        </div>

        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showConfirmModal" class="fixed inset-0 z-[110] flex items-center justify-center p-6 text-left">
                <div @click="showConfirmModal = false" class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm"></div>
                
                <div class="relative max-w-sm w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div :class="{
                            'bg-rose-50 text-rose-600': confirmConfig.type === 'danger',
                            'bg-indigo-50 text-indigo-600': confirmConfig.type === 'indigo'
                        }" class="h-12 w-12 rounded-xl flex items-center justify-center mb-4 text-xl">
                            <i :class="['fa-solid', confirmConfig.icon]"></i>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">{{ confirmConfig.title }}</h3>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ confirmConfig.message }}</p>
                        
                        <div class="mt-8 flex gap-3">
                            <button @click="showConfirmModal = false" 
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-gray-500 bg-gray-50 hover:bg-gray-100 transition-colors">
                                Discard
                            </button>
                            <button @click="confirmConfig.action" 
                                :disabled="isLoggingOut"
                                :class="{
                                    'bg-rose-500 hover:bg-rose-600': confirmConfig.type === 'danger',
                                    'bg-indigo-600 hover:bg-indigo-700': confirmConfig.type === 'indigo'
                                }"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg transition-all active:scale-95 disabled:opacity-50">
                                {{ isLoggingOut ? 'Ending...' : 'Confirm' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>