<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import { useToast } from "vue-toastification";

const toast = useToast();
const loading = ref(true);
const report = ref({
    overview: {},
    talentInsights: {
        seekersWithSkills: 0,
        topSkills: []
    },
    marketActivity: {
        categoryDistribution: []
    }
});

const fetchReport = async () => {
    loading.value = true;
    try {
        const response = await api.get('/admin/dashboard-stats');
        report.value = response.data;
    } catch (e) {
        toast.error("Analytics engine failed to sync data");
        console.error(e);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchReport);

const formatLabel = (str) => {
    return str.replace(/([A-Z])/g, ' $1').trim().toUpperCase();
};
</script>

<template>
    <div class="space-y-8 p-1">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <template v-if="loading">
                <div v-for="i in 4" :key="i" class="h-44 bg-white animate-pulse rounded-[2.5rem] border border-gray-100 shadow-sm"></div>
            </template>
            
            <template v-else>
                <div v-for="(data, key) in report.overview" :key="key" 
                    class="relative overflow-hidden bg-white p-6 rounded-[2.5rem] border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                    
                    <div class="absolute -right-4 -top-4 h-24 w-24 bg-indigo-50/50 rounded-full blur-3xl group-hover:bg-indigo-100/60 transition-colors"></div>

                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-6">
                            <div :class="[
                                'h-12 w-12 rounded-2xl flex items-center justify-center transition-all duration-500 shadow-sm',
                                'bg-gray-50 text-gray-400 group-hover:scale-110',
                                key === 'totalUsers' ? 'group-hover:bg-blue-600 group-hover:text-white' : 
                                key === 'activeJobs' ? 'group-hover:bg-violet-600 group-hover:text-white' :
                                key === 'totalEmployers' ? 'group-hover:bg-emerald-600 group-hover:text-white' : 
                                'group-hover:bg-amber-600 group-hover:text-white'
                            ]">
                                <i v-if="key === 'totalUsers'" class="fa-solid fa-users-gear text-lg"></i>
                                <i v-else-if="key === 'activeJobs'" class="fa-solid fa-briefcase text-lg"></i>
                                <i v-else-if="key === 'totalEmployers'" class="fa-solid fa-user-check text-lg"></i>
                                <i v-else class="fa-solid fa-address-card text-lg"></i>
                            </div>

                            <div class="flex flex-col items-end">
                                <div :class="['flex items-center gap-1 text-xs font-black', data.growth >= 0 ? 'text-emerald-500' : 'text-rose-500']">
                                    <i :class="['fa-solid', data.growth >= 0 ? 'fa-caret-up' : 'fa-caret-down']"></i>
                                    {{ Math.abs(data.growth) }}%
                                </div>
                                <span class="text-[8px] text-gray-400 font-bold uppercase tracking-widest">vs last month</span>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] leading-none">
                                {{ formatLabel(key) }}
                            </p>
                            <h2 class="text-4xl font-black text-gray-900 tracking-tighter">
                                {{ data.value.toLocaleString() }}
                            </h2>
                        </div>

                        <div class="mt-6 flex items-end gap-1 h-8">
                            <div v-for="(val, i) in data.sparkline" :key="i"
                                class="flex-1 rounded-t-sm transition-all duration-700 bg-gray-100 group-hover:bg-indigo-400"
                                :style="{ 
                                    height: (Math.max(...data.sparkline) > 0 ? (val / Math.max(...data.sparkline) * 100) : 10) + '%' 
                                }">
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-900 tracking-tight">Talent Readiness</h3>
                        <p class="text-xs text-gray-400">Common skills across the talent pool</p>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-black text-indigo-600 leading-none block">
                            {{ report.talentInsights.seekersWithSkills }}
                        </span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Skilled {{ report.talentInsights.seekersWithSkills <= 1 ? 'Seeker' : 'Seekers' }}</span>
                    </div>
                </div>

                <div v-if="loading" class="space-y-6">
                    <div v-for="i in 5" :key="i" class="h-4 bg-gray-50 rounded-full animate-pulse"></div>
                </div>
                <div v-else class="space-y-6">
                    <div v-for="skill in report.talentInsights.topSkills" :key="skill.name">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-bold text-gray-700">{{ skill.name }}</span>
                            <span class="text-xs font-medium text-gray-400">{{ skill.count }} {{ skill.count <= 1 ? 'Profile' : 'Profiles' }}</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000" 
                                :style="{ width: (report.overview.talentPool?.value > 0 ? (skill.count / report.overview.talentPool.value * 100) : 0) + '%' }">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
                <h3 class="text-lg font-extrabold text-gray-900 tracking-tight mb-2">Market Demand</h3>
                <p class="text-xs text-gray-400 mb-8">Job postings per category</p>

                <div v-if="loading" class="space-y-4">
                    <div v-for="i in 5" :key="i" class="h-10 bg-gray-50 rounded-xl animate-pulse"></div>
                </div>
                <div v-else class="overflow-hidden border border-gray-50 rounded-2xl">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Category</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Postings</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="cat in report.marketActivity.categoryDistribution" :key="cat.name" class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-gray-600 group-hover:text-indigo-600 transition-colors">{{ cat.name }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="inline-block px-3 py-1 rounded-lg bg-gray-100 text-gray-900 text-xs font-black group-hover:bg-indigo-100 group-hover:text-indigo-700 transition-all">
                                        {{ cat.jobs_count }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>