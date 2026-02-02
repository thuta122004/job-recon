<script setup>
import { computed, reactive, watch, ref } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    userData: Object,
    roles: Array, 
    isEditing: Boolean,
    loading: Boolean,
    disableRole: Boolean
});

const emit = defineEmits(['close', 'save']);

const roleSearch = ref('');
const showRoleDropdown = ref(false);

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

const filteredRoles = computed(() => {
    const query = roleSearch.value.toLowerCase().trim();
    if (!query) return activeRoles.value;
    return activeRoles.value.filter(role => 
        role.name.toLowerCase().includes(query)
    );
});

const selectRole = (role) => {
    form.role_id = role.id;
    roleSearch.value = role.name;
    showRoleDropdown.value = false;
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (!props.isEditing) {
            Object.assign(form, { 
                id: null, role_id: '', first_name: '', last_name: '', 
                email: '', password: '', password_confirmation: '', phone: '',
            });
            roleSearch.value = '';
        } 
        else if (props.userData) {
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
            const currentRole = props.roles?.find(r => r.id == props.userData.role_id);
            roleSearch.value = currentRole ? currentRole.name : '';
        }
    }
    showRoleDropdown.value = false;
});

const passwordsMatch = computed(() => {
    if (!form.password && !form.password_confirmation) return true;
    return form.password === form.password_confirmation;
});

const handleSubmit = () => {
    emit('save', { ...form });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="$emit('close')" class="absolute inset-0 bg-indigo-900/20 backdrop-blur-sm transition-opacity"></div>
        
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative z-10 overflow-hidden border border-gray-100 flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">{{ isEditing ? 'Update User Profile' : 'Create New User' }}</h3>
                    <p class="text-xs text-gray-500">Manage system access, account status, and authentication.</p>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 p-2 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-8 overflow-y-auto">
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

                    <div class="relative flex flex-col gap-1">
                        <div class="relative">
                            <div class="relative group">
                                <input 
                                    type="text"
                                    v-model="roleSearch"
                                    @focus="!disableRole && (showRoleDropdown = true)"
                                    @blur="setTimeout(() => showRoleDropdown = false, 200)"
                                    :disabled="disableRole"
                                    placeholder="Search for a role..."
                                    class="block w-full px-4 py-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all disabled:bg-gray-50 disabled:text-gray-400"
                                />
                                
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2 pointer-events-none">
                                    <i v-if="disableRole" class="fa-solid fa-lock text-[10px] text-amber-500"></i>
                                    <i v-else-if="!form.role_id" class="fa-solid fa-magnifying-glass text-xs text-gray-300"></i>
                                    <i v-else class="fa-solid fa-check text-xs text-emerald-500"></i>
                                </div>
                            </div>

                            <transition
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <div v-if="showRoleDropdown && !disableRole" 
                                     class="absolute z-[60] w-full mt-2 bg-white border border-gray-100 shadow-2xl rounded-2xl overflow-hidden">
                                    <div class="max-h-48 overflow-y-auto p-2 space-y-1">
                                        <div v-if="filteredRoles.length === 0" class="p-4 text-center">
                                            <p class="text-[11px] text-gray-400">No matching roles found</p>
                                        </div>
                                        <button 
                                            v-for="role in filteredRoles" 
                                            :key="role.id"
                                            type="button"
                                            @mousedown="selectRole(role)"
                                            class="w-full text-left px-4 py-3 rounded-xl hover:bg-indigo-50 transition-all flex items-center justify-between group"
                                        >
                                            <span class="text-sm font-bold text-gray-700 group-hover:text-indigo-600">{{ role.name }}</span>
                                            <i class="fa-solid fa-plus text-[10px] text-gray-300 group-hover:text-indigo-400"></i>
                                        </button>
                                    </div>
                                </div>
                            </transition>
                        </div>
                        <p v-if="disableRole" class="text-[10px] text-amber-500 font-bold mt-2 ml-1 flex items-center gap-1.5">
                            <i class="fa-solid fa-lock text-[9px]"></i>
                            <span>Role locked: This user is currently linked to an active employer profile.</span>
                        </p>

                        <p v-else-if="!form.role_id && !showRoleDropdown" 
                        class="text-[10px] text-amber-500 font-bold mt-2 ml-1 flex items-center gap-1.5 animate-pulse">
                            <i class="fa-solid fa-circle-info text-[10px]"></i>
                            <span>Selection required: Assign a system role to define access levels.</span>
                        </p>
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

                    <div class="relative flex flex-col">
                        <div class="relative">
                            <input type="password" id="u_pw" v-model="form.password" placeholder=" "
                                class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                            <label for="u_pw" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">
                                {{ isEditing ? 'New Password' : 'Account Password' }}
                            </label>
                        </div>
                    </div>

                    <div class="relative flex flex-col">
                        <div class="relative">
                            <input type="password" id="u_pwc" v-model="form.password_confirmation" placeholder=" "
                                :class="[!passwordsMatch ? 'border-amber-400 focus:border-amber-500 focus:ring-amber-500/10' : 'border-gray-200']"
                                class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border rounded-xl focus:ring-2 outline-none peer appearance-none transition-all" />
                            <label for="u_pwc" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Confirm Password</label>
                            
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                <i v-if="form.password_confirmation && !passwordsMatch" class="fa-solid fa-triangle-exclamation text-amber-500 text-xs"></i>
                                <i v-else-if="form.password_confirmation && passwordsMatch" class="fa-solid fa-circle-check text-emerald-500 text-xs"></i>
                            </div>
                        </div>

                        <p v-if="!passwordsMatch" 
                        class="text-[10px] text-amber-500 font-bold mt-2 ml-1 flex items-center gap-1.5 animate-pulse">
                            <i class="fa-solid fa-lock-open text-[10px]"></i>
                            <span>Security check: Passwords do not match.</span>
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <div class="bg-indigo-50/50 border border-indigo-100/50 rounded-xl p-4 flex items-start gap-3 transition-all">
                            <div class="bg-white h-6 w-6 rounded-lg flex items-center justify-center shadow-sm shrink-0">
                                <i class="fa-solid fa-shield-halved text-indigo-500 text-[10px]"></i>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <p class="text-[10px] font-bold text-indigo-900 uppercase tracking-widest">Security Protocol</p>
                                <p class="text-[11px] text-indigo-600/80 leading-relaxed font-medium">
                                    {{ isEditing 
                                        ? 'Authentication persistent: Leave password fields empty to maintain current security credentials.' 
                                        : 'Initial access: The user will be required to utilize these credentials for their primary authorization.' 
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button @click="$emit('close')" 
                    class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                    Discard
                </button>
                <button 
                    @click="handleSubmit" 
                    :disabled="loading || (isEditing ? false : !form.role_id) || !passwordsMatch"
                    class="bg-indigo-600 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 active:scale-95 disabled:opacity-50 disabled:active:scale-100 transition-all flex items-center gap-2"
                >
                    <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
                    {{ isEditing ? 'Save Changes' : 'Confirm Registration' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.floating-input:focus ~ .floating-label,
.floating-input:not(:placeholder-shown) ~ .floating-label {
    top: 0.5rem;
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #6366f1;
}

.floating-label {
    top: 1.15rem;
}

::-webkit-scrollbar {
    width: 4px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>