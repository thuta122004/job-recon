<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from "vue-toastification";
import api from '@/services/api';
import JobSeekerEducationModal from '@/components/JobSeekerEducationModal.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const profileId = route.params.profileId;
const profile = ref(null);
const educations = ref([]);
const loading = ref(true);
const saving = ref(false);

const showModal = ref(false);
const selectedEducation = ref(null);

const fetchData = async () => {
    loading.value = true;
    try {
        const res = await api.get(`/job-seeker-educations/${profileId}`);
        if (res.data.status) {
            profile.value = res.data.data.profile;
            educations.value = res.data.data.educations;
        }
    } catch (e) {
        console.error("API Error Details:", e.response); 
        
        toast.error("Candidate not found or unauthorized");
        router.push('/job-seeker-profiles');
    } finally {
        if (profile.value) loading.value = false;
    }
};

const openCreateModal = () => {
    selectedEducation.value = null;
    showModal.value = true;
};

const openEditModal = (edu) => {
    selectedEducation.value = { ...edu };
    showModal.value = true;
};

const handleSave = async (formData) => {
    saving.value = true;
    const cleanData = {
        ...formData,
        field_of_study: formData.field_of_study?.trim() || null,
        end_year: formData.end_year || null,
        description: formData.description?.trim() || null
    };

    try {
        if (cleanData.id) {
            await api.put(`/job-seeker-educations/${cleanData.id}`, cleanData);
            toast.success("Education record updated");
        } else {
            await api.post('/job-seeker-educations', cleanData);
            toast.success("Education record added");
        }
        await fetchData();
        showModal.value = false;
    } catch (e) {
        if (e.response?.status === 422) {
            toast.error(Object.values(e.response.data.errors)[0][0]);
        } else {
            toast.error(e.response?.data?.message || "An error occurred while saving.");
        }
    } finally {
        saving.value = false;
    }
};

const showConfirmModal = ref(false);
const educationToDelete = ref(null);

const confirmDelete = (id) => {
    educationToDelete.value = id;
    showConfirmModal.value = true;
};

const executeDelete = async () => {
    if (!educationToDelete.value) return;
    try {
        await api.delete(`/job-seeker-educations/${educationToDelete.value}`);
        toast.success("Education record removed");
        await fetchData();
    } catch (e) {
        toast.error("Failed to delete record");
    } finally {
        showConfirmModal.value = false;
        educationToDelete.value = null;
    }
};

const getStudyDuration = (start, end) => {
    const endYear = end || new Date().getFullYear();
    const diff = endYear - start;
    return diff <= 0 ? 'Short Course' : `${diff} Year${diff > 1 ? 's' : ''}`;
};

const getLevelClass = (level) => {
    const maps = {
        'PHD': 'text-purple-600 bg-purple-50 border-purple-100',
        'MASTER': 'text-blue-600 bg-blue-50 border-blue-100',
        'BACHELOR': 'text-indigo-600 bg-indigo-50 border-indigo-100',
        'DIPLOMA': 'text-amber-600 bg-amber-50 border-amber-100',
        'CERTIFICATE': 'text-slate-600 bg-slate-50 border-slate-100'
    };
    return maps[level] || 'text-gray-600 bg-gray-50';
};

onMounted(fetchData);
</script>

<template>
    <div class="space-y-6 animate-in fade-in duration-500">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <button @click="router.push('/admin/job-seeker-profiles')" 
                    class="h-11 w-11 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-400 hover:text-indigo-600 transition-all">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Academic Background</h1>
                    <p v-if="profile" class="text-sm text-gray-500 flex items-center gap-2 mt-0.5">
                        Educational history for 
                        <span class="font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg">
                            {{ profile.user?.first_name }} {{ profile.user?.last_name }}
                        </span>
                    </p>
                </div>
            </div>
            <button @click="openCreateModal"
                class="bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 transition-all flex items-center gap-2">
                <i class="fa-solid fa-plus text-xs"></i>
                Add Education
            </button>
        </div>

        <div class="relative">
            <div v-if="loading" class="py-24 text-center">
                <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-500"></i>
            </div>

            <div v-else-if="educations.length === 0" 
                class="bg-white rounded-3xl border-2 border-dashed border-gray-100 p-20 text-center">
                <div class="h-20 w-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fa-solid fa-graduation-cap text-gray-300 text-2xl"></i>
                </div>
                <h3 class="text-gray-900 font-bold text-lg">No education listed</h3>
                <p class="text-gray-500 mt-1 max-w-xs mx-auto text-sm">Add degrees or certificates to improve profile visibility.</p>
                <button @click="openCreateModal" class="mt-6 text-indigo-600 font-bold text-sm hover:underline">Add first record</button>
            </div>

            <div v-else class="space-y-4">
                <div v-for="edu in educations" :key="edu.id" 
                    class="group bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-all animate-in slide-in-from-bottom-2">
                    
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex gap-5 items-center w-full md:w-1/3">
                            <div class="h-14 w-14 rounded-2xl bg-white flex items-center justify-center text-indigo-500 shrink-0 border border-gray-100 shadow-sm group-hover:bg-indigo-50/30 transition-all">
                                <i class="fa-solid fa-graduation-cap text-xl"></i>
                            </div>
                            <div class="space-y-0.5 overflow-hidden">
                                <h3 class="font-bold text-gray-900 text-lg group-hover:text-indigo-600 transition-colors truncate">
                                    {{ edu.qualification_name }}
                                </h3>
                                <p class="text-gray-600 font-medium text-sm truncate">
                                    {{ edu.institution }}
                                </p>
                            </div>
                        </div>

                        <div class="hidden md:flex flex-1 flex-col items-center justify-center px-10 relative">
                            <div class="mb-2 flex items-center gap-1.5 px-3 py-1 bg-gray-50 rounded-full border border-gray-100 group-hover:bg-indigo-600 group-hover:border-indigo-600 transition-all duration-500">
                                <i class="fa-solid fa-calendar-days text-[9px] text-gray-400 group-hover:text-white transition-colors"></i>
                                <span class="text-[10px] font-black text-gray-500 group-hover:text-white uppercase tracking-wider transition-colors">
                                    {{ getStudyDuration(edu.start_year, edu.end_year) }}
                                </span>
                            </div>

                            <div class="w-full h-[1.5px] bg-gray-100 relative">
                                <div class="absolute inset-0 bg-gradient-to-r from-indigo-200 via-indigo-500 to-indigo-200 w-0 group-hover:w-full transition-all duration-700"></div>
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                    <div class="h-1.5 w-1.5 rounded-full bg-white border border-gray-200 group-hover:border-indigo-500 transition-all"></div>
                                </div>
                            </div>
                            
                            <span class="mt-2 text-[8px] font-bold text-gray-300 uppercase tracking-[0.3em] group-hover:text-indigo-300">
                                Academic Period
                            </span>
                        </div>

                        <div class="flex flex-col items-end gap-2 min-w-[160px] md:w-1/3">
                            <div class="flex items-center gap-3 h-9">
                                <span v-if="!edu.end_year" 
                                    class="bg-emerald-50 text-emerald-600 text-[10px] font-black px-3 py-1.5 rounded-xl uppercase border border-emerald-100 whitespace-nowrap order-1">
                                    Currently Studying
                                </span>

                                <div class="flex items-center gap-2 overflow-hidden max-w-0 opacity-0 translate-x-4 group-hover:max-w-[100px] group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-500 order-2">
                                    <button @click="openEditModal(edu)" class="h-9 w-9 flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:bg-white hover:shadow-sm border border-transparent hover:border-indigo-100 rounded-xl transition-all">
                                        <i class="fa-solid fa-pencil text-sm"></i>
                                    </button>
                                    <button @click="confirmDelete(edu.id)" class="h-9 w-9 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-white hover:shadow-sm border border-transparent hover:border-red-100 rounded-xl transition-all">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1">
                                <div :class="getLevelClass(edu.education_level)" 
                                    class="px-2 py-0.5 rounded-lg border text-[10px] font-black tracking-tighter">
                                    {{ edu.education_level.toUpperCase() === 'PHD' ? 'PhD' : edu.education_level.toUpperCase() }}
                                </div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">
                                    {{ edu.start_year }} — {{ edu.end_year || 'Present' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="edu.field_of_study || edu.description" class="mt-2 pt-2 border-t border-gray-50 space-y-2">
                        <p v-if="edu.field_of_study" class="text-xs font-bold text-indigo-600 uppercase tracking-wider">
                           Major: {{ edu.field_of_study }}
                        </p>
                        <p class="text-sm text-gray-500 leading-relaxed whitespace-pre-line">{{ edu.description }}</p>
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
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">Remove Education?</h3>
                        <p class="text-sm text-gray-500 mt-2">This academic record will be permanently deleted.</p>
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

        <JobSeekerEducationModal 
            :is-open="showModal"
            :education-data="selectedEducation"
            :profile-id="profileId"
            :loading="saving"
            @close="showModal = false"
            @save="handleSave"
        />
    </div>
</template>