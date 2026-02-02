<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from "vue-toastification";
import api from '@/services/api';
import EmployerJobPostModal from '@/components/EmployerJobPostModal.vue';

const router = useRouter();
const toast = useToast();

const props = defineProps({
    profileId: { type: [String, Number], required: true }
});

const jobs = ref([]);
const company = ref(null);
const loading = ref(true);
const saving = ref(false);

const showModal = ref(false);
const selectedJob = ref(null);
const isEditing = ref(false);
const statusUpdating = ref(null);

const showConfirmModal = ref(false);
const showArchiveModal = ref(false);
const confirmConfig = ref({ 
    title: '', 
    message: '', 
    action: null, 
    type: 'indigo' 
});

const searchQuery = ref('');
const statusFilter = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(8);

const fetchData = async () => {
    loading.value = true;
    try {
        const res = await api.get(`/employer-profiles/${props.profileId}/job-posts`);
        const responseData = res.data.data || res.data;
        company.value = responseData.profile;
        jobs.value = responseData.jobs || [];
    } catch (e) {
        toast.error("Failed to sync job postings");
    } finally {
        loading.value = false;
    }
};

const searchArchive = ref('');

const filteredArchivedJobs = computed(() => {
    const query = searchArchive.value.toLowerCase().trim();
    return jobs.value.filter(job => {
        return job.status === 'ARCHIVED' && (job.title || '').toLowerCase().includes(query);
    });
});

const filteredJobs = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    return jobs.value.filter(job => {
        if (job.status === 'ARCHIVED') return false;
        const title = (job.title || '').toLowerCase();
        const matchesStatus = statusFilter.value === '' || job.status === statusFilter.value;
        const matchesSearch = title.includes(query);
        return matchesStatus && matchesSearch;
    });
});

const totalPages = computed(() => Math.ceil(filteredJobs.value.length / itemsPerPage.value) || 1);
const paginatedJobs = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredJobs.value.slice(start, start + itemsPerPage.value);
});

const openCreateModal = () => {
    isEditing.value = false;
    selectedJob.value = null;
    showModal.value = true;
};

const openEditModal = (job) => {
    isEditing.value = true;
    selectedJob.value = { ...job };
    showModal.value = true;
};

const handleSave = async (formData) => {
    saving.value = true;
    try {
        const payload = { 
            ...formData, 
            employer_profile_id: props.profileId,
            skills: formData.skills.map(s => (typeof s === 'object' ? s.id : s))
        };
        
        if (isEditing.value) {
            await api.put(`/job-posts/${formData.id}`, payload);
            toast.success("Job updated successfully");
        } else {
            await api.post('/job-posts', payload);
            toast.success("New vacancy posted");
        }
        
        await fetchData();
        showModal.value = false;
    } catch (e) {
        const errorMsg = e.response?.data?.errors 
            ? Object.values(e.response.data.errors)[0][0] 
            : "Operation failed";
        toast.error(errorMsg);
    } finally {
        saving.value = false;
    }
};

const requestToggleStatus = (job) => {
    const willOpen = job.status !== 'OPEN';
    confirmConfig.value = {
        title: willOpen ? 'Activate Vacancy?' : 'Close Vacancy?',
        message: willOpen 
            ? `Are you sure you want to set "${job.title}" to OPEN? It will be visible to candidates.` 
            : `Are you sure you want to CLOSE "${job.title}"? Candidates won't be able to apply.`,
        type: willOpen ? 'indigo' : 'warning',
        action: () => executeToggle(job)
    };
    showConfirmModal.value = true;
};

const executeToggle = async (job) => {
    showConfirmModal.value = false;
    statusUpdating.value = job.id;
    try {
        const res = await api.patch(`/job-posts/${job.id}/toggle-visibility`);
        const updatedStatus = res.data.data?.status || res.data.status;
        job.status = updatedStatus;
        toast.info(`Vacancy is now ${updatedStatus}`);
    } catch (e) {
        toast.error("Failed to update status");
    } finally {
        statusUpdating.value = null;
    }
};

const requestArchive = (job) => {
    confirmConfig.value = {
        title: 'Archive Vacancy?',
        message: `Are you sure you want to archive "${job.title}"? This action cannot be undone.`,
        type: 'warning',
        action: () => executeArchive(job.id)
    };
    showConfirmModal.value = true;
};

const executeArchive = async (id) => {
    showConfirmModal.value = false;
    try {
        await api.delete(`/job-posts/${id}`);
        toast.success("Vacancy moved to archives");
        await fetchData();
    } catch (e) {
        const errorMsg = e.response?.data?.message || "Failed to archive vacancy";
        toast.error(errorMsg);
    }
};

const formatCurrency = (amount, currency) => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || 'USD',
        maximumFractionDigits: 0
    }).format(amount);
};

const formatDate = (dateString) => {
    if (!dateString) return 'No Expiry';
    return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const salaryUpdating = ref(null);

const requestToggleSalary = (job) => {
    const willHide = job.salary_visible;
    confirmConfig.value = {
        title: willHide ? 'Hide Salary Scale?' : 'Show Salary Scale?',
        message: willHide 
            ? `Are you sure you want to hide the salary for "${job.title}"? Candidates will see "Undisclosed".` 
            : `Are you sure you want to show the salary for "${job.title}"? This will be visible to all applicants.`,
        type: willHide ? 'warning' : 'indigo',
        action: () => executeToggleSalary(job)
    };
    showConfirmModal.value = true;
};

const executeToggleSalary = async (job) => {
    showConfirmModal.value = false;
    salaryUpdating.value = job.id;
    try {
        const res = await api.patch(`/job-posts/${job.id}/toggle-salary`);
        job.salary_visible = res.data.visible;
        toast.info(res.data.message);
    } catch (e) {
        toast.error("Failed to update salary privacy");
    } finally {
        salaryUpdating.value = null;
    }
};

const archivedJobs = computed(() => {
    return jobs.value.filter(job => job.status === 'ARCHIVED');
});

const requestRestore = (job) => {
    confirmConfig.value = {
        title: 'Restore Vacancy?',
        message: `Do you want to restore "${job.title}" to the active listings? It will be set to OPEN.`,
        type: 'indigo',
        action: () => executeRestore(job.id)
    };
    showConfirmModal.value = true;
};

const executeRestore = async (id) => {
    showConfirmModal.value = false;
    try {
        await api.post(`/job-posts/${id}/restore`);
        toast.success("Vacancy restored successfully");
        
        await fetchData();
        
        if (archivedJobs.value.length === 0) {
            showArchiveModal.value = false;
        }
    } catch (e) {
        toast.error("Failed to restore vacancy");
    }
};

onMounted(fetchData);
watch(() => props.profileId, fetchData);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div class="flex items-center gap-4">
                <button @click="router.push('/admin/employer-profiles')" 
                    class="h-11 w-11 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-400 hover:text-indigo-600 transition-all shadow-sm">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                            {{ company?.company_name || 'Loading...' }}
                        </h1>
                        <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-600 text-[10px] font-bold uppercase tracking-wider">Jobs</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5">Managing recruitment pipeline and active listings.</p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-fit">
                    <select v-model="statusFilter" @change="currentPage = 1"
                        class="w-64 pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-normal text-gray-500 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="OPEN">Open</option>
                        <option value="CLOSED">Closed</option>
                        <option value="DRAFT">Draft</option>
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </div>
                </div>
                <div class="relative group">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="text" v-model="searchQuery" @input="currentPage = 1" placeholder="Search vacancies..."
                        class="pl-11 pr-4 py-2.5 w-64 bg-white border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
                <button @click="openCreateModal"
                    class="bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all flex items-center gap-2">
                    <i class="fa-solid fa-plus text-xs"></i> Post Vacancy
                </button>
<button @click="showArchiveModal = true"
    class="h-9 w-9 flex items-center justify-center text-rose-500 hover:bg-rose-50 rounded-xl transition-all relative group"
    title="View Archived Vacancies">
    
    <i class="fa-solid fa-trash-can text-sm"></i>

    <span v-if="archivedJobs.length > 0" 
        class="absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-600 text-[8px] text-white font-black border-2 border-white shadow-sm transition-transform group-hover:scale-110">
        {{ archivedJobs.length }}
    </span>
</button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center w-16">No.</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Vacancy Details</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Work & Experience</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Compensation</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Applicants</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Status & Expiry</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Manage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="loading">
                            <td colspan="7" class="px-6 py-20 text-center">
                                <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-500"></i>
                            </td>
                        </tr>
                        <tr v-else-if="filteredJobs.length === 0">
                            <td colspan="7" class="px-6 py-20 text-center text-gray-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-20"></i>
                                <p class="text-sm">No vacancies match your criteria.</p>
                            </td>
                        </tr>
                        <tr v-for="(job, index) in paginatedJobs" :key="job.id" class="hover:bg-indigo-50/30 transition-all duration-300 group">
                            <td class="px-6 py-5 text-center">
                                <span class="text-xs font-bold text-gray-400">
                                    {{ (currentPage - 1) * itemsPerPage + index + 1 }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-3 mb-1">
                                        <span class="font-bold text-gray-900 text-[15px] group-hover:text-indigo-600 transition-colors line-clamp-1">
                                            {{ job.title }}
                                        </span>
                                        <span class="flex items-center gap-1 text-[11px] text-gray-400 font-medium whitespace-nowrap">
                                            <i class="fa-solid fa-location-dot opacity-60"></i>
                                            {{ job.location }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i v-if="job.category?.icon_class" 
                                        :class="job.category.icon_class" 
                                        class="text-[10px] text-indigo-400"></i>
                                        
                                        <span class="text-[10px] font-bold text-indigo-500 bg-indigo-50/50 px-2 py-0.5 rounded uppercase tracking-tighter">
                                            {{ job.category?.name || 'General' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                        <span class="text-xs font-bold text-gray-700 uppercase tracking-tight">
                                            {{ job.employment_type?.replace('-', ' ') }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider ml-3">
                                        {{ job.experience_level?.replace('-', ' ') }} • {{ job.workplace_type }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex items-center justify-between min-w-[150px]">
                                    <div :class="job.salary_visible ? 'opacity-100' : 'opacity-40'" class="flex flex-col transition-all duration-300">
                                        <span class="text-xs font-black text-gray-800">
                                            {{ formatCurrency(job.salary_min, job.currency) }} - {{ formatCurrency(job.salary_max, job.currency) }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">
                                            {{ job.currency }} / Month
                                        </span>
                                    </div>

                                    <button @click="requestToggleSalary(job)" 
                                        :disabled="salaryUpdating === job.id"
                                        :class="job.salary_visible ? 'text-amber-500 bg-amber-50' : 'text-indigo-500 bg-indigo-50'"
                                        class="h-8 w-8 flex items-center justify-center rounded-xl transition-all active:scale-90 disabled:opacity-50"
                                        :title="job.salary_visible ? 'Hide Salary' : 'Show Salary'">
                                        <i v-if="salaryUpdating === job.id" class="fa-solid fa-circle-notch fa-spin text-[10px]"></i>
                                        <i v-else :class="job.salary_visible ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm"></i>
                                    </button>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="inline-flex flex-col items-center">
                                    <span class="text-lg font-black text-gray-900 leading-none">{{ job.application_count || 0 }}</span>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Candidates</span>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex flex-col gap-2">
                                    <button @click="requestToggleStatus(job)" :disabled="statusUpdating === job.id"
                                        :class="[
                                            job.status === 'OPEN' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 
                                            job.status === 'CLOSED' ? 'bg-rose-50 text-rose-600 border-rose-100' : 
                                            'bg-amber-50 text-amber-600 border-amber-100',
                                            statusUpdating === job.id ? 'opacity-50 cursor-wait' : 'hover:brightness-95'
                                        ]"
                                        class="w-fit px-3 py-1 rounded-lg text-[9px] font-black uppercase border transition-all shadow-sm">
                                        {{ job.status }}
                                    </button>
                                    <div class="flex items-center gap-1.5 text-[10px] font-bold text-gray-400">
                                        <i class="fa-regular fa-clock opacity-60"></i>
                                        <span>Exp: {{ formatDate(job.expires_at) }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-end items-center gap-2">
                                    <button @click="openEditModal(job)" 
                                        class="h-9 w-9 flex items-center justify-center text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all" 
                                        title="Edit Vacancy">
                                        <i class="fa-solid fa-pencil text-sm"></i>
                                    </button>
                                    
                                    <button @click="requestToggleStatus(job)" 
                                        :disabled="statusUpdating === job.id"
                                        :class="job.status === 'OPEN' ? 'text-amber-500 hover:bg-amber-50' : 'text-indigo-500 hover:bg-indigo-50'"
                                        class="h-9 w-9 flex items-center justify-center rounded-xl transition-all disabled:opacity-50"
                                        :title="job.status === 'OPEN' ? 'Close Vacancy' : 'Open Vacancy'">
                                        <i v-if="statusUpdating === job.id" class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                                        <i v-else :class="job.status === 'OPEN' ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm"></i>
                                    </button>

                                    <button @click="requestArchive(job)"
                                        class="h-9 w-9 flex items-center justify-center text-rose-500 hover:bg-rose-50 rounded-xl transition-all"
                                        title="Archive Vacancy">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Showing <span class="text-gray-900 font-bold">{{ paginatedJobs.length }}</span> of <span class="text-gray-900 font-bold">{{ filteredJobs.length }}</span></span>
                <div class="flex items-center gap-1">
                    <button @click="currentPage--" :disabled="currentPage === 1" class="p-2 text-gray-500 hover:bg-white hover:shadow-sm rounded-lg disabled:opacity-30 transition-all"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                    <div class="flex items-center px-4"><span class="text-xs font-bold text-gray-700">Page {{ currentPage }} / {{ totalPages }}</span></div>
                    <button @click="currentPage++" :disabled="currentPage >= totalPages" class="p-2 text-gray-500 hover:bg-white hover:shadow-sm rounded-lg disabled:opacity-30 transition-all"><i class="fa-solid fa-chevron-right text-xs"></i></button>
                </div>
            </div>
        </div>
    </div>

    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showArchiveModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-zinc-900/60 backdrop-blur-sm">
            <div class="bg-white w-full max-w-2xl max-h-[85vh] overflow-hidden rounded-3xl shadow-2xl flex flex-col">
                <div class="px-8 py-6 border-b border-gray-100 bg-gray-50/50">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-black text-gray-900">Archived Vacancies</h2>
                            <p class="text-xs text-gray-500">Restore posts to active listings or manage history.</p>
                        </div>
                        <button @click="showArchiveModal = false" class="h-10 w-10 flex items-center justify-center rounded-xl hover:bg-white text-gray-400 transition-colors">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    
                    <div class="relative group">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input 
                            type="text" 
                            v-model="searchArchive" 
                            placeholder="Search archives..."
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                        >
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-3 bg-gray-50/30">
                    <div v-if="filteredArchivedJobs.length === 0" class="py-20 text-center text-gray-400">
                        <i class="fa-solid fa-box-open text-4xl mb-3 opacity-20"></i>
                        <p class="font-bold text-sm">No archived vacancies found.</p>
                    </div>
                    
                    <div v-for="job in filteredArchivedJobs" :key="job.id" 
                        class="group flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl hover:border-indigo-200 hover:shadow-md transition-all">
                        
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-xl bg-gray-50 flex flex-col items-center justify-center border border-gray-100 group-hover:bg-indigo-50 group-hover:border-indigo-100 transition-colors">
                                <span class="text-sm font-black text-gray-700 group-hover:text-indigo-600">{{ job.application_count || 0 }}</span>
                                <span class="text-[7px] font-bold text-gray-400 uppercase tracking-tighter">Apps</span>
                            </div>

                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 leading-tight group-hover:text-indigo-600 transition-colors">
                                    {{ job.title }}
                                </span>
                                <div v-if="job.category" class="flex items-center gap-1.5 mt-1.5">
                                    <i :class="job.category.icon_class || 'fa-solid fa-tag'" 
                                    class="text-[10px] text-indigo-400"></i>
                                    
                                    <span class="text-[10px] font-bold text-indigo-500 uppercase">
                                        {{ job.category.name }}
                                    </span>
                                </div>
                                <span class="text-[9px] text-gray-400 mt-1 italic">
                                    Archived on {{ formatDate(job.updated_at) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="requestRestore(job)" 
                                class="flex items-center gap-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white px-4 py-2.5 rounded-xl text-xs font-black transition-all border border-emerald-100">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-5 border-t border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        Total: {{ filteredArchivedJobs.length }} {{ filteredArchivedJobs.length <= 1 ? 'item' : 'items' }}
                    </span>
                    <button @click="showArchiveModal = false" class="px-6 py-2.5 rounded-xl text-sm font-bold text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 transition-colors">
                        Discard
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div v-if="showConfirmModal" class="fixed inset-0 z-[150] flex items-center justify-center p-6">
            <div @click="showConfirmModal = false" class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm"></div>
            <div class="relative max-w-sm w-full bg-white rounded-2xl shadow-2xl p-6 border border-gray-100">
                <div :class="confirmConfig.type === 'warning' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600'" 
                     class="h-12 w-12 rounded-xl flex items-center justify-center mb-4 text-xl">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900">{{ confirmConfig.title }}</h3>
                <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ confirmConfig.message }}</p>
                <div class="mt-8 flex gap-3">
                    <button @click="showConfirmModal = false" 
                            class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-gray-500 bg-gray-50 hover:bg-gray-100 transition-colors">
                        Cancel
                    </button>
                    <button @click="confirmConfig.action" 
                            :class="confirmConfig.type === 'warning' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-indigo-600 hover:bg-indigo-700'" 
                            class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg active:scale-95 transition-all">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <EmployerJobPostModal 
        :is-open="showModal" 
        :job-data="selectedJob" 
        :employer-id="props.profileId"
        :is-editing="isEditing" 
        :loading="saving"
        @close="showModal = false" 
        @save="handleSave"
    />
</template>