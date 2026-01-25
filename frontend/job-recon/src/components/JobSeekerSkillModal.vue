<script setup>
import { ref, watch, computed } from 'vue';
import api from '@/services/api';

const props = defineProps({
    isOpen: Boolean,
    currentSkills: Array,
    loading: Boolean
});

const emit = defineEmits(['close', 'save']);

const searchQuery = ref('');
const allAvailableSkills = ref([]);
const selectedSkills = ref([]); 
const fetchingSkills = ref(false);

const fetchMasterSkills = async () => {
    fetchingSkills.value = true;
    try {
        const res = await api.get('/skills');
        if (res.data.status) allAvailableSkills.value = res.data.data;
    } catch (e) {
        console.error("Failed to load skills");
    } finally {
        fetchingSkills.value = false;
    }
};

const filteredSkills = computed(() => {
    const query = searchQuery.value.toLowerCase();
    
    return allAvailableSkills.value.filter(skill => {
        const matchesQuery = skill.name.toLowerCase().includes(query);
        
        const isGlobalActive = skill.status === 'ACTIVE'; 
        
        return matchesQuery && isGlobalActive;
    });
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        selectedSkills.value = props.currentSkills.map(s => ({
            id: s.id,
            name: s.name,
            proficiency: parseInt(s.pivot?.proficiency) || 50 
        }));
        if (allAvailableSkills.value.length === 0) fetchMasterSkills();
    }
});

const toggleSkill = (skill) => {
    const index = selectedSkills.value.findIndex(s => s.id === skill.id);
    if (index > -1) {
        selectedSkills.value.splice(index, 1);
    } else {
        selectedSkills.value.push({ 
            id: skill.id, 
            name: skill.name, 
            proficiency: 50 
        });
    }
};

const isSkillSelected = (id) => selectedSkills.value.some(s => s.id === id);

const getSkillRef = (id) => selectedSkills.value.find(s => s.id === id);

const handleSave = () => {
    emit('save', selectedSkills.value);
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
        <div @click="$emit('close')" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        
        <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-xl relative z-10 overflow-hidden border border-gray-100 animate-in zoom-in-95 duration-300">
            <div class="px-10 py-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
                <div>
                    <h3 class="font-black text-gray-900 text-2xl tracking-tight">Manage Skills</h3>
                    <p class="text-[10px] text-gray-400 font-bold mt-1 uppercase tracking-[0.15em]">Set your expertise percentage</p>
                </div>
                <button @click="$emit('close')" class="h-12 w-12 flex items-center justify-center rounded-2xl hover:bg-white hover:shadow-md text-gray-300 hover:text-indigo-600 transition-all active:scale-90">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-8 space-y-8">
                <div class="relative group">
                    <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-gray-300 transition-colors group-focus-within:text-indigo-500"></i>
                    <input 
                        type="text" 
                        v-model="searchQuery" 
                        placeholder="Search skills..."
                        class="w-full pl-12 pr-6 py-4 bg-gray-50 border border-transparent rounded-2xl text-sm font-medium focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 focus:bg-white outline-none transition-all"
                    />
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between px-2">
                        <label class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Available expertise</label>
                        <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">{{ selectedSkills.length }} Active</span>
                    </div>
                    
                    <div class="h-[380px] overflow-y-auto pr-2 custom-scrollbar space-y-3">
                        <div v-if="fetchingSkills" class="h-full flex flex-col items-center justify-center space-y-3 opacity-40">
                            <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-600"></i>
                            <p class="text-[10px] font-black uppercase tracking-widest text-center">Loading master list...</p>
                        </div>
                        
                        <div v-else class="space-y-2">
                            <div 
                                v-for="skill in filteredSkills" 
                                :key="skill.id"
                                :class="isSkillSelected(skill.id) ? 'border-indigo-100 bg-indigo-50/20 ring-1 ring-indigo-50' : 'border-gray-50 bg-white hover:border-gray-200'"
                                class="group flex flex-col p-4 rounded-3xl border transition-all duration-300"
                            >
                                <div class="flex items-center justify-between mb-4">
                                    <button @click="toggleSkill(skill)" class="flex items-center gap-4 flex-1 text-left">
                                        <div :class="isSkillSelected(skill.id) ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-gray-50 border-gray-100 text-transparent'" 
                                            class="h-6 w-6 rounded-lg border flex items-center justify-center transition-all group-hover:scale-110">
                                            <i class="fa-solid fa-check text-[10px]"></i>
                                        </div>
                                        <span :class="isSkillSelected(skill.id) ? 'text-gray-900 font-bold' : 'text-gray-500 font-medium'" class="text-sm">
                                            {{ skill.name }}
                                        </span>
                                    </button>

                                    <div v-if="isSkillSelected(skill.id)" class="text-right">
                                        <span class="text-xs font-black text-indigo-600">{{ getSkillRef(skill.id).proficiency }}%</span>
                                    </div>
                                </div>

                                <div v-if="isSkillSelected(skill.id)" class="px-1 animate-in slide-in-from-top-2 duration-300">
                                    <input 
                                        type="range" 
                                        v-model="getSkillRef(skill.id).proficiency"
                                        min="0" max="100" step="5"
                                        class="skill-slider w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                                    />
                                    <div class="flex justify-between mt-2">
                                        <span class="text-[8px] font-black text-gray-300 uppercase tracking-tighter">Learning</span>
                                        <span class="text-[8px] font-black text-gray-300 uppercase tracking-tighter">Mastered</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-10 py-8 bg-gray-50/50 border-t border-gray-50 flex justify-end items-center gap-6">
                <button @click="$emit('close')" class="text-xs font-black uppercase tracking-widest text-gray-400 hover:text-gray-600 transition-colors">
                    Discard
                </button>
                <button @click="handleSave" :disabled="loading"
                    class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-200 text-white px-10 py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-indigo-100 transition-all flex items-center gap-3 active:scale-95">
                    <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
                    Save Stack
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.skill-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    background: #ffffff;
    border: 3px solid #4f46e5;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    transition: all 0.2s;
}

.skill-slider::-webkit-slider-thumb:hover {
    transform: scale(1.2);
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #f1f5f9; border-radius: 10px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background: #e2e8f0; }
</style>