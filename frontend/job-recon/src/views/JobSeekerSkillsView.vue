<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from "vue-toastification";
import api from '@/services/api';
import JobSeekerSkillModal from '@/components/JobSeekerSkillModal.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const profileId = route.params.profileId;
const profile = ref(null);
const skills = ref([]);
const loading = ref(true);
const saving = ref(false);

const showModal = ref(false);

const fetchData = async () => {
    loading.value = true;
    try {
        const res = await api.get(`/job-seeker/skills/${profileId}`);
        
        if (res.data.status && res.data.data) {
            profile.value = res.data.data.profile;
            const rawSkills = res.data.data.skills || [];
            skills.value = rawSkills.filter(skill => 
                skill.pivot && skill.status === 'ACTIVE'
            );
            skills.value.sort((a, b) => b.pivot.proficiency - a.pivot.proficiency);
        }
    } catch (e) {
        console.error("Fetch Error:", e);
        toast.error("Failed to load skills");
    } finally {
        loading.value = false;
    }
};

const openManageModal = () => {
    showModal.value = true;
};

const handleSave = async (selectedSkillsData) => {
    saving.value = true;
    try {
        const res = await api.post('/job-seeker/skills', {
            job_seeker_profile_id: profileId,
            skills: selectedSkillsData
        });
        
        if (res.data.status) {
            toast.success("Skills updated successfully");
            await fetchData();
            showModal.value = false;
        }
    } catch (e) {
        if (e.response?.status === 422) {
            toast.error(Object.values(e.response.data.errors)[0][0]);
        } else {
            toast.error("An error occurred while updating skills.");
        }
    } finally {
        saving.value = false;
    }
};

const showConfirmModal = ref(false);
const skillToDelete = ref(null);

const confirmDelete = (skill) => {
    skillToDelete.value = skill;
    showConfirmModal.value = true;
};

const executeDelete = async () => {
    if (!skillToDelete.value) return;
    try {
        await api.delete(`/job-seeker/skills/${profileId}/${skillToDelete.value.id}`);
        toast.success(`${skillToDelete.value.name} removed`);
        await fetchData();
    } catch (e) {
        toast.error("Failed to remove skill");
    } finally {
        showConfirmModal.value = false;
        skillToDelete.value = null;
    }
};

const getProficiencyColor = (percent) => {
    if (percent < 30) return 'text-slate-500 bg-slate-50 border-slate-100';
    if (percent < 60) return 'text-blue-600 bg-blue-50 border-blue-100';
    if (percent < 90) return 'text-indigo-600 bg-indigo-50 border-indigo-100';
    return 'text-emerald-600 bg-emerald-50 border-emerald-100';
};

const formatSkillDate = (dateString) => {
    if (!dateString) return 'Recent';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        year: 'numeric' 
    });
};
onMounted(fetchData);
</script>

<template>
    <div class="space-y-6 animate-in fade-in duration-500">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <button @click="router.push('/job-seeker-profiles')" 
                    class="h-11 w-11 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-400 hover:text-indigo-600 transition-all">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Professional Skills</h1>
                    <p v-if="profile" class="text-sm text-gray-500 flex items-center gap-2 mt-0.5">
                        Technical expertise for 
                        <span class="font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg">
                            {{ profile.user?.first_name }} {{ profile.user?.last_name }}
                        </span>
                    </p>
                </div>
            </div>
            <button @click="openManageModal"
                class="bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 transition-all flex items-center gap-2">
                <i class="fa-solid fa-plus text-xs"></i>
                Add Skills
            </button>
        </div>

        <div class="relative">
            <div v-if="loading" class="py-32 text-center">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-indigo-500/30"></i>
            </div>

            <div v-else-if="skills.length === 0" 
                class="bg-white rounded-3xl border-2 border-dashed border-gray-100 p-20 text-center">
                <div class="h-20 w-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fa-solid fa-layer-group text-gray-300 text-2xl"></i>
                </div>
                <h3 class="text-gray-900 font-bold text-lg">No skills added</h3>
                <p class="text-gray-500 mt-1 max-w-xs mx-auto text-sm font-medium">Add technical skills to visualize your proficiency stack.</p>
                <button @click="openManageModal" class="mt-6 text-indigo-600 font-bold text-sm hover:underline">Add skills now</button>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="skill in skills" :key="skill.id" 
                    class="group relative bg-white rounded-[2.5rem] p-8 border border-gray-50 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.02)] hover:shadow-[0_20px_40px_-12px_rgba(79,70,229,0.12)] transition-all duration-500 animate-in slide-in-from-bottom-4">
                    
                    <span class="absolute -bottom-4 -right-2 text-[10rem] font-black text-gray-50 select-none pointer-events-none group-hover:text-indigo-50/50 transition-colors duration-700 leading-none">
                        {{ skill.name.charAt(0) }}
                    </span>

                    <div class="relative flex items-start justify-between mb-10">
                        <div class="space-y-1">
                            <h3 class="font-black text-gray-900 text-xl tracking-tight leading-tight">
                                {{ skill.name }}
                            </h3>
                            <div class="flex items-center gap-2 group/date">
                                <div class="flex -space-x-1">
                                    <span class="h-1 w-1 rounded-full bg-indigo-500"></span>
                                    <span class="h-1 w-1 rounded-full bg-indigo-300 opacity-0 group-hover/card:opacity-100 transition-all duration-500"></span>
                                </div>
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] group-hover/card:text-indigo-600 transition-colors duration-500">
                                    In Stack Since {{ formatSkillDate(skill.pivot.created_at) }}
                                </span>
                            </div>
                        </div>

                        <button @click="confirmDelete(skill)" 
                            class="h-10 w-10 flex items-center justify-center text-gray-200 hover:text-red-500 hover:bg-red-50 rounded-2xl transition-all opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0 duration-300">
                            <i class="fa-solid fa-trash-can text-sm"></i>
                        </button>
                    </div>

                    <div class="relative space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Rating</span>
                            <span class="text-sm font-black text-gray-900">{{ skill.pivot.proficiency }}%</span>
                        </div>
                        
                        <div class="h-3 w-full bg-gray-50 rounded-full border border-gray-100 p-1 overflow-hidden">
                            <div 
                                class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-[1.5s] ease-out shadow-[0_0_15px_rgba(79,70,229,0.4)]"
                                :style="{ width: `${skill.pivot.proficiency}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showConfirmModal" class="fixed inset-0 z-[110] flex items-center justify-center p-6">
                <div @click="showConfirmModal = false" class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm"></div>
                <div class="relative max-w-sm w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
                    <div class="p-6 text-center">
                        <div class="bg-red-50 text-red-600 h-16 w-16 rounded-2xl flex items-center justify-center mb-4 text-2xl mx-auto">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">Remove Skill?</h3>
                        <p class="text-sm text-gray-500 mt-2">The skill <span class="font-bold text-gray-700">{{ skillToDelete?.name }}</span> will be removed from this profile.</p>
                        <div class="mt-8 flex gap-3">
                            <button @click="showConfirmModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-gray-500 bg-gray-50 hover:bg-gray-100 transition-colors">Discard</button>
                            <button @click="executeDelete" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-red-500 hover:bg-red-600 shadow-lg shadow-red-100 transition-all active:scale-95">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <JobSeekerSkillModal 
            :is-open="showModal"
            :current-skills="skills"
            :loading="saving"
            @close="showModal = false"
            @save="handleSave"
        />
    </div>
</template>