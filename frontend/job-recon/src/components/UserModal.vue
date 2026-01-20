<script setup>
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    userData: Object,
    roles: Array, 
    isEditing: Boolean,
    loading: Boolean
});

const emit = defineEmits(['close', 'save']);

const form = reactive({
    id: null,
    role_id: '',
    first_name: '',
    last_name: '',
    email: '',
    password: '', 
    password_confirmation: '',
    phone: '',
});

const activeRoles = computed(() => {
    return props.roles?.filter(role => role.status === 'ACTIVE') ?? [];
});

watch(() => props.isOpen, (newVal) => {
    if (newVal && !props.isEditing) {
        Object.assign(form, { 
            id: null, 
            role_id: '',
            first_name: '', 
            last_name: '', 
            email: '',
            password: '',
            password_confirmation: '',
            phone: '',
        });
    } 
    else if (newVal && props.isEditing && props.userData) {
        Object.assign(form, {
            id: props.userData.id,
            role_id: props.userData.role_id,
            first_name: props.userData.first_name,
            last_name: props.userData.last_name,
            email: props.userData.email,
            phone: props.userData.phone,
            password: '', 
            password_confirmation: ''
        });
    }
});

const handleSubmit = () => {
    emit('save', { ...form });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="$emit('close')" class="absolute inset-0 bg-indigo-900/20 backdrop-blur-sm transition-opacity"></div>
        
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative z-10 overflow-hidden border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">{{ isEditing ? 'Update User Profile' : 'Create New User' }}</h3>
                    <p class="text-xs text-gray-500">Configure account details and system access levels.</p>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 p-2 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-8">
                    
                    <div class="relative">
                        <input type="text" id="u_fn" v-model="form.first_name" placeholder=" "
                            class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="u_fn" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">First Name</label>
                    </div>

                    <div class="relative">
                        <input type="text" id="u_ln" v-model="form.last_name" placeholder=" "
                            class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="u_ln" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Last Name</label>
                    </div>

                    <div class="relative">
                        <select id="u_role" v-model="form.role_id"
                            class="block w-full px-4 py-4 text-sm text-gray-500 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none appearance-none cursor-pointer transition-all">
                            <option value="" disabled>Select Role</option>
                            <option v-for="role in activeRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                            <i class="fa-solid fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>

                    <div class="relative">
                        <input type="text" id="u_phone" v-model="form.phone" placeholder=" "
                            class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="u_phone" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Phone Number</label>
                    </div>

                    <div class="relative md:col-span-2">
                        <input type="email" id="u_email" v-model="form.email" placeholder=" "
                            class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="u_email" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Email Address</label>
                    </div>

                    <div class="relative">
                        <input type="password" id="u_pw" v-model="form.password" placeholder=" "
                            class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="u_pw" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">
                            {{ isEditing ? 'New Password (Optional)' : 'Account Password' }}
                        </label>
                    </div>

                    <div class="relative">
                        <input type="password" id="u_pwc" v-model="form.password_confirmation" placeholder=" "
                            class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="u_pwc" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Confirm Password</label>
                    </div>

                    <div class="md:col-span-2 -mt-4">
                        <p class="text-[10px] text-gray-400 flex items-center gap-1.5 px-1">
                            <i class="fa-solid fa-circle-info text-indigo-400/60"></i>
                            {{ isEditing ? 'Leave password fields blank to keep current password.' : 'User will be required to use this password for their first login.' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button @click="$emit('close')" 
                    class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                    Discard
                </button>
                <button @click="handleSubmit" :disabled="loading"
                    class="bg-indigo-600 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 active:scale-95 disabled:opacity-50 disabled:active:scale-100 transition-all flex items-center gap-2">
                    <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
                    {{ isEditing ? 'Save Changes' : 'Confirm Registration' }}
                </button>
            </div>
        </div>
    </div>
</template>