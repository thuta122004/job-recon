<script setup>
import { reactive, watch, ref } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    roleData: Object,
    isEditing: Boolean,
    loading: Boolean
});

const emit = defineEmits(['close', 'save']);

const form = reactive({
    id: null,
    name: '',
    desc: '',
    status: 'ACTIVE'
});

// Whenever roleData changes (when editing), update the form
watch(() => props.roleData, (newVal) => {
    if (newVal) {
        Object.assign(form, newVal);
    } else {
        Object.assign(form, { id: null, name: '', desc: '', status: 'ACTIVE' });
    }
}, { deep: true });

const handleSubmit = () => {
    emit('save', { ...form });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="$emit('close')" class="absolute inset-0 bg-indigo-900/20 backdrop-blur-sm transition-opacity"></div>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-800">{{ isEditing ? 'Update Existing Role' : 'Create New Role' }}</h3>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-8 space-y-6">
                <div class="relative">
                    <input type="text" id="r_name" v-model="form.name" placeholder=" "
                        class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none peer appearance-none" />
                    <label for="r_name" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Role Name</label>
                </div>

                <div class="relative">
                    <textarea id="r_desc" v-model="form.desc" rows="3" placeholder=" "
                        class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none peer appearance-none"></textarea>
                    <label for="r_desc" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Description</label>
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center cursor-pointer group">
                        <input type="radio" v-model="form.status" value="ACTIVE" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-600 group-hover:text-indigo-600 transition-colors">Active</span>
                    </label>
                    <label class="flex items-center cursor-pointer group">
                        <input type="radio" v-model="form.status" value="INACTIVE" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-600 group-hover:text-indigo-600 transition-colors">Inactive</span>
                    </label>
                </div>
            </div>

            <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button @click="$emit('close')" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</button>
                <button @click="handleSubmit" :disabled="loading"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 flex items-center gap-2">
                    <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
                    {{ isEditing ? 'Save Changes' : 'Confirm & Create' }}
                </button>
            </div>
        </div>
    </div>
</template>