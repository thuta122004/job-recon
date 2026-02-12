<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from "vue-toastification";
import api from '@/services/api';

const toast = useToast();
const loading = ref(true);
const saving = ref(false);
const specialtyInput = ref('');
const previewImage = ref(null);

const profile = ref({
    id: null,
    company_name: '',
    company_logo_url: null,
    tagline: '',
    about_us: '',
    website_url: '',
    industry: '',
    headquarters_location: '',
    company_size: '',
    founded_year: null,
    specialties: [],
    linkedin_url: '',
    is_verified: false,
    delete_logo: false
});

const files = ref({
    company_logo: null
});

const fetchProfile = async () => {
    try {
        const userId = localStorage.getItem('user_id'); 
        if (!userId) return;

        const response = await api.get(`/employer/profile/${userId}`);
        
        if (response.data && response.data.status) {
            const data = response.data.data;
            
            let specs = data.specialties;
            if (typeof specs === 'string') {
                try { 
                    specs = JSON.parse(specs); 
                } catch (e) { 
                    specs = specs.split(',').filter(Boolean); 
                }
            }

            profile.value = { 
                ...profile.value, 
                ...data,
                specialties: Array.isArray(specs) ? specs : [],
                delete_logo: false
            };
            previewImage.value = data.company_logo_url;
        }
    } catch (error) {
        if (error.response?.status !== 404) {
            toast.error("Failed to load company profile");
        }
    } finally {
        loading.value = false;
    }
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    files.value.company_logo = file;
    profile.value.delete_logo = false;

    const reader = new FileReader();
    reader.onload = (e) => previewImage.value = e.target.result;
    reader.readAsDataURL(file);
};

const removeLogo = () => {
    files.value.company_logo = null;
    previewImage.value = null;
    if (profile.value.id) {
        profile.value.delete_logo = true;
    }
};

const addSpecialty = () => {
    const val = specialtyInput.value.trim();
    if (val && !profile.value.specialties.includes(val)) {
        profile.value.specialties.push(val);
        specialtyInput.value = '';
    }
};

const removeSpecialty = (index) => {
    profile.value.specialties.splice(index, 1);
};

const updateProfile = async () => {
    if (!profile.value.company_name) {
        toast.error("Company name is required");
        return;
    }

    saving.value = true;
    const formData = new FormData();
    const userId = localStorage.getItem('user_id');

    if (profile.value.id) {
        formData.append('_method', 'PUT');
    }

    formData.append('user_id', userId); 
    formData.append('company_name', profile.value.company_name || '');
    formData.append('tagline', profile.value.tagline || '');
    formData.append('about_us', profile.value.about_us || '');
    formData.append('website_url', profile.value.website_url || '');
    formData.append('industry', profile.value.industry || '');
    formData.append('headquarters_location', profile.value.headquarters_location || '');
    formData.append('company_size', profile.value.company_size || '');
    formData.append('founded_year', profile.value.founded_year || '');
    formData.append('specialties', JSON.stringify(profile.value.specialties));
    formData.append('linkedin_url', profile.value.linkedin_url || '');
    formData.append('delete_logo', profile.value.delete_logo ? '1' : '0');

    if (files.value.company_logo) {
        formData.append('company_logo', files.value.company_logo);
    }

    try {
        const url = profile.value.id 
            ? `/employer-profiles/${profile.value.id}` 
            : `/employer-profiles`;

        const response = await api.post(url, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        toast.success("Company profile synchronized");
        fetchProfile(); 
    } catch (error) {
        toast.error(error.response?.data?.message || "Update failed");
    } finally {
        saving.value = false;
    }
};

onMounted(fetchProfile);
</script>

<template>
    <div class="max-w-5xl mx-auto py-12 px-6">
        <div v-if="loading" class="flex items-center justify-center py-40">
            <div class="flex flex-col items-center gap-4">
                <div class="h-10 w-10 border-[4px] border-slate-100 border-t-indigo-600 rounded-full animate-spin"></div>
                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Loading Company Data...</span>
            </div>
        </div>

        <div v-else class="space-y-12 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <header class="flex flex-col md:flex-row md:items-end justify-between gap-8 border-b border-gray-100 pb-10">
                <div class="text-left">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Company Profile</h1>
                    <p class="text-sm text-gray-500 mt-1">Establish your brand identity to attract top talent.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div v-if="profile.is_verified" class="flex items-center gap-2 bg-emerald-50 px-4 py-2 rounded-xl border border-emerald-100">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-xs"></i>
                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Verified Employer</span>
                    </div>
                    <div v-else class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-xl border border-gray-100">
                        <i class="fa-solid fa-clock text-gray-400 text-xs"></i>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pending Verification</span>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                <div class="lg:col-span-4 space-y-6 sticky top-8">
                    <div class="bg-white border border-gray-100 rounded-[2rem] p-8 shadow-sm text-center">
                        <div class="group relative h-32 w-32 mx-auto mb-6">
                            <div class="h-full w-full rounded-3xl overflow-hidden bg-gray-50 border-2 border-dashed border-gray-200 transition-all group-hover:border-indigo-400 p-2">
                                <img v-if="previewImage" :src="previewImage" class="h-full w-full object-contain" :class="{ 'opacity-40 grayscale': profile.delete_logo }" />
                                <div v-else class="h-full w-full flex items-center justify-center text-gray-300">
                                    <i class="fa-solid fa-user-check text-3xl"></i>
                                </div>
                            </div>
                            <input type="file" @change="handleFileChange" class="absolute inset-0 opacity-0 cursor-pointer z-10" accept="image/*" />
                            
                            <button v-if="previewImage && !profile.delete_logo" @click.stop="removeLogo" type="button" class="absolute -top-2 -right-2 h-7 w-7 bg-white text-red-500 border border-red-100 rounded-full flex items-center justify-center shadow-md z-20 hover:bg-red-50 transition-all">
                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                            </button>

                            <button v-if="profile.delete_logo" @click.stop="profile.delete_logo = false; previewImage = profile.company_logo_url" type="button" class="absolute -top-2 -right-2 h-7 w-7 bg-amber-500 text-white rounded-full flex items-center justify-center shadow-md z-20">
                                <i class="fa-solid fa-rotate-left text-[10px]"></i>
                            </button>

                            <div class="absolute -bottom-2 -right-2 h-8 w-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center shadow-lg">
                                <i class="fa-solid fa-camera text-[10px]"></i>
                            </div>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900">{{ profile.delete_logo ? 'Removal Pending' : 'Company Logo' }}</h4>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Square SVG, PNG or JPG</p>
                    </div>

                    <div class="bg-white border border-gray-100 rounded-[2rem] p-8 shadow-sm space-y-6">
                        <div class="text-left space-y-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Quick Stats</label>
                            <div class="space-y-4 pt-2">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400">
                                        <i class="fa-solid fa-users text-xs"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase">Size</p>
                                        <p class="text-xs font-bold text-gray-700">{{ profile.company_size || 'Not set' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400">
                                        <i class="fa-solid fa-calendar text-xs"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase">Founded</p>
                                        <p class="text-xs font-bold text-gray-700">{{ profile.founded_year || 'Not set' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-8 bg-white border border-gray-100 rounded-[2.5rem] p-10 shadow-sm space-y-8">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-left">
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Company Name</label>
                            <input v-model="profile.company_name" type="text" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none" placeholder="e.g. Acme Corp" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Industry</label>
                            <input v-model="profile.industry" type="text" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none" placeholder="e.g. Technology" />
                        </div>
                    </div>

                    <div class="space-y-2 text-left">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Tagline</label>
                        <input v-model="profile.tagline" type="text" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none" placeholder="A short, catchy phrase about your company" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-left">
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Website URL</label>
                            <input v-model="profile.website_url" type="url" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none" placeholder="https://..." />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">LinkedIn URL</label>
                            <input v-model="profile.linkedin_url" type="url" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none" placeholder="linkedin.com/company/..." />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-left">
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Headquarters</label>
                            <input v-model="profile.headquarters_location" type="text" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Company Size</label>
                            <div class="relative">
                                <select v-model="profile.company_size" 
                                    class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 transition-all outline-none appearance-none cursor-pointer">
                                    <option value="" disabled>Select Size</option>
                                    <option value="1-10">1-10</option>
                                    <option value="11-50">11-50</option>
                                    <option value="51-200">51-200</option>
                                    <option value="201-500">201-500</option>
                                    <option value="500+">500+</option>
                                </select>
                                <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Founded Year</label>
                            <input v-model="profile.founded_year" type="number" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none" />
                        </div>
                    </div>

                    <div class="space-y-3 text-left">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">Company Specialties</label>
                        <div class="flex flex-wrap gap-2 p-4 bg-gray-50 border border-gray-100 rounded-2xl focus-within:bg-white focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/5 transition-all">
                            <span v-for="(tag, idx) in profile.specialties" :key="idx" 
                                class="bg-indigo-600 text-white px-3 py-1.5 rounded-xl text-[10px] font-black flex items-center gap-2 shadow-sm uppercase tracking-wider">
                                {{ tag }}
                                <button type="button" @click="removeSpecialty(idx)" class="hover:text-indigo-200 transition-colors">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </span>
                            <input 
                                v-model="specialtyInput" 
                                @keydown.enter.prevent="addSpecialty" 
                                placeholder="Add specialty and press Enter..." 
                                class="bg-transparent border-none outline-none text-sm font-medium flex-1 min-w-[200px]" 
                            />
                        </div>
                    </div>

                    <div class="space-y-2 text-left">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider ml-1">About Us</label>
                        <textarea v-model="profile.about_us" rows="6" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none resize-none" placeholder="Describe your company culture, mission, and what it's like to work with you..."></textarea>
                    </div>

                    <div class="pt-4">
                        <button 
                            @click="updateProfile"
                            :disabled="saving"
                            class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-2xl shadow-lg shadow-indigo-100 transition-all flex items-center justify-center gap-2 disabled:opacity-50 active:scale-[0.98]">
                            <i v-if="saving" class="fa-solid fa-circle-notch fa-spin"></i>
                            {{ saving ? 'Syncing Brand Data...' : 'Save Company Profile' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>