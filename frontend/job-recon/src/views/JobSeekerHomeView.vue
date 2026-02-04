<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();
const loading = ref(true);
const searchQuery = ref('');
const locationQuery = ref('');

const homeData = ref({
    stats: { activeJobs: 0, topCompanies: 0 },
    categories: [],
    featuredJobs: []
});

const fetchHomeData = async () => {
    loading.value = true;
    try {
        const response = await api.get('/seeker/home-data');
        homeData.value = response.data;
    } catch (error) {
        console.error("Discovery Engine Error:", error);
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    router.push({
        path: '/seeker/jobs',
        query: { 
            q: searchQuery.value.trim() || undefined, 
            l: locationQuery.value.trim() || undefined 
        }
    });
};

const filterByCategory = (categorySlug) => {
    router.push({
        path: '/seeker/jobs',
        query: { category: categorySlug } 
    });
};

const goToJobDetail = (slug) => {
    router.push({ name: 'job-detail', params: { slug: slug } });
};

const formatCurrency = (value) => {
    return Number(value).toLocaleString();
};

onMounted(fetchHomeData);
</script>

<template>
    <div class="min-h-screen bg-white selection:bg-indigo-100 selection:text-indigo-700">
        <div class="relative pt-20 pb-32 overflow-hidden">
            <div class="absolute inset-0 z-0 pointer-events-none">
                <div class="absolute top-[-10%] left-[-10%] h-[600px] w-[600px] bg-indigo-50 rounded-full blur-[120px] animate-pulse"></div>
                <div class="absolute bottom-0 right-[-5%] h-[400px] w-[400px] bg-blue-50/60 rounded-full blur-[100px]"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-6 text-center">
                <h1 class="text-6xl md:text-8xl font-black text-slate-900 tracking-tighter leading-[0.85] mb-8">
                    Discover your <br/> 
                    <span class="text-indigo-600 italic">perfect</span> match.
                </h1>
                
                <p class="max-w-xl mx-auto text-slate-500 font-medium text-lg mb-12">
                    <template v-if="!loading">
                        Connect with {{ homeData.stats.topCompanies }} top companies hiring for {{ homeData.stats.activeJobs }} open roles today.
                    </template>
                    <template v-else>
                        Loading the latest opportunities for you...
                    </template>
                </p>

                <form @submit.prevent="handleSearch" class="max-w-5xl mx-auto bg-white p-3 rounded-[3rem] shadow-2xl shadow-indigo-100/50 border border-slate-100 flex flex-col md:flex-row items-center gap-3">
                    <div class="flex-1 flex items-center gap-3 px-6 w-full group">
                        <i class="fa-solid fa-magnifying-glass text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Title, keywords, or company..." 
                            class="w-full py-4 bg-transparent border-none outline-none focus:ring-0 font-bold text-slate-700 placeholder:text-slate-300" 
                        />
                    </div>

                    <div class="hidden md:block w-px h-8 bg-slate-100"></div>

                    <div class="flex-1 flex items-center gap-3 px-6 w-full group">
                        <i class="fa-solid fa-location-dot text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                        <input 
                            v-model="locationQuery"
                            type="text" 
                            placeholder="City or Remote" 
                            class="w-full py-4 bg-transparent border-none outline-none focus:ring-0 font-bold text-slate-700 placeholder:text-slate-300" 
                        />
                    </div>

                    <button type="submit" class="w-full md:w-auto px-12 py-5 bg-indigo-600 text-white font-black rounded-[2.5rem] hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 active:scale-95">
                        FIND JOBS
                    </button>
                </form>

                <div class="mt-12 flex flex-wrap justify-center gap-3">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest w-full mb-2">
                        Trending Categories
                    </span>
                    
                    <button v-for="cat in homeData.categories" :key="cat.slug" 
                        @click="filterByCategory(cat.slug)"
                        class="flex items-center gap-3 px-4 py-2 bg-white border border-slate-100 rounded-full text-[11px] font-bold text-slate-600 hover:border-indigo-600 hover:text-indigo-600 transition-all shadow-sm shadow-slate-200/50 active:scale-95 group"
                    >
                        <i :class="cat.icon_class || 'fa-solid fa-layer-group'" 
                        class="text-indigo-500 text-sm group-hover:scale-110 transition-transform">
                        </i>

                        <span>{{ cat.name }}</span>

                        <span class="bg-slate-50 px-2 py-0.5 rounded-full text-[9px] text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-400 transition-colors">
                            {{ cat.job_posts_count }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <section class="py-24 bg-slate-50/50 border-t border-slate-100">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex flex-col md:flex-row justify-between items-end gap-6 mb-16">
                    <div>
                        <h2 class="text-4xl font-black text-slate-900 tracking-tight">Handpicked for you</h2>
                        <p class="text-slate-400 font-bold text-sm uppercase tracking-[0.3em] mt-3">The latest opportunities on JobRecon</p>
                    </div>
                    <router-link to="/seeker/jobs" class="group flex items-center gap-3 text-indigo-600 font-black text-sm">
                        EXPLORE ALL <i class="fa-solid fa-arrow-right-long group-hover:translate-x-2 transition-transform"></i>
                    </router-link>
                </div>

                <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-for="i in 3" :key="i" class="h-80 bg-white animate-pulse rounded-[3rem] border border-slate-100"></div>
                </div>

                <div v-else-if="homeData.featuredJobs.length === 0" class="py-20 text-center">
                    <div class="h-20 w-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-briefcase text-slate-300 text-2xl"></i>
                    </div>
                    <h3 class="text-slate-900 font-bold text-xl">No jobs posted yet</h3>
                    <p class="text-slate-500 mt-2">Check back soon for handpicked opportunities.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="job in homeData.featuredJobs" :key="job.slug"
                        @click="goToJobDetail(job.slug)"
                        class="bg-white p-8 rounded-[3rem] border border-slate-100 hover:border-indigo-200 shadow-sm hover:shadow-2xl hover:shadow-indigo-100/40 hover:-translate-y-2 transition-all duration-500 group cursor-pointer relative overflow-hidden">
                        
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/0 to-indigo-50/0 group-hover:to-indigo-50/50 transition-colors duration-500 pointer-events-none"></div>

                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-6">
                                <div class="relative">
                                    <div class="h-14 w-14 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-50 group-hover:bg-white group-hover:border-indigo-100 transition-all duration-500 overflow-hidden">
                                        <img 
                                            v-if="job.employer?.company_logo_url" 
                                            :src="job.employer.company_logo_url" 
                                            :alt="job.employer?.company_name"
                                            class="h-full w-full object-cover"
                                        />
                                        <i v-else class="fa-solid fa-briefcase text-slate-300 group-hover:text-indigo-500 text-xl"></i>
                                    </div>

                                    <div 
                                        v-if="job.employer?.is_verified == 1 || job.employer?.is_verified == true" 
                                        class="absolute -bottom-1 -right-1 bg-white rounded-full p-0.5 shadow-sm z-30 flex items-center justify-center"
                                        title="Verified Employer"
                                    >
                                        <i class="fa-solid fa-circle-check text-emerald-400 text-[14px]"></i>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[9px] font-black uppercase rounded-lg">
                                    {{ job.employment_type }}
                                </span>
                            </div>

                            <h3 class="text-2xl font-black text-slate-900 mb-2 group-hover:text-indigo-600 transition-colors leading-tight">
                                {{ job.title }}
                            </h3>
                            
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-tighter mb-8">
                                {{ job.category?.name || 'General' }} • {{ job.location }}
                            </p>

                            <div class="flex items-center justify-between pt-8 border-t border-slate-50 group-hover:border-indigo-50 transition-colors">
                               <div>
                                    <p class="text-[9px] font-black text-slate-300 uppercase tracking-widest">Est. Salary</p>
                                    
                                    <p v-if="job.salary_visible && job.salary_min !== null" class="text-lg font-black text-slate-900">
                                        {{ job.currency }} {{ formatCurrency(job.salary_min) }} - {{ formatCurrency(job.salary_max) }}
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
        </section>
    </div>
</template>