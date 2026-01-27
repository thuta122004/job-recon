<script setup>
import { computed, reactive, watch, ref } from 'vue';
import { useToast } from "vue-toastification";

const props = defineProps({
    isOpen: Boolean,
    profileData: Object,
    users: Array,
    profiles: Array,
    isEditing: Boolean,
    loading: Boolean
});

const emit = defineEmits(['close', 'save']);
const toast = useToast();

const userSearch = ref('');
const showUserDropdown = ref(false);
const specialtyInput = ref('');
const imagePreview = ref(null);
const fileName = ref({ logo: '' });

const form = reactive({
    id: null,
    user_id: '',
    company_name: '',
    tagline: '',
    about_us: '',
    website_url: '',
    industry: '',
    headquarters_location: '',
    company_size: '',
    founded_year: null,
    specialties: [],
    linkedin_url: '',
    company_logo: null,
    delete_logo: false,
    is_verified: false
});

const revokePreview = () => {
    if (imagePreview.value && imagePreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(imagePreview.value);
    }
};

const activeUsers = computed(() => {
    const profilesList = Array.isArray(props.profiles) ? props.profiles : (props.profiles?.data || []);
    const linkedUserIds = new Set(profilesList.map(p => Number(p.user_id)));

    return props.users?.filter(user => {
        const isActive = user.status === 'ACTIVE';
        const isEmployer = user.role?.id === 3; 
        const userId = Number(user.id);
        if (props.isEditing && userId === Number(props.profileData?.user_id)) return true;
        return isActive && isEmployer && !linkedUserIds.has(userId);
    }) ?? [];
});

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

const addSpecialty = () => {
    const val = specialtyInput.value.trim();
    if (val && !form.specialties.includes(val)) {
        form.specialties.push(val);
        specialtyInput.value = '';
    }
};
const removeSpecialty = (index) => form.specialties.splice(index, 1);

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        revokePreview();
        if (!props.isEditing) {
            Object.assign(form, {
                id: null, user_id: '', company_name: '', tagline: '',
                about_us: '', website_url: '', industry: '',
                headquarters_location: '', company_size: '', founded_year: null,
                specialties: [], linkedin_url: '', company_logo: null,
                delete_logo: false, is_verified: false
            });
            userSearch.value = '';
            fileName.value = { logo: '' };
            imagePreview.value = null;
        } else if (props.profileData) {
            let specs = props.profileData.specialties;
            if (typeof specs === 'string') {
                try { specs = JSON.parse(specs); } 
                catch (e) { specs = specs.split(',').filter(Boolean); }
            }

            Object.assign(form, {
                id: props.profileData.id,
                user_id: props.profileData.user_id,
                company_name: props.profileData.company_name || '',
                tagline: props.profileData.tagline || '',
                about_us: props.profileData.about_us || '',
                website_url: props.profileData.website_url || '',
                industry: props.profileData.industry || '',
                headquarters_location: props.profileData.headquarters_location || '',
                company_size: props.profileData.company_size || '',
                founded_year: props.profileData.founded_year ? Number(props.profileData.founded_year) : null,
                specialties: Array.isArray(specs) ? [...specs] : [],
                linkedin_url: props.profileData.linkedin_url || '',
                company_logo: null,
                delete_logo: false,
                is_verified: props.profileData.is_verified || false
            });

            const user = props.users?.find(u => u.id == props.profileData.user_id);
            userSearch.value = user ? `${user.first_name} ${user.last_name}` : '';
            fileName.value.logo = props.profileData.company_logo_url ? 'Existing logo attached' : '';
            imagePreview.value = props.profileData.company_logo_url || null;
        }
    }
    showUserDropdown.value = false;
});

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        revokePreview();
        form.company_logo = file;
        form.delete_logo = false;
        fileName.value.logo = file.name;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const clearFile = () => {
    revokePreview();
    if (form.company_logo) {
        form.company_logo = null;
        fileName.value.logo = (props.isEditing && props.profileData?.company_logo_url) ? 'Existing logo attached' : '';
        imagePreview.value = props.isEditing ? props.profileData?.company_logo_url : null;
        form.delete_logo = false;
    } else if (props.isEditing && props.profileData?.company_logo_url) {
        form.delete_logo = true;
        fileName.value.logo = '';
    }
};

const handleSubmit = () => {
    if (!form.user_id) {
        toast.error("An employer account must be linked to create a profile.");
        return;
    }

    const payload = { 
        ...form, 
        founded_year: form.founded_year || null,
        specialties: [...form.specialties] 
    };
    
    emit('save', payload);
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="$emit('close')" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
        
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl relative z-10 overflow-hidden border border-gray-100 max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">{{ isEditing ? 'Edit Corporate Profile' : 'New Company Profile' }}</h3>
                    <p class="text-xs text-gray-500">Define the organization's public identity and branding.</p>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 p-2 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-8 overflow-y-auto custom-scrollbar">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-7">
                    
                    <div class="relative md:col-span-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2 block">Company Administrator</label>
                        <div class="relative">
                            <input type="text" v-model="userSearch" @focus="showUserDropdown = true" @blur="setTimeout(() => showUserDropdown = false, 200)"
                                :disabled="isEditing" placeholder="Search employer accounts by name or email..."
                                class="block w-full px-4 py-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all disabled:bg-gray-50 disabled:text-gray-400" />
                            
                            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
                                <div v-if="showUserDropdown && !isEditing" class="absolute z-[60] w-full mt-2 bg-white border border-gray-100 shadow-2xl rounded-2xl overflow-hidden">
                                    <div class="max-h-48 overflow-y-auto p-2 space-y-1">
                                        <div v-if="filteredUsers.length === 0" class="p-4 text-center">
                                            <i class="fa-solid fa-user-slash text-gray-200 text-xl mb-2"></i>
                                            <p class="text-xs text-gray-400 font-medium italic">No available employer accounts found</p>
                                        </div>
                                        <button v-for="user in filteredUsers" :key="user.id" type="button" @mousedown="selectUser(user)"
                                            class="w-full text-left px-4 py-3 rounded-xl hover:bg-indigo-50 transition-all flex flex-col group">
                                            <span class="text-sm font-bold text-gray-700 group-hover:text-indigo-600">{{ user.first_name }} {{ user.last_name }}</span>
                                            <span class="text-[11px] text-gray-400">{{ user.email }}</span>
                                        </button>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>

                    <div class="relative md:col-span-2">
                        <input type="text" id="c_name" v-model="form.company_name" placeholder=" " class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="c_name" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Legal Company Name</label>
                    </div>

                    <div class="relative md:col-span-2">
                        <input type="text" id="c_tagline" v-model="form.tagline" placeholder=" " class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="c_tagline" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Corporate Tagline</label>
                    </div>

                    <div class="relative">
                        <input type="text" id="c_ind" v-model="form.industry" placeholder=" " class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="c_ind" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Industry</label>
                    </div>

                    <div class="relative">
                        <input type="number" id="c_year" v-model.number="form.founded_year" placeholder=" " class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="c_year" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Founded Year</label>
                    </div>

                    <div class="relative">
                        <input type="text" id="c_hq" v-model="form.headquarters_location" placeholder=" " class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="c_hq" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Headquarters</label>
                    </div>

                    <div class="relative">
                        <select v-model="form.company_size" class="block w-full px-4 py-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all appearance-none">
                            <option value="" disabled>Company size</option>
                            <option value="1-10">1-10</option>
                            <option value="11-50">11-50</option>
                            <option value="51-200">51-200</option>
                            <option value="201-500">201-500</option>
                            <option value="501-1000">501-1,000</option>
                            <option value="1001+">1,001+</option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400"><i class="fa-solid fa-chevron-down text-[10px]"></i></div>
                    </div>

                    <div class="relative">
                        <input type="url" id="c_web" v-model="form.website_url" placeholder=" " class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="c_web" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Website URL</label>
                    </div>

                    <div class="relative">
                        <input type="url" id="c_linkedin" v-model="form.linkedin_url" placeholder=" " class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                        <label for="c_linkedin" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">LinkedIn URL</label>
                    </div>

                    <div class="relative md:col-span-2">
                        <textarea id="c_about" v-model="form.about_us" rows="3" placeholder=" " class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all resize-none"></textarea>
                        <label for="c_about" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">About Us / Overview</label>
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Company Specialties</label>
                        <div class="flex flex-wrap gap-2 p-3 bg-gray-50 border border-gray-200 rounded-xl focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-500 transition-all">
                            <span v-for="(tag, idx) in form.specialties" :key="idx" class="bg-indigo-600 text-white px-3 py-1 rounded-lg text-[11px] font-bold flex items-center gap-2 shadow-sm">
                                {{ tag }}
                                <button type="button" @click="removeSpecialty(idx)" class="hover:text-indigo-200 transition-colors"><i class="fa-solid fa-xmark"></i></button>
                            </span>
                            <input v-model="specialtyInput" @keydown.enter.prevent="addSpecialty" placeholder="Add specialty and press Enter..." class="bg-transparent border-none outline-none text-sm font-medium flex-1 min-w-[200px]" />
                        </div>
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Corporate Logo</label>
                        <div class="relative border-2 border-dashed border-gray-100 rounded-xl p-3 hover:border-indigo-200 transition-colors flex items-center gap-4 group">
                            <input type="file" @change="handleFileChange" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-10" />
                            <div class="bg-indigo-50 h-14 w-14 rounded-xl flex items-center justify-center text-indigo-500 overflow-hidden shrink-0 border border-indigo-100 shadow-inner">
                                <img v-if="imagePreview" :src="imagePreview" class="h-full w-full object-cover transition-all" :class="{ 'grayscale opacity-40 blur-[1px]': form.delete_logo }" />
                                <i v-else class="fa-solid fa-user-check text-lg"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold truncate transition-colors" :class="form.delete_logo ? 'text-red-500 italic' : 'text-gray-700'">{{ form.delete_logo ? 'Marked for deletion' : (fileName.logo || 'Upload Logo') }}</p>
                                <p class="text-[9px] text-gray-400 uppercase tracking-tighter">{{ form.delete_logo ? 'Removal Pending' : 'Square PNG/JPG recommended' }}</p>
                            </div>
                            <button v-if="fileName.logo && !form.delete_logo" @click.stop="clearFile" type="button" class="relative z-20 h-8 w-8 rounded-lg flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                            <button v-if="form.delete_logo" @click.stop="form.delete_logo = false; fileName.logo = 'Existing logo attached'; imagePreview = props.profileData?.company_logo_url" type="button" class="relative z-20 h-8 w-8 rounded-lg flex items-center justify-center text-amber-500 hover:bg-amber-50 transition-all">
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
                
                <button 
                    @click="handleSubmit" 
                    :disabled="loading || (!form.user_id && !isEditing)"
                    class="bg-indigo-600 text-white px-10 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 active:scale-95 disabled:opacity-50 disabled:active:scale-100 transition-all flex items-center gap-2"
                >
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
.floating-label { top: 1.15rem; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>