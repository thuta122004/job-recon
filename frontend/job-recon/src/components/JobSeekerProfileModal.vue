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
    resume: null
});

const fileName = ref({
    picture: '',
    resume: ''
});

const imagePreview = ref(null);

const activeUsers = computed(() => {
    const profilesList = Array.isArray(props.profiles) 
        ? props.profiles 
        : (props.profiles?.data || []);

    const linkedUserIds = new Set(profilesList.map(p => Number(p.user_id)));

    return props.users?.filter(user => {
        const isActive = user.status === 'ACTIVE';
        const isJobSeeker = user.role?.id === 2;
        const userId = Number(user.id);
        
        if (props.isEditing && userId === Number(props.profileData?.user_id)) {
            return true;
        }

        return isActive && isJobSeeker && !linkedUserIds.has(userId);
    }) ?? [];
});

const handleFileChange = (event, type) => {
    const file = event.target.files[0];
    if (file) {
        if (type === 'picture') {
            form.profile_picture = file;
            fileName.value.picture = file.name;
            imagePreview.value = URL.createObjectURL(file);
        } else {
            form.resume = file;
            fileName.value.resume = file.name;
        }
    }
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (!props.isEditing) {
            Object.assign(form, {
                id: null, user_id: '', headline: '', summary: '',
                location: '', current_position: '', experience_years: null,
                profile_visibility: 'PUBLIC', profile_picture: null, resume: null
            });
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
                resume: null
            });
            fileName.value = { 
                picture: props.profileData.profile_picture_url ? 'Current Image Stored' : '', 
                resume: props.profileData.resume_url ? 'Current Resume Stored' : '' 
            };
            imagePreview.value = props.profileData.profile_picture_url || null;
        }
    }
});

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
                    <p class="text-xs text-gray-500">Configure how this candidate appears to potential employers.</p>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 p-2 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-8 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-7">
                    
                    <div class="relative md:col-span-2">
                        <select id="p_user" v-model="form.user_id" :disabled="isEditing"
                            class="block w-full px-4 py-4 text-sm text-gray-600 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none appearance-none transition-all disabled:bg-gray-50 disabled:text-gray-400">
                            <option value="" selected disabled>
                                {{ activeUsers.length > 0 ? 'Link to User Account' : 'No active Job Seekers available' }}
                            </option>
                            <option v-for="user in activeUsers" :key="user.id" :value="user.id">
                                {{ user.first_name }} {{ user.last_name }} ({{ user.email }})
                            </option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                            <i class="fa-solid fa-link text-xs"></i>
                        </div>
                    </div>

                    <div class="relative md:col-span-2">
                        <input type="text" id="p_headline" v-model="form.headline" placeholder=" "
                            class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="p_headline" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Professional Headline (e.g. Senior Instructor)</label>
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
                            <input type="file" @change="e => handleFileChange(e, 'picture')" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" />
                            <div class="bg-indigo-50 h-12 w-12 rounded-xl flex items-center justify-center text-indigo-500 overflow-hidden shrink-0 border border-indigo-100 shadow-inner">
                                <img v-if="imagePreview" :src="imagePreview" class="h-full w-full object-cover" />
                                <i v-else class="fa-solid fa-camera text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold text-gray-700 truncate">{{ fileName.picture || 'Upload Photo' }}</p>
                                <p class="text-[9px] text-gray-400 uppercase tracking-tighter">Square PNG/JPG</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Curriculum Vitae</label>
                        <div class="relative border-2 border-dashed border-gray-100 rounded-xl p-3 hover:border-emerald-200 transition-colors flex items-center gap-4 group">
                            <input type="file" @change="e => handleFileChange(e, 'resume')" accept=".pdf,.doc,.docx" class="absolute inset-0 opacity-0 cursor-pointer" />
                            <div class="bg-emerald-50 h-12 w-12 rounded-xl flex items-center justify-center text-emerald-500 border border-emerald-100 shadow-inner">
                                <i class="fa-solid fa-file-contract text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold text-gray-700 truncate">{{ fileName.resume || 'Attach Resume' }}</p>
                                <p class="text-[9px] text-gray-400 uppercase tracking-tighter">PDF or Word Doc</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button @click="$emit('close')" 
                    class="px-5 py-2.5 text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors">
                    Discard
                </button>
                <button @click="handleSubmit" :disabled="loading"
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
</style>