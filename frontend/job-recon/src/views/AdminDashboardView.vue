<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import { useToast } from "vue-toastification";

const toast = useToast();
const loading = ref(true);
const report = ref({
    overview: {
        totalUsers: 0,
        activeJobs: 0,
        totalEmployers: 0,
        talentPool: 0
    },
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
    return str.replace(/([A-Z])/g, ' $1').toUpperCase();
};
</script>

<template>
    <div class="space-y-8 p-1">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <template v-if="loading">
                <div v-for="i in 4" :key="i" class="h-32 bg-white animate-pulse rounded-3xl border border-gray-100"></div>
            </template>
            <template v-else>
                <div v-for="(value, key) in report.overview" :key="key" 
                    class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="h-12 w-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <i v-if="key === 'totalUsers'" class="fa-solid fa-users-gear text-lg"></i>
                            <i v-else-if="key === 'activeJobs'" class="fa-solid fa-briefcase text-lg"></i>
                            <i v-else-if="key === 'totalEmployers'" class="fa-solid fa-user-check text-lg"></i>
                            <i v-else class="fa-solid fa-address-card text-lg"></i>
                        </div>
                        <span class="text-[10px] font-black bg-emerald-100 text-emerald-700 px-2 py-1 rounded-lg uppercase">Live</span>
                    </div>
                    <h3 class="text-gray-400 text-[10px] font-bold tracking-widest">{{ formatLabel(key) }}</h3>
                    <p class="text-3xl font-black text-gray-900 mt-1">{{ value.toLocaleString() }}</p>
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
                        <span class="text-2xl font-black text-indigo-600 leading-none block">{{ report.talentInsights.seekersWithSkills }}</span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Skilled Seekers</span>
                    </div>
                </div>

                <div v-if="loading" class="space-y-6">
                    <div v-for="i in 5" :key="i" class="h-4 bg-gray-50 rounded-full animate-pulse"></div>
                </div>
                <div v-else class="space-y-6">
                    <div v-for="skill in report.talentInsights.topSkills" :key="skill.name">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-bold text-gray-700">{{ skill.name }}</span>
                            <span class="text-xs font-medium text-gray-400">{{ skill.count }} Profiles</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000" 
                                :style="{ width: (report.overview.talentPool > 0 ? (skill.count / report.overview.talentPool * 100) : 0) + '%' }">
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