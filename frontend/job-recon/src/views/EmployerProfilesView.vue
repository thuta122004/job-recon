<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from "vue-toastification";
import { useRouter } from 'vue-router';
import api from '@/services/api';
import EmployerProfileModal from '@/components/EmployerProfileModal.vue';

const router = useRouter();
const profiles = ref([]);
const users = ref([]);
const roles = ref([]);

const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const selectedProfile = ref(null);
const isEditing = ref(false);

const industryFilter = ref('');
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(8);

const toast = useToast();

const zoomedImage = ref(null);
const zoomName = ref('');

const verificationFilter = ref('all');

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
            api.get('/employer-profiles'),
            api.get('/users'),
            api.get('/roles')
        ]);
        profiles.value = profileRes.data.data || profileRes.data;
        users.value = userRes.data.data || userRes.data;
        roles.value = roleRes.data.data || roleRes.data;
    } catch (e) {
        toast.error("Failed to sync employer profiles");
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
    const query = searchQuery.value.toLowerCase().trim();
    
    return profiles.value.filter(profile => {
        const companyName = (profile.company_name || '').toLowerCase();
        const industry = (profile.industry || '').toLowerCase();
        const userEmail = (profile.user?.email || '').toLowerCase();

        const matchesVerification = 
            verificationFilter.value === 'all' || 
            (verificationFilter.value === 'verified' && profile.is_verified) ||
            (verificationFilter.value === 'unverified' && !profile.is_verified);

        const matchesIndustry = industryFilter.value === '' || profile.industry === industryFilter.value;
        
        const matchesSearch = companyName.includes(query) || 
                             industry.includes(query) ||
                             userEmail.includes(query);

        return matchesVerification && matchesIndustry && matchesSearch;
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
        const value = formDataRaw[key];
        
        if (value === null || value === undefined) return;

        if (key === 'specialties' && Array.isArray(value)) {
            data.append('specialties', JSON.stringify(value));
        } 
        else if (typeof value === 'boolean') {
            data.append(key, value ? '1' : '0');
        }
        else if (value instanceof File) {
            data.append(key, value);
        }
        else {
            data.append(key, value);
        }
    });

    try {
        if (isEditing.value) {
            data.append('_method', 'PUT'); 
            await api.post(`/employer-profiles/${formDataRaw.id}`, data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            toast.success("Company profile updated!");
        } else {
            await api.post('/employer-profiles', data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            toast.success("Employer profile created!");
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

const showConfirmModal = ref(false);
const confirmConfig = ref({ title: '', message: '', action: null, type: 'indigo' });

const requestToggleVerification = (profile) => {
    const isVerified = profile.is_verified;
    confirmConfig.value = {
        title: isVerified ? 'Revoke Verification?' : 'Verify Company?',
        message: isVerified 
            ? 'This will remove the verified badge from this employer profile.' 
            : 'This will add a verification badge, increasing trust for job seekers.',
        type: isVerified ? 'warning' : 'indigo',
        action: () => executeToggleVerification(profile.id)
    };
    showConfirmModal.value = true;
};

const executeToggleVerification = async (id) => {
    try {
        await api.delete(`/employer-profiles/${id}`);
        toast.info(`Verification status updated`);
        await fetchData();
    } catch (e) {
        toast.error("Failed to update verification status");
    } finally {
        showConfirmModal.value = false;
    }
};

const isEmployerDisabled = computed(() => {
    if (!roles.value.length) return false;
    const employerRole = roles.value.find(r => r.id === 3);
    return employerRole ? employerRole.status !== 'ACTIVE' : false;
});

const showActionModal = ref(false);
const activeProfileForActions = ref(null);

const openActionModal = (profile) => {
    activeProfileForActions.value = profile;
    showActionModal.value = true;
};

const navigateTo = (path) => {
    showActionModal.value = false;
    router.push(path);
};

onMounted(fetchData);
</script>

<template>
<div class="space-y-6">
        <div v-if="isEmployerDisabled && !loading" 
            class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3 animate-in slide-in-from-top-2 duration-300">
            <div class="h-10 w-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-bold text-amber-900">Creation Disabled</h4>
                <p class="text-xs text-amber-700 mt-0.5">
                    The <span class="font-bold">Employer</span> role is currently inactive. Reactivate it in 
                    <router-link to="/roles" class="underline hover:text-amber-900 transition-colors">Roles Management</router-link>.
                </p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Company Directory</h1>
                <p class="text-sm text-gray-500 mt-1">Centralized hub for partner organizations and employer branding.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-fit">
                    <select v-model="verificationFilter" @change="resetPage"
                        class="w-64 pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-normal text-gray-500 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                        <option value="all">All Status</option>
                        <option value="verified">Verified Only</option>
                        <option value="unverified">Unverified</option>
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </div>
                </div>
                <div class="relative group">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="text" v-model="searchQuery" @input="resetPage" placeholder="Search companies..."
                        class="pl-11 pr-4 py-2.5 w-64 bg-white border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
                
                <button @click="openCreateModal" :disabled="isEmployerDisabled"
                    :class="[isEmployerDisabled ? 'bg-gray-200 text-gray-400 cursor-not-allowed shadow-none' : 'bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white shadow-indigo-200']"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all flex items-center gap-2">
                    <i class="fa-solid fa-plus text-xs"></i> Add Company
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">No</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Organization</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Industry & Size</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Links & HQ</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Specialties</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="loading">
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-500"></i>
                                    <span class="text-sm text-gray-400 font-medium">Fetching company data...</span>
                                </div>
                            </td>
                        </tr>
                        
                        <tr v-else-if="filteredProfiles.length === 0">
                            <td colspan="6" class="px-6 py-20 text-center text-gray-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-20"></i>
                                <p class="text-sm">No companies match your search.</p>
                            </td>
                        </tr>

                        <tr v-for="(profile, index) in paginatedProfiles" :key="profile.id" class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="px-6 py-4 text-xs font-mono text-gray-400">{{ (currentPage - 1) * itemsPerPage + (index + 1) }}</td>
                            
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div @click="openZoom(profile.company_logo_url, profile.company_name)" 
                                        class="h-10 w-10 rounded-xl overflow-hidden bg-gray-50 border border-gray-200 cursor-zoom-in transition-transform hover:scale-110 shadow-sm">
                                        <img v-if="profile.company_logo_url" :src="profile.company_logo_url" class="h-full w-full object-cover">
                                        <div v-else class="h-full w-full flex items-center justify-center text-gray-300"><i class="fa-solid fa-user-check text-xs"></i></div>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-gray-900 truncate">{{ profile.company_name }}</span>
                                            <i v-if="profile.is_verified" class="fa-solid fa-circle-check text-emerald-400 text-[12px]" title="Verified"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] text-gray-400 font-medium italic">
                                                {{ profile.founded_year ? `Est. ${profile.founded_year}` : 'Establishment N/A' }}
                                            </span>
                                            <div class="flex items-center gap-2 group/copy">
                                                <span class="text-[11px] text-gray-500 truncate">{{ profile.user?.email }}</span>
                                                <button @click="copyToClipboard(profile.user?.email, 'Email')" class="opacity-0 group-hover/copy:opacity-100 text-gray-400 hover:text-indigo-600 transition-all">
                                                    <i class="fa-regular fa-copy text-[10px]"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-gray-700">{{ profile.industry || 'N/A' }}</span>
                                    <span class="text-[10px] text-indigo-600 font-bold uppercase tracking-tight">{{ profile.company_size || 'Size Unknown' }}</span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1.5">
                                    <span class="text-xs text-gray-600 flex items-center gap-1">
                                        <i class="fa-solid fa-location-dot text-[10px]"></i>
                                        {{ profile.headquarters_location || 'Remote' }}
                                    </span>
                                    <div class="flex items-center gap-3">
                                        <a v-if="profile.website_url" :href="profile.website_url" target="_blank" class="text-blue-500 text-[11px] font-bold hover:underline">
                                            <i class="fa-solid fa-globe mr-1"></i>Web
                                        </a>
                                        <a v-if="profile.linkedin_url" :href="profile.linkedin_url" target="_blank" class="text-[#0077B5] text-[11px] font-bold hover:underline">
                                            <i class="fa-brands fa-linkedin mr-1"></i>LinkedIn
                                        </a>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1 max-w-[200px]">
                                    <template v-if="profile.specialties?.length">
                                        <span v-for="tag in profile.specialties.slice(0, 2)" :key="tag" class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px]">{{ tag }}</span>
                                        <span v-if="profile.specialties.length > 2" class="text-[10px] text-gray-400 font-bold mt-0.5">+{{ profile.specialties.length - 2 }}</span>
                                    </template>
                                    <span v-else class="text-gray-300 text-[10px]">None</span>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <button @click="openEditModal(profile)" class="text-indigo-600 hover:bg-indigo-50 p-2 rounded-md transition-all" title="Edit Company">
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>
                                    
                                    <button @click="requestToggleVerification(profile)" 
                                        :class="profile.is_verified ? 'text-amber-500 hover:bg-amber-50' : 'text-indigo-500 hover:bg-indigo-50'"
                                        class="p-2 rounded-md transition-all"
                                        :title="profile.is_verified ? 'Revoke Verification' : 'Verify Company'">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </button>

                                    <button @click="openActionModal(profile)" 
                                        class="flex items-center gap-1 bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white px-3 py-2 rounded-xl text-xs font-bold transition-all border border-indigo-100">
                                        Manage
                                        <i class="fa-solid fa-gear text-[10px]"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Showing <span class="text-gray-900 font-bold">{{ paginatedProfiles.length }}</span> of <span class="text-gray-900 font-bold">{{ filteredProfiles.length }}</span></span>
                <div class="flex items-center gap-1">
                    <button @click="currentPage--" :disabled="currentPage === 1" class="p-2 text-gray-500 hover:bg-white hover:shadow-sm rounded-lg disabled:opacity-30 transition-all"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                    <div class="flex items-center px-4"><span class="text-xs font-bold text-gray-700">Page {{ currentPage }} / {{ totalPages }}</span></div>
                    <button @click="currentPage++" :disabled="currentPage >= totalPages" class="p-2 text-gray-500 hover:bg-white hover:shadow-sm rounded-lg disabled:opacity-30 transition-all"><i class="fa-solid fa-chevron-right text-xs"></i></button>
                </div>
            </div>
        </div>

        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="zoomedImage" class="fixed inset-0 z-[100] flex items-center justify-center p-6">
                <div @click="closeZoom" class="absolute inset-0 bg-zinc-900/90 backdrop-blur-sm"></div>
                <div class="relative max-w-sm w-full bg-white rounded-2xl overflow-hidden shadow-2xl">
                    <div class="aspect-square bg-zinc-50 flex items-center justify-center">
                        <img :src="zoomedImage" class="w-full h-full object-contain" @click.stop />
                    </div>
                    <div class="p-4 bg-white"><h3 class="text-zinc-900 text-center font-bold">{{ zoomName }}</h3></div>
                </div>
            </div>
        </Transition>

        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="showConfirmModal" class="fixed inset-0 z-[110] flex items-center justify-center p-6">
                <div @click="showConfirmModal = false" class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm"></div>
                <div class="relative max-w-sm w-full bg-white rounded-2xl shadow-2xl p-6 border border-gray-100">
                    <div :class="confirmConfig.type === 'warning' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600'" 
                         class="h-12 w-12 rounded-xl flex items-center justify-center mb-4 text-xl">
                         <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">{{ confirmConfig.title }}</h3>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ confirmConfig.message }}</p>
                    <div class="mt-8 flex gap-3">
                        <button @click="showConfirmModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-gray-500 bg-gray-50 hover:bg-gray-100 transition-colors">Discard</button>
                        <button @click="confirmConfig.action" :class="confirmConfig.type === 'warning' ? 'bg-amber-500 hover:bg-amber-600' : 'bg-indigo-600 hover:bg-indigo-700'" 
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg active:scale-95 transition-all">Confirm</button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div v-if="showActionModal" class="fixed inset-0 z-[120] flex items-center justify-center p-6">
                <div @click="showActionModal = false" class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm"></div>
                
                <div class="relative max-w-sm w-full bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100">
                    <div class="p-6 text-center border-b border-gray-50 bg-gray-50/50">
                        <div class="h-16 w-16 rounded-2xl bg-white shadow-sm mx-auto mb-3 overflow-hidden border border-gray-100 flex items-center justify-center">
                            <img v-if="activeProfileForActions?.company_logo_url" :src="activeProfileForActions.company_logo_url" class="h-full w-full object-cover">
                            <div v-else class="h-full w-full flex items-center justify-center text-indigo-200">
                                <i class="fa-solid fa-building text-2xl"></i>
                            </div>
                        </div>
                        <h3 class="text-lg font-black text-gray-900 leading-tight">
                            {{ activeProfileForActions?.company_name }}
                        </h3>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mt-1">Employer Management Hub</p>
                    </div>

                    <div class="p-4 grid grid-cols-1 gap-2">
                        <button @click="navigateTo(`/employer-profiles/${activeProfileForActions.id}/job-posts`)"
                            class="group flex items-center justify-between p-4 rounded-2xl hover:bg-indigo-50 border border-transparent hover:border-indigo-100 transition-all text-left">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-bullhorn"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">Job Listings</p>
                                    <p class="text-[10px] text-gray-500 font-medium">Manage vacancies & recruitment</p>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 group-hover:text-indigo-500"></i>
                        </button>

                        <button disabled
                            class="group flex items-center justify-between p-4 rounded-2xl bg-gray-50/50 border border-gray-100 cursor-not-allowed opacity-70 transition-all text-left">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                    <i class="fa-solid fa-chart-pie"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-400">Company Insights</p>
                                    <p class="text-[10px] text-gray-400 font-medium italic">Statistics & performance logs</p>
                                </div>
                            </div>
                            <i class="fa-solid fa-lock text-[10px] text-gray-300"></i>
                        </button>
                    </div>

                    <div class="p-4 bg-gray-50/80 mt-2">
                        <button @click="showActionModal = false" 
                            class="w-full py-3 rounded-xl text-xs font-bold text-gray-500 hover:bg-white hover:shadow-sm border border-transparent hover:border-gray-200 transition-all">
                            Close Hub
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

    <EmployerProfileModal 
        :is-open="showModal" 
        :profile-data="selectedProfile" 
        :users="users" 
        :profiles="profiles"  :is-editing="isEditing"
        :loading="saving"
        @close="showModal = false"
        @save="handleSave"
    />
</template>