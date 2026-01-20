<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from "vue-toastification";
import api from '@/services/api';
import UserModal from '@/components/UserModal.vue';

const users = ref([]);
const roles = ref([]);
const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const selectedUser = ref(null);
const isEditing = ref(false);

const statusFilter = ref('');
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(8);

const toast = useToast();

const fetchData = async () => {
    loading.value = true;
    try {
        const [userRes, roleRes] = await Promise.all([
            api.get('/users'),
            api.get('/roles')
        ]);
        users.value = userRes.data.data || userRes.data;
        roles.value = roleRes.data.data || roleRes.data;
    } catch (e) {
        toast.error("Failed to sync data from server");
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

const filteredUsers = computed(() => {
    return users.value.filter(user => {
        const fullName = `${user.first_name} ${user.last_name}`.toLowerCase();
        const matchesStatus = statusFilter.value === '' || user.status === statusFilter.value;
        const matchesSearch = fullName.includes(searchQuery.value.toLowerCase()) || 
                             user.email.toLowerCase().includes(searchQuery.value.toLowerCase());
        return matchesStatus && matchesSearch;
    });
});

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / itemsPerPage.value) || 1);

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredUsers.value.slice(start, start + itemsPerPage.value);
});

const resetPage = () => { currentPage.value = 1; };

const openCreateModal = () => {
    isEditing.value = false;
    selectedUser.value = null;
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    selectedUser.value = { ...user };
    showModal.value = true;
};

const handleSave = async (formData) => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await api.put(`/users/${formData.id}`, formData);
            toast.success("User updated successfully!");
        } else {
            await api.post('/users', { ...formData, status: 'ACTIVE' });
            toast.success("User account created!");
        }
        await fetchData();
        showModal.value = false;
    } catch (e) {
        if (e.response?.status === 422) {
            const validationErrors = e.response.data.errors;
            toast.error(Object.values(validationErrors)[0][0]); 
        } else {
            toast.error("Server error. Please try again later.");
        }
    } finally {
        saving.value = false;
    }
};

const toggleActive = async (user) => {
    const action = user.status === 'ACTIVE' ? 'deactivate' : 'reactivate';
    if (!confirm(`Are you sure you want to ${action} this user?`)) return;
    try {
        await api.delete(`/users/${user.id}`);
        toast.info(`User ${user.status === 'ACTIVE' ? 'deactivated' : 'reactivated'}`);
        fetchData();
    } catch (e) {
        toast.error(e.response?.data?.message || "Failed to update status");
    }
};

const handleSuspend = async (user) => {
    const isSuspended = user.status === 'SUSPENDED';
    const action = isSuspended ? 'unsuspend' : 'suspend';
    if (!confirm(`Are you sure you want to ${action} this user?`)) return;
    try {
        await api.patch(`/users/${user.id}/suspend`);
        isSuspended ? toast.success(`User unsuspended`) : toast.warning(`User suspended`);
        fetchData();
    } catch (e) {
        toast.error(e.response?.data?.message || "Action failed");
    }
};

onMounted(fetchData);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">User Management</h1>
                <p class="text-sm text-gray-500 mt-1">Manage system access, security settings, and profiles.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-fit">
                    <select v-model="statusFilter" @change="resetPage"
                        class="w-64 pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-normal text-gray-500 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="ACTIVE">Active</option>
                        <option value="INACTIVE">Inactive</option>
                        <option value="SUSPENDED">Suspended</option>
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </div>
                </div>

                <div class="relative group">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="text" v-model="searchQuery" @input="resetPage" placeholder="Search users..."
                        class="pl-11 pr-4 py-2.5 w-64 bg-white border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
                
                <button @click="openCreateModal"
                    class="bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Add New User
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">No</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">User Identity</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Contact Info</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Access Level</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="loading">
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-500"></i>
                                    <span class="text-sm text-gray-400 font-medium">Fetching users...</span>
                                </div>
                            </td>
                        </tr>
                        
                        <tr v-else-if="filteredUsers.length === 0">
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-400">
                                    <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-20"></i>
                                    <p class="text-sm">No users match your current filters.</p>
                                </div>
                            </td>
                        </tr>

                        <tr v-for="(user, index) in paginatedUsers" :key="user.id" class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="px-6 py-4 text-xs font-mono text-gray-400">
                                {{ (currentPage - 1) * itemsPerPage + (index + 1) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900 text-sm group-hover:text-indigo-600 transition-colors">{{ user.first_name }} {{ user.last_name }}</span>
                                    <div class="flex items-center gap-2 group/copy">
                                        <span class="text-[11px] text-gray-500">{{ user.email }}</span>
                                        <button @click="copyToClipboard(user.email, 'Email')" 
                                                class="opacity-0 group-hover/copy:opacity-100 text-gray-400 hover:text-indigo-600 transition-all">
                                            <i class="fa-regular fa-copy text-[10px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="user.phone" class="flex items-center gap-2 text-gray-600 group/copy">
                                    <i class="fa-solid fa-phone text-[10px] text-gray-300"></i>
                                    <span class="text-xs font-medium">{{ user.phone }}</span>
                                    <button @click="copyToClipboard(user.phone, 'Phone number')" 
                                            class="opacity-0 group-hover/copy:opacity-100 text-gray-400 hover:text-indigo-600 transition-all">
                                        <i class="fa-regular fa-copy text-[10px]"></i>
                                    </button>
                                </div>
                                <span v-else class="text-gray-300 text-xs">—</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">
                                    {{ user.role?.name || 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="{
                                    'bg-emerald-50 text-emerald-600 border-emerald-100': user.status === 'ACTIVE',
                                    'bg-rose-50 text-rose-600 border-rose-100': user.status === 'INACTIVE',
                                    'bg-amber-50 text-amber-600 border-amber-100': user.status === 'SUSPENDED'
                                }" class="px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-tight uppercase border">
                                    {{ user.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <button @click="openEditModal(user)" class="text-indigo-600 hover:bg-indigo-50 p-2 rounded-md transition-all" title="Edit User">
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>
                                    
                                    <button @click="handleSuspend(user)" 
                                        :disabled="user.status === 'INACTIVE'"
                                        :class="[
                                            'p-2 rounded-md transition-all disabled:opacity-20',
                                            user.status === 'SUSPENDED' ? 'text-emerald-600 hover:bg-emerald-50' : 'text-amber-500 hover:bg-amber-50'
                                        ]"
                                        :title="user.status === 'SUSPENDED' ? 'Unsuspend User' : 'Suspend User'">
                                        <i v-if="user.status === 'SUSPENDED'" class="fa-solid fa-unlock"></i>
                                        <i v-else class="fa-solid fa-ban"></i>
                                    </button>

                                    <button @click="toggleActive(user)"
                                        :disabled="user.status === 'SUSPENDED'"
                                        :class="user.status === 'ACTIVE' ? 'text-red-400 hover:bg-red-50' : 'text-emerald-500 hover:bg-emerald-50'"
                                        class="p-2 rounded-md transition-all disabled:opacity-20"
                                        :title="user.status === 'ACTIVE' ? 'Deactivate User' : 'Reactivate User'">
                                        <i v-if="user.status === 'ACTIVE'" class="fa-solid fa-trash-can"></i>
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
                    Showing <span class="text-gray-900 font-bold">{{ paginatedUsers.length }}</span> of <span class="text-gray-900 font-bold">{{ filteredUsers.length }}</span>
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
    </div>

    <UserModal 
        :is-open="showModal" 
        :user-data="selectedUser" 
        :roles="roles" 
        :is-editing="isEditing"
        :loading="saving"
        @close="showModal = false"
        @save="handleSave"
    />
</template>