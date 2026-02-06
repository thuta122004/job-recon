<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '@/services/api';

const router = useRouter();
const route = useRoute();

const loading = ref(true);
const jobs = ref([]);
const categories = ref([]);

const filters = ref({
    q: route.query.q || '',
    l: route.query.l || '',
    category: route.query.category || ''
});

let debounceTimer = null;

const fetchDiscoveryData = async () => {
    loading.value = true;
    try {
        const [jobsRes, homeRes] = await Promise.all([
            api.get('/seeker/jobs', { params: filters.value }),
            api.get('/seeker/home-data')
        ]);
        
        if (jobsRes.data.status) {
            jobs.value = jobsRes.data.data;
        }

        if (homeRes.data.categories) {
            categories.value = homeRes.data.categories;
        }
    } catch (error) {
        console.error("Discovery Error:", error);
    } finally {
        loading.value = false;
    }
};

const refreshJobs = async () => {
    try {
        const response = await api.get('/seeker/jobs', { params: filters.value });
        if (response.data.status) {
            jobs.value = response.data.data;
        }
    } catch (error) {
        console.error("Filter Error:", error);
    }
};

watch([() => filters.value.q, () => filters.value.l, () => filters.value.category], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.replace({ query: { ...filters.value } });
        refreshJobs();
    }, 400);
});

const formatCurrency = (value) => {
    return Number(value).toLocaleString();
};

const goToDetail = (slug) => {
    router.push({ name: 'job-detail', params: { slug } });
};

onMounted(fetchDiscoveryData);
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] selection:bg-indigo-100 selection:text-indigo-700">
        
        <nav class="bg-white/80 backdrop-blur-md border-b border-slate-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                <button @click="router.push({ name: 'seeker-home' })" class="group flex items-center gap-2 text-slate-400 hover:text-indigo-600 font-bold text-xs uppercase tracking-widest transition-all">
                    <i class="fa-solid fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> 
                    Job Discovery
                </button>
                <div class="flex items-center gap-3">
                    <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span class="text-[10px] font-black text-slate-900 uppercase tracking-[0.2em]">Live Results</span>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-12 items-start">
                
                <aside class="w-full lg:w-80 space-y-6 lg:sticky lg:top-32">
                    <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm">
                        <h2 class="text-[10px] font-black text-slate-900 uppercase tracking-[0.3em] mb-10">Refine Search</h2>

                        <div class="space-y-8">
                            <div>
                                <label class="text-[9px] font-black text-slate-300 uppercase tracking-widest mb-3 block">Role or Company</label>
                                <div class="relative group">
                                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-indigo-500 transition-colors"></i>
                                    <input v-model="filters.q" type="text" placeholder="e.g. Instructor" class="w-full pl-11 pr-4 py-4 bg-slate-50 border-none rounded-2xl text-xs font-bold text-slate-700 placeholder:text-slate-300 focus:ring-2 focus:ring-indigo-100 transition-all" />
                                </div>
                            </div>

                            <div>
                                <label class="text-[9px] font-black text-slate-300 uppercase tracking-widest mb-3 block">Location</label>
                                <div class="relative group">
                                    <i class="fa-solid fa-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-indigo-500 transition-colors"></i>
                                    <input v-model="filters.l" type="text" placeholder="City or Remote" class="w-full pl-11 pr-4 py-4 bg-slate-50 border-none rounded-2xl text-xs font-bold text-slate-700 placeholder:text-slate-300 focus:ring-2 focus:ring-indigo-100 transition-all" />
                                </div>
                            </div>

                            <button @click="filters = { q: '', l: '', category: '' }" class="w-full py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-rose-500 transition-colors border border-slate-50 rounded-2xl hover:bg-rose-50 hover:border-rose-100">
                                Reset Filters
                            </button>
                        </div>
                    </div>

                    <div class="p-10 rounded-[3rem] bg-white border border-slate-100 shadow-sm relative overflow-hidden">
    <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-50/30 rounded-full -mr-12 -mt-12"></div>
    
    <div class="relative z-10">
        <p class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] mb-8 flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span>
            Popular Industries
        </p>
        
                        <div class="space-y-3">
                            <button 
                                v-for="cat in categories" 
                                :key="cat.slug"
                                @click="filters.category = cat.slug"
                                :class="filters.category === cat.slug ? 'bg-indigo-600 text-white shadow-indigo-100' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                                class="w-full flex items-center justify-between px-5 py-3.5 rounded-2xl transition-all group"
                            >
                                <span class="text-[11px] font-black uppercase tracking-tight">{{ cat.name }}</span>
                                <span 
                                    :class="filters.category === cat.slug ? 'bg-white/20 text-white' : 'bg-white text-slate-400'"
                                    class="text-[9px] px-2 py-0.5 rounded-lg font-bold"
                                >
                                    {{ cat.job_posts_count }}
                                </span>
                            </button>
                        </div>

                        <p class="mt-8 text-[10px] text-slate-400 font-bold uppercase tracking-widest text-center">
                            {{ jobs.length }} Jobs Found
                        </p>
                    </div>
                </div>
                </aside>

                <div class="flex-1 w-full space-y-8">
                    
                    <div v-if="loading" class="space-y-8">
                        <div v-for="i in 3" :key="i" class="h-64 bg-white animate-pulse rounded-[3rem] border border-slate-100"></div>
                    </div>

                    <div v-else-if="jobs.length === 0" class="bg-white rounded-[3rem] p-24 text-center border border-slate-100 shadow-sm">
                        <div class="h-20 w-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fa-solid fa-ghost text-3xl text-slate-200"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900">No matches found.</h3>
                        <p class="text-slate-400 font-medium mt-2">Try adjusting your filters or keywords.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-8">
                        <div v-for="job in jobs" :key="job.slug" @click="goToDetail(job.slug)"
                            class="bg-white p-10 rounded-[3rem] border border-slate-100 hover:border-indigo-200 shadow-sm hover:shadow-2xl hover:shadow-indigo-100/40 hover:-translate-y-1 transition-all duration-500 group cursor-pointer relative overflow-hidden flex flex-col md:flex-row md:items-center gap-10">
                            
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/0 to-indigo-50/0 group-hover:to-indigo-50/50 transition-colors duration-500 pointer-events-none"></div>

                            <div class="relative shrink-0 z-10">
                                <div class="h-20 w-20 bg-slate-50 rounded-[1.5rem] flex items-center justify-center border border-slate-50 group-hover:bg-white group-hover:border-indigo-100 transition-all duration-500 overflow-hidden">
                                    <img v-if="job.employer?.company_logo_url" :src="job.employer.company_logo_url" class="h-full w-full object-cover" />
                                    <i v-else class="fa-solid fa-briefcase text-slate-300 group-hover:text-indigo-500 text-2xl"></i>
                                </div>
                                <div 
                                    v-if="job.employer?.is_verified == 1 || job.employer?.is_verified == true" 
                                    class="absolute -bottom-1 -right-1 bg-white rounded-full p-0.5 shadow-sm z-30 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-circle-check text-emerald-400 text-[14px]"></i>
                                </div>
                            </div>

                            <div class="flex-1 space-y-3 relative z-10">
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[9px] font-black uppercase rounded-lg">{{ job.employment_type }}</span>
                                    <span class="px-3 py-1 bg-slate-50 text-slate-500 text-[9px] font-black uppercase rounded-lg">{{ job.category?.name || 'General' }}</span>
                                </div>
                                <h3 class="text-3xl font-black text-slate-900 group-hover:text-indigo-600 transition-colors leading-tight tracking-tighter">{{ job.title }}</h3>
                                <p class="text-slate-400 font-bold text-xs uppercase tracking-tight">{{ job.employer?.company_name }} • {{ job.location }}</p>
                            </div>

                            <div class="shrink-0 text-left md:text-right flex flex-row md:flex-col justify-between items-center md:items-end gap-6 border-t md:border-t-0 md:border-l border-slate-50 pt-8 md:pt-0 md:pl-10 relative z-10">
                                <div>
                                    <p class="text-[9px] font-black text-slate-300 uppercase tracking-widest mb-1">Financial Range</p>
                                    
                                    <p v-if="job.salary_visible && job.salary_min !== null" class="text-xl font-black text-slate-900">
                                        {{ job.currency }} {{ formatCurrency(job.salary_min) }}
                                    </p>
                                    
                                    <p v-else-if="job.salary_visible" class="text-lg font-black text-slate-400 italic">
                                        Negotiable
                                    </p>
                                    
                                    <p v-else class="text-lg font-black text-slate-400 italic">
                                        Competitive
                                    </p>
                                </div>
                                <div class="h-12 w-12 rounded-full bg-slate-900 text-white flex items-center justify-center group-hover:bg-indigo-600 group-hover:rotate-[-45deg] transition-all duration-500 shadow-lg shadow-slate-200 group-hover:shadow-indigo-200">
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>