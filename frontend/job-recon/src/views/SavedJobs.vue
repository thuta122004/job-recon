<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { useToast } from 'vue-toastification';

const router = useRouter();
const toast = useToast();

const loading = ref(true);
const savedJobs = ref([]);

const fetchSavedJobs = async () => {
    loading.value = true;
    try {
        const seekerId = localStorage.getItem('job_seeker_profile_id');
        
        if (!seekerId) {
            toast.error("Profile ID not found. Please log in again.");
            return;
        }

        const response = await api.get(`/seeker/saved-jobs/${seekerId}`);
        
        if (response.data.status) {
            savedJobs.value = response.data.data;
        }
    } catch (error) {
        console.error("Saved Jobs Error:", error);
        toast.error("Failed to load saved jobs");
    } finally {
        loading.value = false;
    }
};

const formatCurrency = (value) => Number(value).toLocaleString();

const goToDetail = (slug) => {
    router.push({ name: 'job-detail', params: { slug } });
};

const unsaveJob = async (id, event) => {
    event.stopPropagation();
    try {
        const response = await api.post(`/seeker/jobs/${id}/toggle-save`);
        if (!response.data.is_saved) {
            savedJobs.value = savedJobs.value.filter(job => job.id !== id);
            toast.info("Removed from bookmarks");
        }
    } catch (error) {
        toast.error("Action failed");
    }
};

onMounted(fetchSavedJobs);
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] selection:bg-indigo-100">
        
        <nav class="bg-white/80 backdrop-blur-md border-b border-slate-100 sticky top-0 z-50">
            <div class="max-w-5xl mx-auto px-6 h-20 flex items-center justify-between">
                <button @click="router.back()" class="group flex items-center gap-2 text-slate-400 hover:text-indigo-600 font-bold text-xs uppercase tracking-widest transition-all">
                    <i class="fa-solid fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> 
                    Back to Discovery
                </button>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-4 py-2 bg-indigo-50 rounded-xl border border-indigo-100/50">
                        <i class="fa-solid fa-bookmark text-indigo-600 text-xs"></i>
                        <span class="text-[10px] font-black text-indigo-900 uppercase tracking-[0.2em]">
                            Saved Opportunities
                        </span>
                    </div>
                    
                    <button 
                        @click="fetchSavedJobs" 
                        :disabled="loading"
                        class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95"
                    >
                        <i class="fa-solid fa-rotate text-xs" :class="{'fa-spin': loading}"></i>
                    </button>
                </div>
            </div>
        </nav>

        <main class="max-w-5xl mx-auto px-6 py-16">
            <header class="mb-12">
                <h1 class="text-4xl font-black text-slate-900 tracking-tighter mb-2">My Bookmarks</h1>
                <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">You have {{ savedJobs.length }} jobs saved for later</p>
            </header>

            <div v-if="loading" class="space-y-6">
                <div v-for="i in 3" :key="i" class="h-40 bg-white animate-pulse rounded-[2.5rem] border border-slate-100"></div>
            </div>

            <div v-else-if="savedJobs.length === 0" class="bg-white rounded-[3rem] p-24 text-center border border-slate-100 shadow-sm">
                <div class="h-20 w-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fa-regular fa-bookmark text-3xl text-indigo-200"></i>
                </div>
                <h3 class="text-2xl font-black text-slate-900">Your list is empty.</h3>
                <p class="text-slate-400 font-medium mt-2 mb-8">Start exploring and save jobs you're interested in.</p>
                <button @click="router.push({ name: 'job-listings' })" class="px-8 py-4 bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-indigo-600 transition-all">
                    Find Jobs
                </button>
            </div>

            <div v-else class="grid grid-cols-1 gap-6">
                <div v-for="job in savedJobs" :key="job.slug" @click="goToDetail(job.slug)"
                    class="bg-white p-8 rounded-[2.5rem] border border-slate-100 hover:border-indigo-200 shadow-sm hover:shadow-xl hover:shadow-indigo-100/30 transition-all duration-500 group cursor-pointer relative overflow-hidden flex flex-col md:flex-row md:items-center gap-8">
                    
                    <div class="shrink-0">
                        <div class="h-16 w-16 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-50 group-hover:bg-white transition-all overflow-hidden">
                            <img v-if="job.employer?.company_logo_url" :src="job.employer.company_logo_url" class="h-full w-full object-cover" />
                            <i v-else class="fa-solid fa-briefcase text-slate-300 text-xl"></i>
                        </div>
                    </div>

                    <div class="flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-[9px] font-black uppercase px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-md">
                                {{ job.employment_type }}
                            </span>

                            <template v-if="job.salary_visible && job.salary_min !== null">
                                <span class="text-[11px] font-black text-emerald-600 uppercase tracking-tight">
                                    {{ job.currency }} {{ formatCurrency(job.salary_min) }} - {{ formatCurrency(job.salary_max) }}
                                </span>
                            </template>
                            <template v-else-if="job.salary_visible">
                                <span class="text-[11px] font-black text-slate-400 italic uppercase tracking-tight">
                                    Negotiable
                                </span>
                            </template>
                            <template v-else>
                                <span class="text-[11px] font-black text-slate-400 italic uppercase tracking-tight">
                                    Competitive
                                </span>
                            </template>
                        </div>

                        <h3 class="text-xl font-black text-slate-900 group-hover:text-indigo-600 transition-colors tracking-tight">
                            {{ job.title }}
                        </h3>
                        <p class="text-slate-400 font-bold text-[10px] uppercase tracking-tight">
                            {{ job.employer?.company_name }} • {{ job.location }}
                        </p>
                    </div>

                    <div class="flex items-center gap-4 border-t md:border-t-0 pt-6 md:pt-0">
                        <button @click="unsaveJob(job.id, $event)" class="h-12 w-12 rounded-2xl border border-slate-100 text-slate-300 hover:text-rose-500 hover:bg-rose-50 hover:border-rose-100 transition-all" title="Remove">
                            <i class="fa-solid fa-trash-can text-sm"></i>
                        </button>
                        <div class="h-12 w-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center group-hover:bg-indigo-600 transition-all shadow-lg shadow-slate-200 group-hover:shadow-indigo-100">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>