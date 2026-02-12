<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();
const loading = ref(true);

const homeData = ref({
    stats: { 
        totalPosts: 0, 
        activeJobs: 0, 
        closedJobs: 0, 
        platformReach: 0 
    },
    myRecentPosts: [],
    industryInsights: {
        totalCompetitors: 0,
        industryJobs: 0
    }
});

const fetchHomeData = async () => {
    loading.value = true;
    try {
        const userId = localStorage.getItem('user_id');
        if (!userId) {
            router.push('/login');
            return;
        }
        const response = await api.get(`/employer/home-data/${userId}`);
        homeData.value = response.data;
    } catch (error) {
        console.error("Employer Dashboard Error:", error);
    } finally {
        loading.value = false;
    }
};

const goToJobManagement = () => {
    router.push('/employer/jobs');
};

const createNewJob = () => {
    router.push('/employer/jobs/create');
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const tips = [
    {
        highlight: "2.5x more",
        text: "Job posts with detailed salary ranges receive applications than those without.",
        sub: "Transparency wins."
    },
    {
        highlight: "40% higher",
        text: "Verified company profiles see engagement from top-tier talent.",
        sub: "Trust is key."
    },
    {
        highlight: "Tuesday",
        text: "is the best day to post. Most job seekers start their search early in the week.",
        sub: "Timing matters."
    },
    {
        highlight: "Remote",
        text: "options attract 3x more diverse candidates across different regions.",
        sub: "Expand your reach."
    },
    {
        highlight: "Response time",
        text: "within 48 hours increases your chance of landing the candidate by 60%.",
        sub: "Stay fast."
    }
];

const currentTip = ref(tips[Math.floor(Math.random() * tips.length)]);

onMounted(fetchHomeData);
</script>

<template>
    <div class="min-h-screen bg-white selection:bg-indigo-100 selection:text-indigo-700">
        <div class="relative pt-16 pb-12 overflow-hidden">
            <div class="absolute inset-0 z-0 pointer-events-none">
                <div class="absolute top-[-10%] left-[-10%] h-[500px] w-[500px] bg-slate-50 rounded-full blur-[100px]"></div>
                <div class="absolute bottom-0 right-[-5%] h-[300px] w-[300px] bg-indigo-50/50 rounded-full blur-[80px]"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-6">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-10">
                    <div class="max-w-xl">
                        <h1 class="text-5xl md:text-6xl font-black text-slate-900 tracking-tighter leading-[0.9] mb-6">
                            Connect with <br/> 
                            <span class="text-indigo-600">Top Talent.</span>
                        </h1>
                        <p class="text-slate-500 font-medium text-lg mb-8">
                            Your listings are currently visible to <span class="text-indigo-600 font-bold">{{ homeData.stats.platformReach }}</span> registered job seekers.
                        </p>
                        <button @click="createNewJob" class="px-8 py-4 bg-indigo-600 text-white font-black rounded-2xl hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-100 flex items-center gap-3 active:scale-95">
                            <i class="fa-solid fa-plus text-xs"></i>
                            CREATE JOB POST
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4 w-full lg:w-auto">
                        <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm min-w-[180px]">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Listings</p>
                            <span class="text-4xl font-black text-slate-900">{{ homeData.stats.totalPosts }}</span>
                            <div class="mt-2 text-[10px] font-bold text-slate-400 italic">Lifetime posts</div>
                        </div>
                        <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm min-w-[180px]">
                            <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-2">Active Jobs</p>
                            <span class="text-4xl font-black text-indigo-600">{{ homeData.stats.activeJobs }}</span>
                            <div class="mt-2 text-[10px] font-bold text-indigo-400">Currently live</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="py-12 bg-slate-50/30 border-t border-slate-100">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    
                    <div class="lg:col-span-8">
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Recent Activity</h2>
                                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-1">Manage your latest openings</p>
                            </div>
                            <button @click="goToJobManagement" class="px-5 py-2 rounded-xl bg-white border border-slate-200 text-[10px] font-black text-slate-600 uppercase tracking-widest hover:border-indigo-600 hover:text-indigo-600 transition-all">
                                View Full List
                            </button>
                        </div>

                        <div v-if="loading" class="space-y-4">
                            <div v-for="i in 3" :key="i" class="h-24 bg-white animate-pulse rounded-3xl border border-slate-100"></div>
                        </div>

                        <div v-else-if="homeData.myRecentPosts.length === 0" class="bg-white p-16 rounded-[3rem] border border-dashed border-slate-200 text-center">
                            <div class="h-16 w-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fa-solid fa-briefcase text-slate-200 text-2xl"></i>
                            </div>
                            <h3 class="font-bold text-slate-900">No job posts found</h3>
                            <p class="text-slate-500 text-sm mt-1">Get started by creating your first listing.</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="job in homeData.myRecentPosts" :key="job.id" 
                                class="bg-white p-6 rounded-[2rem] border border-slate-100 hover:border-indigo-100 transition-all group flex items-center justify-between">
                                
                                <div class="flex items-center gap-5">
                                    <div class="h-14 w-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                                        <i :class="job.category?.icon_class || 'fa-solid fa-briefcase'" class="text-xl"></i>
                                    </div>
                                    
                                    <div>
                                        <h4 class="font-black text-slate-900 group-hover:text-indigo-600 transition-colors">
                                            {{ job.title }}
                                        </h4>
                                        <div class="flex items-center gap-3 mt-1">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                                {{ job.category?.name || 'General' }}
                                            </span>
                                            <span class="h-1 w-1 bg-slate-200 rounded-full"></span>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                                Posted {{ formatDate(job.created_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-6">
                                    <span :class="job.status === 'OPEN' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400'" 
                                        class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest">
                                        {{ job.status }}
                                    </span>
                                    <button @click="router.push(`/employer/jobs/edit/${job.id}`)" 
                                            class="h-10 w-10 rounded-xl bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all">
                                        <i class="fa-solid fa-chevron-right text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-4 space-y-6">
                        <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm relative overflow-hidden">
                            <div class="relative z-10">
                                <h3 class="text-lg font-black text-slate-900 leading-tight mb-6">Market Insights</h3>
                                
                                <div class="space-y-6">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <i class="fa-solid fa-building-shield text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Competitors</p>
                                            <p class="text-xl font-black text-slate-900">{{ homeData.industryInsights.totalCompetitors }} <span class="text-xs font-bold text-slate-400 ml-1">In Industry</span></p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <i class="fa-solid fa-magnifying-glass-chart text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Global Demand</p>
                                            <p class="text-xl font-black text-slate-900">{{ homeData.industryInsights.industryJobs }} <span class="text-xs font-bold text-slate-400 ml-1">Industry Jobs</span></p>
                                        </div>
                                    </div>
                                </div>

                                <button class="w-full mt-8 py-4 border border-indigo-100 text-indigo-600 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-indigo-600 hover:text-white transition-all">
                                    ANALYZE TRENDS
                                </button>
                            </div>
                        </div>

                        <div class="bg-indigo-600 rounded-[2.5rem] p-8 text-white relative overflow-hidden group">
                            <i class="fa-solid fa-lightbulb absolute -bottom-4 -right-4 text-7xl text-white/10 -rotate-12 group-hover:rotate-0 transition-transform duration-500"></i>
                            
                            <div class="relative z-10">
                                <h4 class="text-[10px] font-black text-indigo-200 uppercase tracking-[0.2em] mb-4">
                                    Pro Tip • {{ currentTip.sub }}
                                </h4>
                                
                                <p class="font-bold text-sm leading-relaxed">
                                    <template v-if="currentTip.text.startsWith(currentTip.highlight)">
                                        <span class="text-indigo-200">{{ currentTip.highlight }}</span> {{ currentTip.text.replace(currentTip.highlight, '') }}
                                    </template>
                                    
                                    <template v-else>
                                        {{ currentTip.text.split(currentTip.highlight)[0] }}
                                        <span class="text-indigo-200">{{ currentTip.highlight }}</span>
                                        {{ currentTip.text.split(currentTip.highlight)[1] }}
                                    </template>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>