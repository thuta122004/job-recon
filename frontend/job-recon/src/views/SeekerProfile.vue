<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from "vue-toastification";
import api from '@/services/api';

const toast = useToast();
const loading = ref(true);
const saving = ref(false);

const profile = ref({
    id: null,
    headline: '',
    summary: '',
    location: '',
    current_position: '',
    experience_years: 0,
    profile_visibility: 'PUBLIC',
    profile_picture_url: null,
    resume_url: null
});

const files = ref({
    profile_picture: null,
    resume: null
});

const previewImage = ref(null);

const fetchProfile = async () => {
    try {
        const userId = localStorage.getItem('user_id'); 
        
        if (!userId) {
            console.error("No user_id found in storage");
            return;
        }

        const response = await api.get(`/seeker/my-profile/${userId}`);
        
        if (response.data) {
            const data = response.data.data || response.data;
            profile.value = { ...profile.value, ...data };
            previewImage.value = data.profile_picture_url;
        }
    } catch (error) {
        if (error.response?.status !== 404) {
            toast.error("Failed to load profile data");
        }
    } finally {
        loading.value = false;
    }
};

const handleFileChange = (e, type) => {
    const file = e.target.files[0];
    if (!file) return;
    files.value[type] = file;

    if (type === 'profile_picture') {
        const reader = new FileReader();
        reader.onload = (e) => previewImage.value = e.target.result;
        reader.readAsDataURL(file);
    }
};

const updateProfile = async () => {
    saving.value = true;
    const formData = new FormData();
    const userId = localStorage.getItem('user_id');
    if (profile.value.id) {
        formData.append('_method', 'PUT');
    }

    formData.append('user_id', userId); 
    formData.append('headline', profile.value.headline || '');
    formData.append('summary', profile.value.summary || '');
    formData.append('location', profile.value.location || '');
    formData.append('current_position', profile.value.current_position || '');
    formData.append('experience_years', profile.value.experience_years || 0);
    formData.append('profile_visibility', profile.value.profile_visibility);

    if (files.value.profile_picture) {
        formData.append('profile_picture', files.value.profile_picture);
    }
    if (files.value.resume) {
        formData.append('resume', files.value.resume);
    }

    try {
        const url = profile.value.id 
            ? `/job-seeker-profiles/${profile.value.id}` 
            : `/job-seeker-profiles`;

        const response = await api.post(url, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        const result = response.data.data || response.data;
        
        if (result) {
            toast.success("Profile synchronized successfully");
            profile.value = { ...profile.value, ...result };
            if (result.profile_picture_url) {
                localStorage.setItem('user_profile_pic', result.profile_picture_url);
            }
        }
    } catch (error) {
        const message = error.response?.data?.errors 
            ? Object.values(error.response.data.errors)[0][0] 
            : "Update failed";
        toast.error(message);
    } finally {
        saving.value = false;
    }
};

const isPdf = (url) => {
    return url && url.toLowerCase().endsWith('.pdf');
};

const isWord = (url) => {
    return url && (url.toLowerCase().endsWith('.doc') || url.toLowerCase().endsWith('.docx'));
};

onMounted(fetchProfile);
</script>

<template>
    <div class="max-w-5xl mx-auto py-12 px-6">
        <div v-if="loading" class="flex items-center justify-center py-40">
            <div class="flex flex-col items-center gap-4">
                <div class="h-10 w-10 border-[4px] border-slate-100 border-t-indigo-600 rounded-full animate-spin"></div>
                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Syncing with server...</span>
            </div>
        </div>

        <div v-else class="space-y-12 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <header class="flex flex-col md:flex-row md:items-end justify-between gap-8 border-b border-gray-100 pb-10">
                <div class="text-left">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Personal Identity</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage your professional persona and visibility across the network.</p>
                </div>
                
                <div class="flex flex-col items-start md:items-end gap-3">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mr-2">Profile Visibility</span>
                    <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl border border-gray-200">
                        <button 
                            @click="profile.profile_visibility = 'PUBLIC'"
                            :class="profile.profile_visibility === 'PUBLIC' ? 'bg-white text-indigo-600 shadow-sm border-gray-200' : 'text-gray-500'"
                            class="px-5 py-2 text-[10px] font-bold uppercase tracking-widest rounded-lg border border-transparent transition-all">
                            Public
                        </button>
                        <button 
                            @click="profile.profile_visibility = 'PRIVATE'"
                            :class="profile.profile_visibility === 'PRIVATE' ? 'bg-white text-rose-500 shadow-sm border-gray-200' : 'text-gray-500'"
                            class="px-5 py-2 text-[10px] font-bold uppercase tracking-widest rounded-lg border border-transparent transition-all">
                            Private
                        </button>
                    </div>
                </div>
            </header>

            <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white border border-gray-100 rounded-[2rem] p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4 mb-4 text-left">
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="fa-solid fa-graduation-cap"></i></div>
                        <div>
                            <h4 class="text-xs font-black text-gray-900 uppercase tracking-wider">Qualifications</h4>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-0.5">Education History</p>
                        </div>
                    </div>
                    <router-link to="/seeker/education" class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl transition-all flex items-center justify-center gap-2 group shadow-lg shadow-emerald-100">
                        Manage Education <i class="fa-solid fa-arrow-right text-[9px] group-hover:translate-x-1 transition-transform"></i>
                    </router-link>
                </div>

                <div class="bg-white border border-gray-100 rounded-[2rem] p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4 mb-4 text-left">
                        <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="fa-solid fa-briefcase"></i></div>
                        <div>
                            <h4 class="text-xs font-black text-gray-900 uppercase tracking-wider">Work History</h4>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-0.5">Professional Experience</p>
                        </div>
                    </div>
                    <router-link to="/seeker/experience" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl transition-all flex items-center justify-center gap-2 group shadow-lg shadow-indigo-100">
                        Manage Experience <i class="fa-solid fa-arrow-right text-[9px] group-hover:translate-x-1 transition-transform"></i>
                    </router-link>
                </div>

                <div class="bg-white border border-gray-100 rounded-[2rem] p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4 mb-4 text-left">
                        <div class="h-10 w-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center"><i class="fa-solid fa-layer-group"></i></div>
                        <div>
                            <h4 class="text-xs font-black text-gray-900 uppercase tracking-wider">Competency Stack</h4>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-0.5">Skills & Expertise</p>
                        </div>
                    </div>
                    <router-link to="/seeker/skill" class="w-full py-4 bg-orange-600 hover:bg-orange-700 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl transition-all flex items-center justify-center gap-2 group shadow-lg shadow-orange-100">
                        Manage Skills <i class="fa-solid fa-arrow-right text-[9px] group-hover:translate-x-1 transition-transform"></i>
                    </router-link>
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                <div class="lg:col-span-4 space-y-6 sticky top-8">
                    <div class="bg-white border border-gray-100 rounded-[2rem] p-8 shadow-sm text-center">
                        <div class="group relative h-32 w-32 mx-auto mb-6">
                            <div class="h-full w-full rounded-3xl overflow-hidden bg-gray-50 border-2 border-dashed border-gray-200 transition-all group-hover:border-indigo-400">
                                <img v-if="previewImage" :src="previewImage" class="h-full w-full object-cover" />
                                <div v-else class="h-full w-full flex items-center justify-center text-gray-300">
                                    <i class="fa-solid fa-user text-3xl"></i>
                                </div>
                            </div>
                            <input type="file" @change="e => handleFileChange(e, 'profile_picture')" class="absolute inset-0 opacity-0 cursor-pointer z-10" accept="image/*" />
                            <div class="absolute -bottom-2 -right-2 h-8 w-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center shadow-lg">
                                <i class="fa-solid fa-camera text-[10px]"></i>
                            </div>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900">Profile Photo</h4>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">PNG or JPG up to 2MB</p>
                    </div>

                    <div class="bg-white border border-gray-100 rounded-[2rem] p-8 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.2em]">Resume Sync</h4>
                            
                            <div v-if="profile.resume_url" class="flex gap-2">
                                <a v-if="isPdf(profile.resume_url)"
                                :href="profile.resume_url" 
                                target="_blank"
                                class="h-8 w-8 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center hover:bg-emerald-100 transition-colors shadow-sm"
                                title="View PDF">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </a>

                                <a v-else-if="isWord(profile.resume_url)"
                                :href="profile.resume_url" 
                                download
                                class="h-8 w-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-100 transition-colors shadow-sm"
                                title="Download Word Document">
                                    <i class="fa-solid fa-download text-xs"></i>
                                </a>
                            </div>
                        </div>

                        <div class="relative group border-2 border-dashed border-gray-100 rounded-2xl p-6 hover:bg-gray-50 hover:border-indigo-200 transition-all cursor-pointer">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 truncate">
                                        {{ files.resume?.name || (profile.resume_url ? 'Active_Resume.pdf' : 'Not Uploaded') }}
                                    </p>
                                    <p class="text-[10px] text-gray-400 font-medium">Click to replace file</p>
                                </div>
                            </div>
                            <input type="file" @change="e => handleFileChange(e, 'resume')" class="absolute inset-0 opacity-0 cursor-pointer" />
                        </div>
                        
                        <div class="mt-4 py-3 px-4 bg-gray-50 rounded-xl border border-gray-100">
                             <div class="flex items-center gap-2">
                                <div class="h-1.5 w-1.5 rounded-full" :class="profile.resume_url ? 'bg-emerald-500' : 'bg-gray-300'"></div>
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-tight">
                                    {{ profile.resume_url ? 'Verified & Searchable' : 'Not yet uploaded' }}
                                </span>
                             </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-8 bg-white border border-gray-100 rounded-[2.5rem] p-10 shadow-sm space-y-8">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-left">
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Current Position</label>
                            <input v-model="profile.current_position" type="text" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Experience (Years)</label>
                            <input v-model="profile.experience_years" type="number" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none" />
                        </div>
                    </div>

                    <div class="space-y-2 text-left">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Professional Headline</label>
                        <input v-model="profile.headline" type="text" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none" />
                    </div>

                    <div class="space-y-2 text-left">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Location</label>
                        <input v-model="profile.location" type="text" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none" />
                    </div>

                    <div class="space-y-2 text-left">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Professional Summary</label>
                        <textarea v-model="profile.summary" rows="5" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none resize-none"></textarea>
                    </div>

                    <div class="pt-4">
                        <button 
                            @click="updateProfile"
                            :disabled="saving"
                            class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-2xl shadow-lg shadow-indigo-100 transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                            <i v-if="saving" class="fa-solid fa-circle-notch fa-spin"></i>
                            {{ saving ? 'Updating Directory...' : 'Save Profile Changes' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
