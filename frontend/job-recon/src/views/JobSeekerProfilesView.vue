<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from "vue-toastification";
import api from '@/services/api';
import JobSeekerProfileModal from '@/components/JobSeekerProfileModal.vue';

const profiles = ref([]);
const users = ref([]);
const roles = ref([]);

const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const selectedProfile = ref(null);
const isEditing = ref(false);

const visibilityFilter = ref('');
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(8);

const toast = useToast();

const zoomedImage = ref(null);
const zoomName = ref('');

const openZoom = (url, name) => {
    if (url) {
        zoomedImage.value = url;
        zoomName.value = name;
    }
};

const closeZoom = () => {
    zoomedImage.value = null;
    zoomName.value = '';
};

const fetchData = async () => {
    loading.value = true;
    try {
        const [profileRes, userRes, roleRes] = await Promise.all([
            api.get('/job-seeker-profiles'),
            api.get('/users'),
            api.get('/roles')
        ]);
        profiles.value = profileRes.data.data || profileRes.data;
        users.value = userRes.data.data || userRes.data;
        roles.value = roleRes.data.data || roleRes.data;
    } catch (e) {
        toast.error("Failed to sync profiles from server");
    } finally {
        loading.value = false;
    }
};

const copyToClipboard = async (text, type) => {
    try {
        await navigator.clipboard.writeText(text);
        toast.info(`${type} copied to clipboard!`, { timeout: 2000 });
    } catch (e) {
        toast.error("Failed to copy text");
    }
};

const filteredProfiles = computed(() => {
    return profiles.value.filter(profile => {
        const fullName = `${profile.user?.first_name} ${profile.user?.last_name}`.toLowerCase();
        const headline = (profile.headline || '').toLowerCase();
        const matchesVisibility = visibilityFilter.value === '' || profile.profile_visibility === visibilityFilter.value;
        const matchesSearch = fullName.includes(searchQuery.value.toLowerCase()) || 
                             headline.includes(searchQuery.value.toLowerCase()) ||
                             profile.user?.email.toLowerCase().includes(searchQuery.value.toLowerCase());
        return matchesVisibility && matchesSearch;
    });
});

const totalPages = computed(() => Math.ceil(filteredProfiles.value.length / itemsPerPage.value) || 1);

const paginatedProfiles = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredProfiles.value.slice(start, start + itemsPerPage.value);
});

const resetPage = () => { currentPage.value = 1; };

const openCreateModal = () => {
    isEditing.value = false;
    selectedProfile.value = null;
    showModal.value = true;
};

const openEditModal = (profile) => {
    isEditing.value = true;
    selectedProfile.value = { ...profile };
    showModal.value = true;
};

const handleSave = async (formDataRaw) => {
    saving.value = true;
    const data = new FormData();
    Object.keys(formDataRaw).forEach(key => {
        if (formDataRaw[key] !== null && formDataRaw[key] !== undefined) {
            data.append(key, formDataRaw[key]);
        }
    });

    try {
        if (isEditing.value) {
            data.append('_method', 'PUT'); 
            await api.post(`/job-seeker-profiles/${formDataRaw.id}`, data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            toast.success("Profile updated successfully!");
        } else {
            await api.post('/job-seeker-profiles', data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            toast.success("Job seeker profile created!");
        }
        await fetchData();
        showModal.value = false;
    } catch (e) {
        if (e.response?.status === 422) {
            toast.error(Object.values(e.response.data.errors)[0][0]); 
        } else {
            toast.error("Server error occurred.");
        }
    } finally {
        saving.value = false;
    }
};

const showConfirmModal = ref(false);
const confirmConfig = ref({ title: '', message: '', action: null, type: 'indigo' });

const requestToggleVisibility = (profile) => {
    const isPublic = profile.profile_visibility === 'PUBLIC';
    confirmConfig.value = {
        title: isPublic ? 'Hide Profile?' : 'Make Profile Public?',
        message: isPublic 
            ? 'This will hide the candidate from the public talent directory. You can change this back at any time.' 
            : 'This will make the candidate’s portfolio visible to all recruiters and employers.',
        type: isPublic ? 'warning' : 'indigo',
        action: () => executeToggleVisibility(profile.id)
    };
    showConfirmModal.value = true;
};

const executeToggleVisibility = async (id) => {
    try {
        await api.delete(`/job-seeker-profiles/${id}`);
        toast.info(`Visibility updated`);
        await fetchData();
    } catch (e) {
        toast.error("Failed to update visibility");
    } finally {
        showConfirmModal.value = false;
    }
};

const isJobSeekerDisabled = computed(() => {
    const jobSeekerRole = roles.value.find(r => r.id == 2);
    
    if (!jobSeekerRole) return true;
    
    return jobSeekerRole.status !== 'ACTIVE';
});

onMounted(fetchData);
</script>

<template>
<div class="space-y-6">
        <div v-if="isJobSeekerDisabled && !loading" 
            class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3 mb-6 animate-in slide-in-from-top-2 duration-300">
            <div class="h-10 w-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-bold text-amber-900">Creation Disabled</h4>
                <p class="text-xs text-amber-700 mt-0.5">
                    The <span class="font-bold">Job Seeker</span> role is currently inactive. You cannot create new profiles until this role is reactivated in 
                    <router-link to="/roles" class="underline hover:text-amber-900 transition-colors">Roles Management</router-link>.
                </p>
            </div>
        </div>
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Talent Directory</h1>
                <p class="text-sm text-gray-500 mt-1">Manage job seeker portfolios, resumes, and visibility.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-fit">
                    <select v-model="visibilityFilter" @change="resetPage"
                        class="w-64 pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-normal text-gray-500 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                        <option value="">All Visibility</option>
                        <option value="PUBLIC">Public</option>
                        <option value="PRIVATE">Private</option>
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </div>
                </div>

                <div class="relative group">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="text" v-model="searchQuery" @input="resetPage" placeholder="Search talent..."
                        class="pl-11 pr-4 py-2.5 w-64 bg-white border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <button @click="openCreateModal"
                        :disabled="isJobSeekerDisabled"
                        :title="isJobSeekerDisabled ? 'Job Seeker role is currently inactive' : 'Add Profile'"
                        :class="[
                            'px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all flex items-center gap-2',
                            isJobSeekerDisabled 
                                ? 'bg-gray-200 text-gray-400 cursor-not-allowed shadow-none' 
                                : 'bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white shadow-indigo-200'
                        ]"
                    >
                        <i class="fa-solid fa-plus text-xs"></i>
                        Add Profile
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">No</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Candidate</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Headline</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Experience</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Visibility</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="loading">
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-500"></i>
                                    <span class="text-sm text-gray-400 font-medium">Fetching talent data...</span>
                                </div>
                            </td>
                        </tr>
                        
                        <tr v-else-if="filteredProfiles.length === 0">
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-400">
                                    <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-20"></i>
                                    <p class="text-sm">No profiles match your current filters.</p>
                                </div>
                            </td>
                        </tr>

                        <tr v-for="(profile, index) in paginatedProfiles" :key="profile.id" class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="px-6 py-4 text-xs font-mono text-gray-400">
                                {{ (currentPage - 1) * itemsPerPage + (index + 1) }}
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div @click="openZoom(profile.profile_picture_url, `${profile.user?.first_name} ${profile.user?.last_name}`)" class="h-9 w-9 rounded-full overflow-hidden bg-gray-100 border border-gray-200 shadow-sm transition-all hover:scale-110 hover:border-indigo-400 cursor-zoom-in">
                                        <img v-if="profile.profile_picture_url" :src="profile.profile_picture_url" class="h-full w-full object-cover">
                                        <div v-else class="h-full w-full flex items-center justify-center text-gray-300">
                                            <i class="fa-solid fa-user text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 text-sm group-hover:text-indigo-600 transition-colors">
                                            {{ profile.user?.first_name }} {{ profile.user?.last_name }}
                                        </span>
                                        <div class="flex items-center gap-2 group/copy">
                                        <span class="text-[11px] text-gray-500">{{ profile.user?.email }}</span>
                                        <button @click="copyToClipboard(profile.user?.email, 'Email')" 
                                                class="opacity-0 group-hover/copy:opacity-100 text-gray-400 hover:text-indigo-600 transition-all">
                                            <i class="fa-regular fa-copy text-[10px]"></i>
                                        </button>
                                    </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-gray-700">{{ profile.headline || 'No Headline' }}</span>
                                    <span class="text-[11px] text-gray-400">{{ profile.current_position || 'Open to Work' }}</span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg w-fit">
                                        {{ profile.experience_years }} {{ profile.experience_years == 1 ? 'Year' : 'Years' }} Exp
                                    </span>
                                    <span class="text-[10px] text-gray-400 flex items-center gap-1">
                                        <i class="fa-solid fa-location-dot text-[9px]"></i>
                                        {{ profile.location || 'Remote' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span :class="profile.profile_visibility === 'PUBLIC' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-gray-50 text-gray-500 border-gray-100'"
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-tight uppercase border">
                                    {{ profile.profile_visibility }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <a v-if="profile.resume_url" :href="profile.resume_url" target="_blank"
                                        class="text-emerald-600 hover:bg-emerald-50 p-2 rounded-md transition-all" title="View Resume">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>

                                    <button @click="openEditModal(profile)" class="text-indigo-600 hover:bg-indigo-50 p-2 rounded-md transition-all" title="Edit Profile">
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>
                                    
                                    <button @click="requestToggleVisibility(profile)" 
                                        :class="profile.profile_visibility === 'PUBLIC' ? 'text-amber-500 hover:bg-amber-50' : 'text-indigo-500 hover:bg-indigo-50'"
                                        class="p-2 rounded-md transition-all"
                                        :title="profile.profile_visibility === 'PUBLIC' ? 'Hide Profile' : 'Show Profile'">
                                        <i v-if="profile.profile_visibility === 'PUBLIC'" class="fa-solid fa-eye-slash"></i>
                                        <i v-else class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">
                    Showing <span class="text-gray-900 font-bold">{{ paginatedProfiles.length }}</span> of <span class="text-gray-900 font-bold">{{ filteredProfiles.length }}</span>
                </span>
                
                <div class="flex items-center gap-1">
                    <button @click="currentPage--" :disabled="currentPage === 1"
                        class="p-2 text-gray-500 hover:bg-white hover:shadow-sm rounded-lg disabled:opacity-30 disabled:hover:bg-transparent transition-all">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>
                    
                    <div class="flex items-center px-4">
                        <span class="text-xs font-bold text-gray-700">Page {{ currentPage }} <span class="text-gray-400 font-normal mx-1">/</span> {{ totalPages }}</span>
                    </div>

                    <button @click="currentPage++" :disabled="currentPage >= totalPages"
                        class="p-2 text-gray-500 hover:bg-white hover:shadow-sm rounded-lg disabled:opacity-30 disabled:hover:bg-transparent transition-all">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="zoomedImage" class="fixed inset-0 z-[100] flex items-center justify-center p-6">
                <div @click="closeZoom" class="absolute inset-0 bg-zinc-900/90 backdrop-blur-sm"></div>
                
                <div class="relative max-w-md w-full">
                    <div class="bg-white rounded-2xl overflow-hidden shadow-2xl">
                        <div class="aspect-square bg-zinc-100 relative">
                            <img :src="zoomedImage" 
                                class="w-full h-full object-cover" 
                                @click.stop />
                            
                            <button @click="closeZoom" 
                                class="absolute top-4 right-4 bg-black/20 hover:bg-black/40 backdrop-blur-md text-white w-8 h-8 rounded-full flex items-center justify-center transition-all">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </button>
                        </div>
                        
                        <div class="p-5 bg-white">
                            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Candidate Profile</p>
                            <h3 class="text-zinc-900 text-lg font-bold">{{ zoomName }}</h3>
                        </div>
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
            <div v-if="showConfirmModal" class="fixed inset-0 z-[110] flex items-center justify-center p-6">
                <div @click="showConfirmModal = false" class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm"></div>
                <div class="relative max-w-sm w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div :class="confirmConfig.type === 'warning' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600'" 
                             class="h-12 w-12 rounded-xl flex items-center justify-center mb-4 text-xl">
                            <i :class="confirmConfig.type === 'warning' ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">{{ confirmConfig.title }}</h3>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ confirmConfig.message }}</p>
                        <div class="mt-8 flex gap-3">
                            <button @click="showConfirmModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-gray-500 bg-gray-50 hover:bg-gray-100 transition-colors">Cancel</button>
                            <button @click="confirmConfig.action" 
                                :class="confirmConfig.type === 'warning' ? 'bg-amber-500 hover:bg-amber-600' : 'bg-indigo-600 hover:bg-indigo-700'"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg transition-all active:scale-95">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

    <JobSeekerProfileModal 
        :is-open="showModal" 
        :profile-data="selectedProfile" 
        :users="users" 
        :profiles="profiles"
        :is-editing="isEditing"
        :loading="saving"
        @close="showModal = false"
        @save="handleSave"
    />
</template>