<script setup>
import { reactive, ref, computed, watch, onMounted } from 'vue';
import { useToast } from "vue-toastification";
import api from '@/services/api';

const props = defineProps({
    isOpen: Boolean,
    jobData: Object,
    employerId: { type: [String, Number], required: true },
    isEditing: Boolean,
    loading: Boolean
});

const emit = defineEmits(['close', 'save']);
const toast = useToast();
const categorySearch = ref('');
const showCategoryDropdown = ref(false);

const skillSearch = ref('');
const showSkillDropdown = ref(false);
const availableSkills = ref([]);
const categories = ref([]);

const form = reactive({
    id: null,
    title: '',
    job_category_id: '',
    workplace_type: 'ON-SITE',
    location: '',
    employment_type: 'FULL-TIME',
    experience_level: 'ENTRY-LEVEL',
    description: '',
    responsibilities: '', 
    qualifications: '',   
    salary_min: null,
    salary_max: null,
    currency: 'USD',
    skills: [], 
    status: 'DRAFT',
    expires_at: null
});

const fetchMetadata = async () => {
    try {
        const [skillRes, catRes] = await Promise.all([
            api.get('/skills'),
            api.get('/job-categories')
        ]);
        availableSkills.value = skillRes.data.data || skillRes.data;
        categories.value = catRes.data.data || catRes.data;
    } catch (e) {
        toast.error("Metadata sync failed");
    }
};

const filteredCategories = computed(() => {
    const query = categorySearch.value.toLowerCase().trim();
    if (!query) return categories.value;
    return categories.value.filter(cat => 
        cat.name.toLowerCase().includes(query)
    );
});

const filteredSkills = computed(() => {
    const query = skillSearch.value.toLowerCase().trim();
    const selectedIds = new Set(form.skills.map(s => s.id));
    let list = availableSkills.value.filter(s => !selectedIds.has(s.id));
    if (query) list = list.filter(s => s.name.toLowerCase().includes(query));
    return list;
});

const selectCategory = (cat) => {
    form.job_category_id = cat.id;
    categorySearch.value = cat.name;
    showCategoryDropdown.value = false;
};

const addSkill = (skill) => {
    form.skills.push(skill);
    skillSearch.value = '';
    showSkillDropdown.value = false;
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (!props.isEditing) {
            Object.assign(form, {
                id: null, title: '', job_category_id: '', workplace_type: 'ON-SITE',
                location: '', employment_type: 'FULL-TIME', experience_level: 'ENTRY-LEVEL',
                description: '', responsibilities: '', qualifications: '',
                salary_min: null, salary_max: null, currency: 'USD', 
                skills: [],
                status: 'OPEN', 
                expires_at: null,
                salary_visible: true
            });
            categorySearch.value = '';
        } else if (props.jobData) {
            const expiryDate = props.jobData.expires_at ? props.jobData.expires_at.split(' ')[0] : null;
            
            Object.assign(form, { 
                ...props.jobData, 
                expires_at: expiryDate,
                skills: props.jobData.skills ? [...props.jobData.skills] : [],
                salary_visible: props.jobData.salary_visible ?? true
            });

            const currentCat = categories.value.find(c => c.id === props.jobData.job_category_id);
            categorySearch.value = currentCat ? currentCat.name : '';
        }
    }
    showCategoryDropdown.value = false;
});

onMounted(fetchMetadata);
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="$emit('close')" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl relative z-10 overflow-hidden border border-gray-100 max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">{{ isEditing ? 'Edit Vacancy' : 'New Job Posting' }}</h3>
                    <p class="text-xs text-gray-500">Database Schema: job_posts | All fields synced with migration</p>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 p-2 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-8 overflow-y-auto custom-scrollbar space-y-7">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Job Title</label>
                        <input type="text" v-model="form.title" placeholder="e.g. Senior Software Engineer" 
                               class="w-full px-4 py-3.5 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all placeholder:text-gray-300" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Job Category</label>
                        <div class="relative">
                            <div class="relative">
                                <input 
                                    type="text" 
                                    v-model="categorySearch" 
                                    @focus="showCategoryDropdown = true" 
                                    @blur="setTimeout(() => showCategoryDropdown = false, 200)"
                                    placeholder="Search categories..."
                                    class="block w-full px-4 py-3.5 text-sm text-gray-900 bg-white border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all placeholder:text-gray-300" 
                                />
                                <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                            </div>
                            
                            <transition 
                                enter-active-class="transition duration-200 ease-out" 
                                enter-from-class="opacity-0 translate-y-1" 
                                enter-to-class="opacity-100 translate-y-0" 
                                leave-active-class="transition duration-150 ease-in" 
                                leave-from-class="opacity-100 translate-y-0" 
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <div v-if="showCategoryDropdown" class="absolute z-[60] w-full mt-2 bg-white border border-gray-100 shadow-2xl rounded-2xl overflow-hidden">
                                    <div class="max-h-60 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                        <div v-if="filteredCategories.length === 0" class="p-4 text-center">
                                            <i class="fa-solid fa-layer-group text-gray-200 text-xl mb-2"></i>
                                            <p class="text-xs text-gray-400 font-medium italic">No matching categories found</p>
                                        </div>
                                        
                                        <button 
                                            v-for="cat in filteredCategories" 
                                            :key="cat.id" 
                                            type="button" 
                                            @mousedown="selectCategory(cat)"
                                            class="w-full text-left px-4 py-3 rounded-xl hover:bg-indigo-50 transition-all flex items-center justify-between group"
                                        >
                                            <div class="flex items-center gap-3">
                                                <div class="h-8 w-8 rounded-lg bg-gray-50 flex items-center justify-center group-hover:bg-white transition-colors">
                                                    <i :class="cat.icon_class || 'fa-solid fa-tag'" class="text-indigo-500 text-xs"></i>
                                                </div>
                                                <span class="text-sm font-bold text-gray-700 group-hover:text-indigo-600 transition-colors">{{ cat.name }}</span>
                                            </div>
                                            <i v-if="form.job_category_id === cat.id" class="fa-solid fa-check text-indigo-600 text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </transition>
                        </div>
                        <p v-if="!form.job_category_id && !showCategoryDropdown" class="text-[10px] text-amber-500 font-bold mt-1.5 ml-1 animate-pulse">
                            <i class="fa-solid fa-circle-info mr-1"></i> Please select a valid category from the list.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex flex-col">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Workplace</label>
                        <div class="relative">
                            <select v-model="form.workplace_type" class="w-full px-4 py-3.5 text-sm bg-white border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none appearance-none pr-10">
                                <option value="ON-SITE">On-Site</option>
                                <option value="REMOTE">Remote</option>
                                <option value="HYBRID">Hybrid</option>
                            </select>
                            <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Employment</label>
                        <div class="relative">
                            <select v-model="form.employment_type" class="w-full px-4 py-3.5 text-sm bg-white border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none appearance-none pr-10">
                                <option value="FULL-TIME">Full-Time</option>
                                <option value="PART-TIME">Part-Time</option>
                                <option value="CONTRACT">Contract</option>
                                <option value="INTERNSHIP">Internship</option>
                                <option value="VOLUNTEER">Volunteer</option>
                            </select>
                            <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Exp. Level</label>
                        <div class="relative">
                            <select v-model="form.experience_level" class="w-full px-4 py-3.5 text-sm bg-white border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none appearance-none pr-10">
                                <option value="ENTRY-LEVEL">Entry-Level</option>
                                <option value="MID-SENIOR">Mid-Senior</option>
                                <option value="DIRECTOR">Director</option>
                                <option value="EXECUTIVE">Executive</option>
                            </select>
                            <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                    <div class="md:col-span-1 flex flex-col">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Location</label>
                        <input type="text" v-model="form.location" placeholder="City, Country" class="w-full px-4 py-3.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 outline-none" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Expiry Date</label>
                        <div class="relative">
                            <input type="date" 
                                v-model="form.expires_at" 
                                class="w-full px-4 py-3.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all" />
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Currency</label>
                        <div class="relative">
                            <select v-model="form.currency" class="w-full px-4 py-3.5 text-sm bg-white border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none appearance-none pr-10">
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="GBP">GBP (£)</option>
                                <option value="MMK">MMK (K)</option>
                                <option value="THB">THB (฿)</option>
                            </select>
                            <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                    <div class="flex flex-col">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Min Salary</label>
                        <input type="number" v-model.number="form.salary_min" placeholder="0" class="w-full px-4 py-3.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 outline-none" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Max Salary</label>
                        <input type="number" v-model.number="form.salary_max" placeholder="0" class="w-full px-4 py-3.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 outline-none" />
                    </div>
                </div>

                <div class="relative flex flex-col">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Required Skills</label>
                    <div class="flex flex-wrap gap-2 p-3 bg-gray-50/50 border border-gray-200 rounded-xl focus-within:ring-4 focus-within:ring-indigo-500/10 focus-within:bg-white transition-all min-h-[56px]">
                        <span v-for="skill in form.skills" :key="skill.id" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-[11px] font-bold flex items-center gap-2 shadow-sm">
                            {{ skill.name }}
                            <button type="button" @click="form.skills = form.skills.filter(s => s.id !== skill.id)" class="hover:text-indigo-200">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </span>
                        <input type="text" v-model="skillSearch" @focus="showSkillDropdown = true" @blur="setTimeout(() => showSkillDropdown = false, 200)" placeholder="Add skills..." class="bg-transparent border-none outline-none text-sm font-medium flex-1 min-w-[120px]" />
                    </div>
                    
                    <div v-if="showSkillDropdown" class="absolute z-[60] w-full mt-[72px] bg-white border border-gray-100 shadow-2xl rounded-xl max-h-48 overflow-y-auto p-2">
                        <button v-for="skill in filteredSkills" :key="skill.id" type="button" @mousedown="addSkill(skill)" class="w-full text-left px-4 py-2.5 rounded-lg hover:bg-indigo-50 text-sm font-bold text-gray-700 transition-colors">
                            <i class="fa-solid fa-plus text-indigo-400 mr-2 text-[10px]"></i> {{ skill.name }}
                        </button>
                        <div v-if="filteredSkills.length === 0" class="px-4 py-2 text-xs text-gray-400 italic text-center">No more skills found</div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex flex-col">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Job Description</label>
                        <textarea v-model="form.description" rows="4" placeholder="Describe the core purpose of this role..." class="w-full px-4 py-3.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none resize-none transition-all"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Responsibilities</label>
                            <textarea v-model="form.responsibilities" rows="4" placeholder="List key duties..." class="w-full px-4 py-3.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none resize-none transition-all"></textarea>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-1.5">Qualifications</label>
                            <textarea v-model="form.qualifications" rows="4" placeholder="Required education or certificates..." class="w-full px-4 py-3.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none resize-none transition-all"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button @click="$emit('close')" class="px-5 py-2.5 text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors">Discard</button>
                <button 
                    @click="emit('save', {...form})" 
                    :disabled="loading || !form.job_category_id" 
                    class="bg-indigo-600 text-white px-10 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 active:scale-95 disabled:opacity-50 disabled:active:scale-100 transition-all flex items-center gap-2"
                >
                    <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
                    {{ isEditing ? 'Update Posting' : 'Publish Vacancy' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>