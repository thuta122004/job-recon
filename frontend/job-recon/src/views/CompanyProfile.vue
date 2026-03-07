<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { useToast } from "vue-toastification";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const loading = ref(true);
const company = ref(null);
const logoError = ref(false);

const formattedSpecialties = computed(() => {
    const raw = company.value?.specialties || company.value?.specialization;
    
    if (!raw) return [];
    
    if (Array.isArray(raw)) return raw;

    if (typeof raw === 'string') {
        return raw.split(',')
                  .map(s => s.trim())
                  .filter(s => s.length > 0);
    }
    
    return [];
});

const fetchCompanyProfile = async () => {
    try {
        loading.value = true;
        const response = await api.get(`/employer/profile/${route.params.id}`);
        company.value = response.data.data;
    } catch (error) {
        toast.error("Could not load company profile.");
    } finally {
        loading.value = false;
    }
};

const handleLogoError = () => {
    logoError.value = true;
};

onMounted(() => {
    fetchCompanyProfile();
});
</script>

<template>
   <nav class="bg-white/80 backdrop-blur-md border-b border-slate-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <button @click="router.push({ name: 'seeker-home' })" class="group flex items-center gap-2 text-slate-400 hover:text-indigo-600 font-bold text-xs uppercase tracking-widest transition-all">
                <i class="fa-solid fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> 
                Back to Home
            </button>
        </div>
    </nav>

    <div class="min-h-screen bg-white text-left selection:bg-indigo-100 selection:text-indigo-700">
        <div v-if="loading" class="max-w-6xl mx-auto px-6 pt-12 animate-pulse">
            <div class="flex items-center gap-8 mb-12">
                <div class="h-32 w-32 bg-slate-100 rounded-[2.5rem]"></div>
                <div class="space-y-3 flex-1">
                    <div class="h-8 bg-slate-100 w-1/3 rounded-lg"></div>
                    <div class="h-4 bg-slate-100 w-1/4 rounded-lg"></div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <div class="lg:col-span-2 h-96 bg-slate-50 rounded-[2.5rem]"></div>
                <div class="h-96 bg-slate-50 rounded-[2.5rem]"></div>
            </div>
        </div>

        <template v-else-if="company">
            <header class="bg-slate-50/50 border-b border-slate-100 pt-16 pb-12">
                <div class="max-w-6xl mx-auto px-6">
                    <div class="flex flex-col md:flex-row items-center md:items-end gap-10">
                        <div class="h-40 w-40 bg-white rounded-[2.5rem] shadow-2xl shadow-indigo-100/40 p-3 border border-slate-100 flex items-center justify-center overflow-hidden shrink-0 group">
                            <img v-if="company.company_logo_url && !logoError" 
                                :src="company.company_logo_url" 
                                @error="handleLogoError"
                                class="h-full w-full object-contain transition-transform duration-500 group-hover:scale-110" />
                            <div v-else class="h-full w-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-4xl font-black">
                                {{ company.company_name?.charAt(0).toUpperCase() }}
                            </div>
                        </div>

                        <div class="flex-1 text-center md:text-left pb-2">
                            <div class="flex flex-col md:flex-row md:items-center gap-4 mb-4">
                                <h1 class="text-4xl font-black text-slate-900 tracking-tighter">
                                    {{ company.company_name }}
                                </h1>
                                <span v-if="company.is_verified" class="inline-flex items-center px-4 py-1.5 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-emerald-100 self-center">
                                    <i class="fa-solid fa-circle-check mr-2"></i> Official Partner
                                </span>
                            </div>
                            
                            <div class="flex flex-wrap justify-center md:justify-start items-center gap-6">
                                <span class="text-indigo-600 font-black uppercase tracking-widest text-[11px] flex items-center gap-2">
                                    <i class="fa-solid fa-briefcase opacity-40 text-sm"></i>
                                    {{ company.industry || 'Industry not specified' }}
                                </span>
                                <span v-if="company.headquarters_location" class="text-slate-400 font-black uppercase tracking-widest text-[11px] flex items-center gap-2">
                                    <i class="fa-solid fa-location-dot opacity-40 text-sm"></i>
                                    {{ company.headquarters_location }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pb-2">
                            <a v-if="company.website_url" :href="company.website_url" target="_blank"
                                class="h-12 px-8 bg-white border border-slate-200 text-slate-900 text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-slate-50 transition-all shadow-sm flex items-center">
                                Website
                            </a>
                            <a v-if="company.linkedin_url" :href="company.linkedin_url" target="_blank"
                                class="h-12 w-12 bg-indigo-600 text-white rounded-2xl flex items-center justify-center hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-100">
                                <i class="fa-brands fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-16">
                    
                    <div class="lg:col-span-2 space-y-16">
                        <section>
                            <h3 class="text-[11px] font-black text-slate-300 uppercase tracking-[0.3em] mb-10 flex items-center gap-4">
                                Organization Profile
                                <div class="h-px bg-slate-100 flex-1"></div>
                            </h3>
                            <p v-if="company.tagline" class="text-2xl font-black text-slate-900 tracking-tight leading-tight mb-8">
                                "{{ company.tagline }}"
                            </p>
                            <div class="prose prose-slate max-w-none text-slate-500 text-sm leading-[2.2] font-medium whitespace-pre-line">
                                {{ company.about_us || 'No detailed description available.' }}
                            </div>
                        </section>

                        <section v-if="formattedSpecialties.length > 0">
                            <h3 class="text-[11px] font-black text-slate-300 uppercase tracking-[0.3em] mb-8 flex items-center gap-4">
                                Core Specialties
                                <div class="h-px bg-slate-100 flex-1"></div>
                            </h3>
                            <div class="flex flex-wrap gap-3">
                                <span v-for="tag in formattedSpecialties" :key="tag" 
                                    class="px-5 py-3 bg-white text-slate-600 text-[10px] font-black uppercase tracking-widest rounded-2xl border border-slate-200 hover:border-indigo-600 hover:text-indigo-600 transition-all cursor-default">
                                    {{ tag }}
                                </span>
                            </div>
                        </section>
                    </div>

                    <div class="space-y-10">
                        <div class="bg-white rounded-[2.5rem] p-10 border border-slate-100 shadow-2xl shadow-slate-200/40 space-y-10">
                            <h3 class="text-[11px] font-black text-slate-900 uppercase tracking-[0.2em] border-b border-slate-50 pb-6">Key Insights</h3>
                            <div class="space-y-8">
                                <div class="flex items-center gap-5">
                                    <div class="h-12 w-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 shrink-0">
                                        <i class="fa-solid fa-users text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Company Size</p>
                                        <p class="text-[11px] font-black text-slate-900 uppercase tracking-tight">{{ company.company_size || 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-5">
                                    <div class="h-12 w-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 shrink-0">
                                        <i class="fa-solid fa-calendar-check text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Founded</p>
                                        <p class="text-[11px] font-black text-slate-900 uppercase tracking-tight">{{ company.founded_year || 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-indigo-600 rounded-[2.5rem] p-10 text-white relative overflow-hidden shadow-2xl shadow-indigo-200 group">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-white/20 transition-all duration-700"></div>
                            <h4 class="text-xl font-black leading-tight uppercase tracking-tighter mb-4 relative z-10">
                                Careers at {{ company.company_name }}
                            </h4>
                            <p class="text-indigo-100 text-[11px] font-bold uppercase tracking-widest leading-loose mb-10 relative z-10 opacity-80">
                                Ready to join our mission? Explore our latest job vacancies.
                            </p>
                            <router-link :to="{ name: 'job-listings', query: { q: company.company_name } }" 
                                class="w-full h-14 bg-white text-indigo-600 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] flex items-center justify-center hover:bg-slate-50 transition-all active:scale-95 relative z-10 shadow-xl shadow-indigo-900/20">
                                View Openings
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.prose {
    line-height: 2.2;
}
</style>