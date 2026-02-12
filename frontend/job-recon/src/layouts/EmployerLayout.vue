<script setup>
import { computed, ref, onMounted } from 'vue';
import { useRoute, useRouter, RouterView } from 'vue-router';
import { useToast } from "vue-toastification";
import api from '@/services/api';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const token = localStorage.getItem('auth_token');
const isAuthenticated = computed(() => !!token);

const userName = localStorage.getItem('user_name') || 'Employer';
const userEmail = localStorage.getItem('user_email') || 'hiring@jobrecon.com';

const isVerified = computed(() => {
    const status = localStorage.getItem('user_is_verified');
    return status === 'true' || status === '1' || status === true;
});

const companyLogo = ref(null);
const isLoggingOut = ref(false);
const showConfirmModal = ref(false);

const confirmConfig = ref({ 
    title: '', 
    message: '', 
    action: null, 
    type: 'danger', 
    icon: '' 
});

onMounted(() => {
    companyLogo.value = localStorage.getItem('company_logo');
});

const requestLogout = () => {
    confirmConfig.value = {
        title: 'Sign Out?',
        message: 'Are you sure you want to log out of the Employer Portal? You will need to sign in again to manage your job postings.',
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
        console.error("Logout failed, cleaning storage anyway.");
    } finally {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_role');
        localStorage.removeItem('user_name');
        localStorage.removeItem('user_email');
        localStorage.removeItem('company_logo');
        localStorage.removeItem('user_is_verified');
        localStorage.removeItem('user_profile_pic');
        localStorage.removeItem('user_id');

        showConfirmModal.value = false;
        toast.success("Successfully Signed Out");

        setTimeout(() => {
            router.push({ name: 'login' });
        }, 1000);
    }
};
</script>

<template>
    <div class="min-h-screen bg-white selection:bg-indigo-100 selection:text-indigo-700 text-left">
        <nav class="bg-white/80 backdrop-blur-xl border-b border-slate-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                
                <div class="flex items-center gap-12">
                    <router-link :to="{ name: 'employer-home' }" class="relative flex items-center group">
                        <div class="absolute -left-3 h-12 w-12 bg-indigo-50 rounded-full blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative z-10 flex-shrink-0">
                            <img src="@/assets/logo.svg" 
                                alt="JobRecon Logo" 
                                class="h-10 w-10 object-contain"
                                style="filter: invert(48%) sepia(13%) saturate(583%) hue-rotate(182deg) brightness(92%) contrast(88%);" />
                        </div>
                        <span class="relative z-10 text-xl font-black text-slate-900 tracking-tighter ml-1">
                            Job<span class="text-indigo-600">Recon</span>
                        </span>
                    </router-link>

                    <div class="hidden md:flex items-center gap-1">
                        <router-link :to="{ name: 'employer-home' }" 
                            class="px-4 py-2 text-[11px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 transition-all"
                            active-class="text-indigo-600">
                            Dashboard
                        </router-link>
                        <router-link to="/employer/jobs" 
                            class="px-4 py-2 text-[11px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 transition-all"
                            active-class="text-indigo-600">
                            Manage Jobs
                        </router-link>
                    </div>
                </div>

                <div class="flex items-center gap-5">
                    <template v-if="isAuthenticated">
                        <div class="hidden sm:flex flex-col items-end mr-1 text-right">
                            <span class="text-[10px] font-black text-slate-900 uppercase tracking-widest leading-none mb-1">
                                {{ userName }}
                            </span>
                            <span v-if="isVerified" class="text-[9px] font-bold text-emerald-500 uppercase leading-none flex items-center gap-1">
                                <i class="fa-solid fa-circle-check text-[10px]"></i> Official Partner
                            </span>
                            <span v-else class="text-[9px] font-bold text-indigo-500 uppercase leading-none opacity-70">
                                {{ userEmail }}
                            </span>
                        </div>

                        <div class="group relative">
                            <div class="h-12 w-12 rounded-2xl bg-slate-100 overflow-hidden border border-slate-200 shadow-xl shadow-slate-200 cursor-pointer group-hover:-rotate-3 transition-all duration-300 flex items-center justify-center">
                                <img v-if="companyLogo && companyLogo !== ''" 
                                    :src="companyLogo" 
                                    @error="(e) => e.target.style.display = 'none'"
                                    alt="Company Logo" 
                                    class="h-full w-full object-cover" />
                                
                                <div v-else class="h-full w-full flex items-center justify-center bg-slate-900 text-white text-xs font-black">
                                    {{ userName.charAt(0).toUpperCase() }}
                                </div>
                            </div>
                            
                            <div class="absolute right-0 mt-4 w-60 bg-white rounded-[2.5rem] shadow-2xl shadow-indigo-100/60 border border-slate-50 py-4 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                                <div class="px-8 py-4 border-b border-slate-50 mb-3 text-left">
                                    <p class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em]">Employer Portal</p>
                                    <p class="text-[11px] font-black text-slate-900 uppercase tracking-tight truncate leading-tight">{{ userName }}</p>
                                </div>

                                <router-link to="/employer/profile" class="flex items-center px-8 py-3 text-[10px] font-black text-slate-500 hover:text-indigo-600 uppercase tracking-widest transition-colors">
                                    <i class="fa-solid fa-user-check mr-3 text-sm opacity-40"></i> Profile
                                </router-link>

                                <router-link to="/employer/jobs/create" class="flex items-center px-8 py-3 text-[10px] font-black text-slate-500 hover:text-indigo-600 uppercase tracking-widest transition-colors">
                                    <i class="fa-solid fa-plus-circle mr-3 text-sm opacity-40"></i> Post Job
                                </router-link>

                                <div class="mt-3 pt-3 border-t border-slate-50">
                                    <button @click="requestLogout" class="w-full flex items-center px-8 py-3 text-[10px] font-black text-rose-500 hover:bg-rose-50 uppercase tracking-widest transition-all rounded-b-xl text-left">
                                        <i class="fa-solid fa-power-off mr-3 text-sm opacity-50"></i> Sign Out
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <router-link :to="{ name: 'login' }" 
                            class="px-6 py-3 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all transform active:scale-95">
                            Sign In
                        </router-link>
                    </template>
                </div>
            </div>
        </nav>

        <main class="relative">
            <RouterView />
        </main>

        <footer class="bg-white py-20 mt-20 border-t border-slate-100">
            <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-10">
                <div class="flex items-center gap-1">
                    <img src="@/assets/logo.svg" class="h-6 w-6 opacity-30 grayscale" />
                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-tighter">
                        JobRecon &copy; 2026 — Employer Portal.
                    </span>
                </div>
                <div class="flex gap-8">
                    <a href="#" class="text-[10px] font-black text-slate-300 hover:text-indigo-600 transition-colors uppercase tracking-[0.2em]">Partner Help</a>
                    <a href="#" class="text-[10px] font-black text-slate-300 hover:text-indigo-600 transition-colors uppercase tracking-[0.2em]">Terms</a>
                </div>
            </div>
        </footer>

        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showConfirmModal" class="fixed inset-0 z-[110] flex items-center justify-center p-6">
                <div @click="showConfirmModal = false" class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm"></div>
                
                <div class="relative max-w-sm w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div class="bg-rose-50 text-rose-600 h-12 w-12 rounded-xl flex items-center justify-center mb-4 text-xl">
                            <i :class="['fa-solid', confirmConfig.icon]"></i>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">{{ confirmConfig.title }}</h3>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ confirmConfig.message }}</p>
                        
                        <div class="mt-8 flex gap-3">
                            <button @click="showConfirmModal = false" 
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-gray-500 bg-gray-50 hover:bg-gray-100 transition-colors">
                                Cancel
                            </button>
                            <button @click="confirmConfig.action" 
                                :disabled="isLoggingOut"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-rose-500 hover:bg-rose-600 shadow-lg shadow-rose-100 transition-all active:scale-95 disabled:opacity-50">
                                {{ isLoggingOut ? 'Signing Out...' : 'Confirm' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>