<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from "vue-toastification";
import api from '@/services/api';
import RoleModal from '@/components/RoleModal.vue';

// State
const roles = ref([]);
const loading = ref(false);
const saving = ref(false);
const error = ref(false);
const showModal = ref(false);
const isEditing = ref(false);
const searchQuery = ref('');
const selectedRole = ref(null);

const toast = useToast();

// Computed
const filteredRoles = computed(() => {
    return roles.value.filter(r => r.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

// Methods
const fetchRoles = async () => {
    loading.value = true;
    error.value = false;
    try {
        const res = await api.get('/roles');
        roles.value = res.data.data || res.data;
    } catch (e) {
        error.value = true;
        toast.error("Failed to connect to API");
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    isEditing.value = false;
    selectedRole.value = null;
    showModal.value = true;
};

const openEditModal = (role) => {
    isEditing.value = true;
    selectedRole.value = role; // Pass data to modal
    showModal.value = true;
};

const handleSave = async (formData) => {
    if (!formData.name) return toast.warning('Name is required');
    
    saving.value = true;
    try {
        if (isEditing.value) {
            await api.put(`/roles/${formData.id}`, formData);
            toast.success("Role updated successfully!");
        } else {
            await api.post('/roles', formData);
            toast.success("Role created successfully!");
        }
        await fetchRoles();
        showModal.value = false;
    } catch (e) {
        toast.error("Error saving data. Check console.");
        console.error(e);
    } finally {
        saving.value = false;
    }
};

const deleteRole = async (id, currentStatus) => {
    const action = currentStatus === 'ACTIVE' ? 'deactivate' : 'reactivate';
    if (!confirm(`Are you sure you want to ${action} this role?`)) return;

    try {
        await api.delete(`/roles/${id}`);
        toast.info(`Role status changed`);
        fetchRoles();
    } catch (e) {
        toast.error("Failed to update status");
    }
};

onMounted(fetchRoles);
</script>

<template>
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Roles Management</h1>
            <p class="text-sm text-gray-500">Create and manage access levels for your team.</p>
        </div>
        <div class="flex gap-3">
            <input type="text" v-model="searchQuery" placeholder="Filter by name..."
                class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none w-64">
            <button @click="openCreateModal"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-medium shadow-sm transition-all">
                <i class="fa-solid fa-plus mr-2"></i>Add Role
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">#</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Role Name</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr v-if="loading"><td colspan="5" class="px-6 py-12 text-center text-gray-400">Loading...</td></tr>
                <tr v-else-if="error"><td colspan="5" class="px-6 py-12 text-center text-red-500">API Error</td></tr>
                <tr v-else-if="filteredRoles.length === 0"><td colspan="5" class="px-6 py-12 text-center text-gray-400">No roles found.</td></tr>

                <tr v-for="role in filteredRoles" :key="role.id" class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4 text-sm text-gray-400">#{{ role.id }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ role.name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ role.desc || 'No description' }}</td>
                    <td class="px-6 py-4">
                        <span :class="role.status === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            class="px-2.5 py-1 rounded-md text-[10px] font-bold tracking-wider uppercase">
                            {{ role.status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button @click="openEditModal(role)" class="text-indigo-600 hover:bg-indigo-50 p-2 rounded-md transition-all">
                            <i class="fa-solid fa-pencil"></i>
                        </button>
                        <button @click="deleteRole(role.id, role.status)"
                            :class="role.status === 'ACTIVE' ? 'text-red-400 hover:bg-red-50' : 'text-emerald-500 hover:bg-emerald-50'"
                            class="p-2 rounded-md transition-all">
                            <i v-if="role.status === 'ACTIVE'" class="fa-solid fa-trash-can"></i>
                            <i v-else class="fa-solid fa-rotate-left"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <RoleModal 
        :is-open="showModal" 
        :role-data="selectedRole" 
        :is-editing="isEditing"
        :loading="saving"
        @close="showModal = false"
        @save="handleSave"
    />
</template>