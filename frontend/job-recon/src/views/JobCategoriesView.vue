<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from "vue-toastification";
import api from '@/services/api';
import JobCategoryModal from '@/components/JobCategoryModal.vue';

const categories = ref([]);
const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const selectedCategory = ref(null);
const isEditing = ref(false);

const statusFilter = ref('');
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(8);

const toast = useToast();

const showConfirmModal = ref(false);
const confirmConfig = ref({ title: '', message: '', action: null, type: 'indigo', icon: '' });

const fetchCategories = async () => {
    loading.value = true;
    try {
        const response = await api.get('/job-categories');
        categories.value = response.data.data || response.data;
    } catch (e) {
        toast.error("Failed to sync categories from server");
    } finally {
        loading.value = false;
    }
};

const filteredCategories = computed(() => {
    return categories.value.filter(cat => {
        const matchesStatus = statusFilter.value === '' || cat.status === statusFilter.value;
        const matchesSearch = cat.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        return matchesStatus && matchesSearch;
    });
});

const totalPages = computed(() => Math.ceil(filteredCategories.value.length / itemsPerPage.value) || 1);

const paginatedCategories = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredCategories.value.slice(start, start + itemsPerPage.value);
});

const resetPage = () => { currentPage.value = 1; };

const openCreateModal = () => {
    isEditing.value = false;
    selectedCategory.value = null;
    showModal.value = true;
};

const openEditModal = (category) => {
    isEditing.value = true;
    selectedCategory.value = { ...category };
    showModal.value = true;
};

const handleSave = async (formData) => {
    if (!formData.name) return toast.error('Name is required');
    
    saving.value = true;
    try {
        if (isEditing.value) {
            await api.put(`/job-categories/${formData.id}`, formData);
            toast.success("Category updated successfully!");
        } else {
            await api.post('/job-categories', formData);
            toast.success("Category created successfully!");
        }
        await fetchCategories();
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
        }
    } finally {
        saving.value = false;
    }
};

const requestToggleStatus = (category) => {
    const isActive = category.status === 'ACTIVE';
    confirmConfig.value = {
        title: isActive ? 'Deactivate Category?' : 'Reactivate Category?',
        message: isActive 
            ? `Are you sure you want to deactivate "${category.name}"? This sector will be hidden from public job listings.` 
            : `This will restore the "${category.name}" sector to the active industry list.`,
        type: isActive ? 'danger' : 'indigo',
        icon: isActive ? 'fa-trash-can' : 'fa-rotate-left',
        action: () => executeToggleStatus(category.id)
    };
    showConfirmModal.value = true;
};

const executeToggleStatus = async (id) => {
    try {
        await api.delete(`/job-categories/${id}`);
        toast.info("Category status updated");
        await fetchCategories();
    } catch (e) {
        toast.error("Failed to update status");
    } finally {
        showConfirmModal.value = false;
    }
};

onMounted(fetchCategories);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Category Management</h1>
                <p class="text-sm text-gray-500 mt-1">Manage industry sectors and job classifications.</p>
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
                    <input type="text" v-model="searchQuery" @input="resetPage" placeholder="Search categories..."
                        class="pl-11 pr-4 py-2.5 w-64 bg-white border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
                
                <button @click="openCreateModal"
                    class="bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Add Category
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">No</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Icon & Name</th>
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
                                    <span class="text-sm text-gray-400 font-medium">Fetching categories...</span>
                                </div>
                            </td>
                        </tr>
                        
                        <tr v-else-if="filteredCategories.length === 0">
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-400">
                                    <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-20"></i>
                                    <p class="text-sm">No categories match your current filters.</p>
                                </div>
                            </td>
                        </tr>

                        <tr v-for="(cat, index) in paginatedCategories" :key="cat.id" class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="px-6 py-4 text-xs font-mono text-gray-400">
                                {{ (currentPage - 1) * itemsPerPage + (index + 1) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 border border-gray-100 group-hover:bg-white group-hover:text-indigo-600 transition-all">
                                        <i :class="cat.icon_class || 'fa-solid fa-layer-group'"></i>
                                    </div>
                                    <div class="font-bold text-gray-900 text-sm group-hover:text-indigo-600 transition-colors">{{ cat.name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-500 line-clamp-1 max-w-xs">{{ cat.description || '—' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="cat.status === 'ACTIVE' 
                                    ? 'bg-emerald-50 text-emerald-600 border-emerald-100' 
                                    : 'bg-rose-50 text-rose-600 border-rose-100'"
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-tight uppercase border">
                                    {{ cat.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <button @click="openEditModal(cat)" class="text-indigo-600 hover:bg-indigo-50 p-2 rounded-md transition-all">
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>
                                    <button @click="requestToggleStatus(cat)"
                                        :class="cat.status === 'ACTIVE' ? 'text-red-400 hover:bg-red-50' : 'text-emerald-500 hover:bg-emerald-50'"
                                        class="p-2 rounded-md transition-all"
                                        :title="cat.status === 'ACTIVE' ? 'Deactivate Category' : 'Reactivate Category'">
                                        <i v-if="cat.status === 'ACTIVE'" class="fa-solid fa-trash-can"></i>
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
                    Showing <span class="text-gray-900 font-bold">{{ paginatedCategories.length }}</span> of <span class="text-gray-900 font-bold">{{ filteredCategories.length }}</span>
                </span>
                
                <div class="flex items-center gap-1">
                    <button @click="currentPage--" :disabled="currentPage === 1"
                        class="p-2 text-gray-500 hover:bg-white hover:shadow-sm rounded-lg disabled:opacity-30 transition-all">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>
                    <div class="flex items-center px-4">
                        <span class="text-xs font-bold text-gray-700">Page {{ currentPage }} <span class="text-gray-400 font-normal mx-1">/</span> {{ totalPages }}</span>
                    </div>
                    <button @click="currentPage++" :disabled="currentPage >= totalPages"
                        class="p-2 text-gray-500 hover:bg-white hover:shadow-sm rounded-lg disabled:opacity-30 transition-all">
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
                        <div :class="confirmConfig.type === 'danger' ? 'bg-rose-50 text-rose-600' : 'bg-indigo-50 text-indigo-600'" 
                             class="h-12 w-12 rounded-xl flex items-center justify-center mb-4 text-xl">
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
                                :class="confirmConfig.type === 'danger' ? 'bg-rose-500 hover:bg-rose-600' : 'bg-indigo-600 hover:bg-indigo-700'"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg transition-all active:scale-95">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

    <JobCategoryModal 
        :is-open="showModal" 
        :category-data="selectedCategory" 
        :is-editing="isEditing"
        :loading="saving"
        @close="showModal = false"
        @save="handleSave"
    />
</template>