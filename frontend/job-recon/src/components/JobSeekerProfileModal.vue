<script setup>
import { computed, reactive, watch, ref } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    profileData: Object,
    users: Array,
    profiles: Array,
    isEditing: Boolean,
    loading: Boolean
});

const emit = defineEmits(['close', 'save']);

const userSearch = ref('');
const showUserDropdown = ref(false);

const form = reactive({
    id: null,
    user_id: '',
    headline: '',
    summary: '',
    location: '',
    current_position: '',
    experience_years: null,
    profile_visibility: 'PUBLIC',
    profile_picture: null,
    resume: null,
    delete_picture: false,
    delete_resume: false
});

const fileName = ref({
    picture: '',
    resume: ''
});

const imagePreview = ref(null);

const filteredUsers = computed(() => {
    const query = userSearch.value.toLowerCase().trim();
    if (!query) return activeUsers.value;
    return activeUsers.value.filter(user => 
        `${user.first_name} ${user.last_name}`.toLowerCase().includes(query) ||
        user.email.toLowerCase().includes(query)
    );
});

const selectUser = (user) => {
    form.user_id = user.id;
    userSearch.value = `${user.first_name} ${user.last_name}`;
    showUserDropdown.value = false;
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (!props.isEditing) {
            Object.assign(form, {
                id: null, user_id: '', headline: '', summary: '',
                location: '', current_position: '', experience_years: null,
                profile_visibility: 'PUBLIC', profile_picture: null, resume: null,
                delete_picture: false, delete_resume: false
            });
            userSearch.value = '';
            fileName.value = { picture: '', resume: '' };
            imagePreview.value = null;
        } else if (props.profileData) {
            Object.assign(form, {
                id: props.profileData.id,
                user_id: props.profileData.user_id,
                headline: props.profileData.headline || '',
                summary: props.profileData.summary || '',
                location: props.profileData.location || '',
                current_position: props.profileData.current_position || '',
                experience_years: props.profileData.experience_years,
                profile_visibility: props.profileData.profile_visibility || 'PUBLIC',
                profile_picture: null,
                resume: null,
                delete_picture: false,
                delete_resume: false
            });

            const user = props.users?.find(u => u.id == props.profileData.user_id);
            userSearch.value = user ? `${user.first_name} ${user.last_name}` : '';
            
            fileName.value = { 
                picture: props.profileData.profile_picture_url ? 'Current Image Stored' : '', 
                resume: props.profileData.resume_url ? 'Current Resume Stored' : '' 
            };
            imagePreview.value = props.profileData.profile_picture_url || null;
        }
    }
    showUserDropdown.value = false;
});

const activeUsers = computed(() => {
    const profilesList = Array.isArray(props.profiles) ? props.profiles : (props.profiles?.data || []);
    const linkedUserIds = new Set(profilesList.map(p => Number(p.user_id)));

    return props.users?.filter(user => {
        const isActive = user.status === 'ACTIVE';
        const isJobSeeker = user.role?.id === 2;
        const userId = Number(user.id);
        if (props.isEditing && userId === Number(props.profileData?.user_id)) return true;
        return isActive && isJobSeeker && !linkedUserIds.has(userId);
    }) ?? [];
});

const handleFileChange = (event, type) => {
    const file = event.target.files[0];
    if (file) {
        if (type === 'picture') {
            form.profile_picture = file;
            form.delete_picture = false;
            fileName.value.picture = file.name;
            imagePreview.value = URL.createObjectURL(file);
        } else {
            form.resume = file;
            form.delete_resume = false;
            fileName.value.resume = file.name;
        }
    }
};

const resumeIcon = computed(() => {
    if (form.delete_resume) {
        return { icon: 'fa-file-circle-xmark', colorClass: 'bg-red-50 text-red-500 border-red-100', label: 'Removal Pending' };
    }

    const fileNameToCheck = (form.resume?.name || props.profileData?.resume_url || '').toLowerCase();
    
    if (fileNameToCheck.endsWith('.pdf')) {
        return { icon: 'fa-file-pdf', colorClass: 'bg-red-50 text-red-600 border-red-100', label: 'PDF Document' };
    } 
    if (fileNameToCheck.endsWith('.doc') || fileNameToCheck.endsWith('.docx')) {
        return { icon: 'fa-file-word', colorClass: 'bg-blue-50 text-blue-600 border-blue-100', label: 'Word Document' };
    }
    if (!fileName.value.resume) {
        return { icon: 'fa-file-contract', colorClass: 'bg-emerald-50 text-emerald-500 border-emerald-100', label: 'No File Attached' };
    }

    return { icon: 'fa-file-lines', colorClass: 'bg-indigo-50 text-indigo-500 border-indigo-100', label: 'Stored Document' };
});
const clearFile = (type) => {
    if (type === 'picture') {
        if (form.profile_picture) {
            form.profile_picture = null;
            fileName.value.picture = (props.isEditing && props.profileData?.profile_picture_url) ? 'Current Image Stored' : '';
            imagePreview.value = props.isEditing ? props.profileData?.profile_picture_url : null;
            form.delete_picture = false;
        } else if (props.isEditing && props.profileData?.profile_picture_url) {
            form.delete_picture = true;
            fileName.value.picture = ''; 
        }
    } else {
        if (form.resume) {
            form.resume = null;
            fileName.value.resume = (props.isEditing && props.profileData?.resume_url) ? 'Current Resume Stored' : '';
            form.delete_resume = false;
        } else if (props.isEditing && props.profileData?.resume_url) {
            form.delete_resume = true;
            fileName.value.resume = '';
        }
    }
};

const handleSubmit = () => {
    emit('save', { ...form });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="$emit('close')" class="absolute inset-0 bg-indigo-900/20 backdrop-blur-sm transition-opacity"></div>
        
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl relative z-10 overflow-hidden border border-gray-100 max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">{{ isEditing ? 'Edit Professional Profile' : 'New Talent Profile' }}</h3>
                    <p class="text-xs text-gray-500">Centralized repository for candidate identities and portfolios.</p>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 p-2 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-8 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-7">
                    
                    <div class="relative md:col-span-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2 block">
                            Candidate Account
                        </label>
                        
                        <div class="relative">
                            <div class="relative group">
                                <input 
                                    type="text"
                                    v-model="userSearch"
                                    @focus="showUserDropdown = true"
                                    @blur="setTimeout(() => showUserDropdown = false, 200)"
                                    :disabled="isEditing"
                                    placeholder="Search by name or email..."
                                    class="block w-full px-4 py-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all disabled:bg-gray-50 disabled:text-gray-400"
                                />
                                
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2 pointer-events-none">
                                    <i v-if="!userSearch" class="fa-solid fa-magnifying-glass text-xs text-gray-300"></i>
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
                                <div v-if="showUserDropdown && !isEditing" 
                                    class="absolute z-[60] w-full mt-2 bg-white border border-gray-100 shadow-2xl rounded-2xl overflow-hidden">
                                    
                                    <div class="max-h-64 overflow-y-auto p-2 space-y-1">
                                        <div v-if="filteredUsers.length === 0" class="p-4 text-center">
                                            <i class="fa-solid fa-user-slash text-gray-200 text-xl mb-2"></i>
                                            <p class="text-xs text-gray-400 font-medium">No available candidates found</p>
                                        </div>

                                        <button 
                                            v-for="user in filteredUsers" 
                                            :key="user.id"
                                            type="button"
                                            @mousedown="selectUser(user)"
                                            class="w-full text-left px-4 py-3 rounded-xl hover:bg-indigo-50 transition-all flex items-center justify-between group"
                                        >
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-700 group-hover:text-indigo-600">
                                                    {{ user.first_name }} {{ user.last_name }}
                                                </span>
                                                <span class="text-[11px] text-gray-400">{{ user.email }}</span>
                                            </div>
                                            <i class="fa-solid fa-plus text-[10px] text-gray-300 group-hover:text-indigo-400"></i>
                                        </button>
                                    </div>
                                </div>
                            </transition>
                        </div>
                        <p v-if="!form.user_id && !isEditing && !showUserDropdown" 
                        class="text-[10px] text-amber-500 font-bold mt-2 ml-1 flex items-center gap-1.5 animate-pulse">
                            <i class="fa-solid fa-circle-info text-[10px]"></i>
                            <span>Selection required: Link an active account to create this profile.</span>
                        </p>
                    </div>

                    <div class="relative md:col-span-2">
                        <input type="text" id="p_headline" v-model="form.headline" placeholder=" "
                            class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="p_headline" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Professional Headline</label>
                    </div>

                    <div class="relative">
                        <input type="text" id="p_pos" v-model="form.current_position" placeholder=" "
                            class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="p_pos" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Current Position</label>
                    </div>

                    <div class="relative">
                        <input type="text" id="p_loc" v-model="form.location" placeholder=" "
                            class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="p_loc" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Primary Location</label>
                    </div>

                    <div class="relative h-[58px]">
                        <input type="number" id="p_exp" v-model="form.experience_years" placeholder=" "
                            class="floating-input block w-full h-full pl-4 pr-14 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        
                        <label for="p_exp" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">
                            Experience
                        </label>
                        <div class="absolute right-4 inset-y-0 flex items-center pointer-events-none">
                            <span class="text-[10px] font-bold text-gray-300 peer-focus:text-indigo-400 uppercase tracking-tighter">
                                {{ form.experience_years == 1 ? 'Year' : 'Years' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Profile Visibility</label>
                        <div class="grid grid-cols-2 bg-gray-100 rounded-xl border border-gray-200/50">
                            <button @click="form.profile_visibility = 'PUBLIC'" 
                                :class="form.profile_visibility === 'PUBLIC' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                class="flex items-center justify-center gap-2 py-2 text-xs font-bold rounded-lg transition-all">
                                <i class="fa-solid fa-globe text-[10px]"></i> Public
                            </button>
                            <button @click="form.profile_visibility = 'PRIVATE'" 
                                :class="form.profile_visibility === 'PRIVATE' ? 'bg-white text-gray-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                class="flex items-center justify-center gap-2 py-2 text-xs font-bold rounded-lg transition-all">
                                <i class="fa-solid fa-lock text-[10px]"></i> Private
                            </button>
                        </div>
                    </div>

                    <div class="relative md:col-span-2">
                        <textarea id="p_sum" v-model="form.summary" rows="3" placeholder=" "
                            class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all resize-none"></textarea>
                        <label for="p_sum" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Professional Summary</label>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Avatar</label>
                        <div class="relative border-2 border-dashed border-gray-100 rounded-xl p-3 hover:border-indigo-200 transition-colors flex items-center gap-4 group">
                            <input type="file" @change="e => handleFileChange(e, 'picture')" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-10" />
                            
                            <div class="bg-indigo-50 h-12 w-12 rounded-xl flex items-center justify-center text-indigo-500 overflow-hidden shrink-0 border border-indigo-100 shadow-inner">
                                <img v-if="imagePreview" :src="imagePreview" 
                                    class="h-full w-full object-cover transition-all duration-300" 
                                    :class="{ 'grayscale opacity-40 blur-[1px]': form.delete_picture }" />
                                <i v-else class="fa-solid fa-camera text-sm"></i>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold truncate transition-colors"
                                :class="form.delete_picture ? 'text-red-500 italic' : 'text-gray-700'">
                                    {{ form.delete_picture ? 'Marked for deletion' : (fileName.picture || 'Upload Photo') }}
                                </p>
                                <p class="text-[9px] text-gray-400 uppercase tracking-tighter">
                                    {{ form.delete_picture ? 'Removal Pending' : 'Square PNG/JPG' }}
                                </p>
                            </div>

                            <button v-if="fileName.picture && !form.delete_picture" 
                                @click.stop="clearFile('picture')" 
                                type="button" 
                                class="relative z-20 h-8 w-8 rounded-lg flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>

                            <button v-if="form.delete_picture" 
                                @click.stop="form.delete_picture = false; fileName.picture = 'Current Image Stored'; imagePreview = props.profileData?.profile_picture_url" 
                                type="button" 
                                class="relative z-20 h-8 w-8 rounded-lg flex items-center justify-center text-amber-500 hover:bg-amber-50 transition-all">
                                <i class="fa-solid fa-rotate-left text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Curriculum Vitae</label>
                        <div class="relative border-2 border-dashed border-gray-100 rounded-xl p-3 hover:border-emerald-200 transition-colors flex items-center gap-4 group">
                            <input type="file" @change="e => handleFileChange(e, 'resume')" accept=".pdf,.doc,.docx" class="absolute inset-0 opacity-0 cursor-pointer z-10" />
                            
                            <div :class="[
                                'h-12 w-12 rounded-xl flex items-center justify-center border shadow-inner transition-colors',
                                resumeIcon.colorClass
                            ]">
                                <i :class="['fa-solid text-sm', resumeIcon.icon]"></i>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold truncate transition-colors" 
                                :class="form.delete_resume ? 'text-red-500 italic' : 'text-gray-700'">
                                    {{ form.delete_resume ? 'Marked for deletion' : (fileName.resume || 'Attach Resume') }}
                                </p>
                                <p class="text-[9px] text-gray-400 uppercase tracking-tighter">
                                    {{ resumeIcon.label }}
                                </p>
                            </div>

                            <button v-if="fileName.resume && !form.delete_resume" @click.stop="clearFile('resume')" type="button" 
                                class="relative z-20 h-8 w-8 rounded-lg flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                            
                            <button v-if="form.delete_resume" @click.stop="form.delete_resume = false; fileName.resume = 'Current Resume Stored'" type="button" 
                                class="relative z-20 h-8 w-8 rounded-lg flex items-center justify-center text-amber-500 hover:bg-amber-50 transition-all">
                                <i class="fa-solid fa-rotate-left text-xs"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button @click="$emit('close')" 
                    class="px-5 py-2.5 text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors">
                    Discard
                </button>
                <button @click="handleSubmit" :disabled="loading || (!form.user_id && !isEditing)"
                    class="bg-indigo-600 text-white px-10 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 active:scale-95 disabled:opacity-50 disabled:active:scale-100 transition-all flex items-center gap-2">
                    <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
                    {{ isEditing ? 'Save Changes' : 'Publish Profile' }}
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

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
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