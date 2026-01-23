<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from "vue-toastification";
import api from '@/services/api';
import SkillModal from '@/components/SkillModal.vue';

const skills = ref([]);
const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const selectedskill = ref(null);
const isEditing = ref(false);

const statusFilter = ref('');
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(8);

const toast = useToast();

const showConfirmModal = ref(false);
const confirmConfig = ref({ title: '', message: '', action: null, type: 'indigo', icon: '' });

const fetchskills = async () => {
    loading.value = true;
    try {
        const response = await api.get('/skills');
        skills.value = response.data.data || response.data;
    } catch (e) {
        toast.error("Failed to sync skills from server");
    } finally {
        loading.value = false;
    }
};

const filteredskills = computed(() => {
    return skills.value.filter(skill => {
        const matchesStatus = statusFilter.value === '' || skill.status === statusFilter.value;
        const matchesSearch = skill.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        return matchesStatus && matchesSearch;
    });
});

const totalPages = computed(() => Math.ceil(filteredskills.value.length / itemsPerPage.value) || 1);

const paginatedskills = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredskills.value.slice(start, start + itemsPerPage.value);
});

const resetPage = () => { currentPage.value = 1; };

const openCreateModal = () => {
    isEditing.value = false;
    selectedskill.value = null;
    showModal.value = true;
};

const openEditModal = (skill) => {
    isEditing.value = true;
    selectedskill.value = { ...skill };
    showModal.value = true;
};

const handleSave = async (formData) => {
    if (!formData.name) return toast.error('Name is required');
    
    saving.value = true;
    try {
        if (isEditing.value) {
            const updateData = {
                name: formData.name,
                desc: formData.desc
            };
            await api.put(`/skills/${formData.id}`, updateData);
            toast.success("skill updated successfully!");
        } else {
            await api.post('/skills', formData);
            toast.success("skill created successfully!");
        }
        await fetchskills();
        showModal.value = false;
    } catch (e) {
        if (e.response && e.response.status === 422) {
            const validationErrors = e.response.data.errors;
            
            if (validationErrors.name) {
                toast.error(validationErrors.name[0]); 
            } else {
                toast.error("Please check your input.");
            }
        } else {
            toast.error("Server error. Please try again later.");
            console.error(e);
        }
    } finally {
        saving.value = false;
    }
};

const requestToggleStatus = (skill) => {
    const isActive = skill.status === 'ACTIVE';
    confirmConfig.value = {
        title: isActive ? 'Deactivate skill?' : 'Reactivate skill?',
        message: isActive 
            ? `Are you sure you want to deactivate the "${skill.name}" skill? Users assigned to this skill may lose access to certain features.` 
            : `This will restore the "${skill.name}" skill, allowing it to be assigned to users again.`,
        type: isActive ? 'danger' : 'indigo',
        icon: isActive ? 'fa-trash-can' : 'fa-rotate-left',
        action: () => executeToggleStatus(skill.id)
    };
    showConfirmModal.value = true;
};

const executeToggleStatus = async (id) => {
    try {
        await api.delete(`/skills/${id}`);
        toast.info("skill status updated");
        await fetchskills();
    } catch (e) {
        toast.error("Failed to update skill status");
    } finally {
        showConfirmModal.value = false;
    }
};

onMounted(fetchskills);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Skill Management</h1>
                <p class="text-sm text-gray-500 mt-1">Standardized library of technical and soft skill definitions.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-fit">
                    <select v-model="statusFilter" @change="resetPage"
                        class="w-64 pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-normal text-gray-500 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="ACTIVE">Active</option>
                        <option value="INACTIVE">Inactive</option>
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </div>
                </div>

                <div class="relative group">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="text" v-model="searchQuery" @input="resetPage" placeholder="Search skills..."
                        class="pl-11 pr-4 py-2.5 w-64 bg-white border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
                
                <button @click="openCreateModal"
                    class="bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Add New skill
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">No</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Skill Info</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="loading">
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-500"></i>
                                    <span class="text-sm text-gray-400 font-medium">Fetching skills...</span>
                                </div>
                            </td>
                        </tr>
                        
                        <tr v-else-if="filteredskills.length === 0">
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-400">
                                    <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-20"></i>
                                    <p class="text-sm">No skills match your current filters.</p>
                                </div>
                            </td>
                        </tr>

                        <tr v-for="(skill, index) in paginatedskills" :key="skill.id" class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="px-6 py-4 text-xs font-mono text-gray-400">
                                {{ (currentPage - 1) * itemsPerPage + (index + 1) }}
                            </td>
                            <td v-if="filteredskills.length > 0" class="px-6 py-4">
                                <div class="font-bold text-gray-900 text-sm group-hover:text-indigo-600 transition-colors">{{ skill.name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-500 line-clamp-1 max-w-xs">{{ skill.desc || '—' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="skill.status === 'ACTIVE' 
                                    ? 'bg-emerald-50 text-emerald-600 border-emerald-100' 
                                    : 'bg-rose-50 text-rose-600 border-rose-100'"
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-tight uppercase border">
                                    {{ skill.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <button @click="openEditModal(skill)" class="text-indigo-600 hover:bg-indigo-50 p-2 rounded-md transition-all">
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>
                                    <button @click="requestToggleStatus(skill)"
                                        :class="skill.status === 'ACTIVE' ? 'text-red-400 hover:bg-red-50' : 'text-emerald-500 hover:bg-emerald-50'"
                                        class="p-2 rounded-md transition-all"
                                        :title="skill.status === 'ACTIVE' ? 'Deactivate skill' : 'Reactivate skill'">
                                        <i v-if="skill.status === 'ACTIVE'" class="fa-solid fa-trash-can"></i>
                                        <i v-else class="fa-solid fa-rotate-left"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">
                    Showing <span class="text-gray-900 font-bold">{{ paginatedskills.length }}</span> of <span class="text-gray-900 font-bold">{{ filteredskills.length }}</span>
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
            <div v-if="showConfirmModal" class="fixed inset-0 z-[110] flex items-center justify-center p-6">
                <div @click="showConfirmModal = false" class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm"></div>
                
                <div class="relative max-w-sm w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div :class="{
                            'bg-rose-50 text-rose-600': confirmConfig.type === 'danger',
                            'bg-indigo-50 text-indigo-600': confirmConfig.type === 'indigo'
                        }" class="h-12 w-12 rounded-xl flex items-center justify-center mb-4 text-xl">
                            <i :class="['fa-solid', confirmConfig.icon]"></i>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">{{ confirmConfig.title }}</h3>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ confirmConfig.message }}</p>
                        
                        <div class="mt-8 flex gap-3">
                            <button @click="showConfirmModal = false" 
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-gray-500 bg-gray-50 hover:bg-gray-100 transition-colors">
                                Discard
                            </button>
                            <button @click="confirmConfig.action" 
                                :class="{
                                    'bg-rose-500 hover:bg-rose-600': confirmConfig.type === 'danger',
                                    'bg-indigo-600 hover:bg-indigo-700': confirmConfig.type === 'indigo'
                                }"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg transition-all active:scale-95">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

    <skillModal 
        :is-open="showModal" 
        :skill-data="selectedskill" 
        :is-editing="isEditing"
        :loading="saving"
        @close="showModal = false"
        @save="handleSave"
    />
</template>