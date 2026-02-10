<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from "vue-toastification";
import api from '@/services/api';
import JobSeekerExperienceModal from '@/components/JobSeekerExperienceModal.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const profileId = ref(route.params.profileId);
const isAdmin = computed(() => route.path.includes('/admin'));
const profile = ref(null);
const experiences = ref([]);
const loading = ref(true);
const saving = ref(false);

const showModal = ref(false);
const selectedExperience = ref(null);

const fetchData = async () => {
    loading.value = true;
    try {
        if (!profileId.value) {
            const userId = localStorage.getItem('user_id');
            const profileRes = await api.get(`/seeker/my-profile/${userId}`);
            profileId.value = profileRes.data.data.id;
        }

        const res = await api.get(`/job-seeker-experiences/${profileId.value}`);
        if (res.data.status) {
            profile.value = res.data.data.profile;
            experiences.value = res.data.data.experiences;
        }
    } catch (e) {
        toast.error("Work history not found");
        router.push(isAdmin.value ? '/admin/job-seeker-profiles' : '/seeker/profile');
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    selectedExperience.value = null;
    showModal.value = true;
};

const openEditModal = (exp) => {
    selectedExperience.value = { ...exp };
    showModal.value = true;
};

const handleSave = async (formData) => {
    saving.value = true;
    const cleanData = {
        ...formData,
        job_seeker_profile_id: profileId.value,
        location: formData.location?.trim() || null,
        employment_type: formData.employment_type || 'FULL-TIME',
        end_date: formData.end_date || null,
        description: formData.description?.trim() || null
    };

    try {
        if (cleanData.id) {
            await api.put(`/job-seeker-experiences/${cleanData.id}`, cleanData);
            toast.success("Experience record updated");
        } else {
            await api.post('/job-seeker-experiences', cleanData);
            toast.success("Experience record created");
        }
        await fetchData();
        showModal.value = false;
    } catch (e) {
        const msg = e.response?.data?.errors ? Object.values(e.response.data.errors)[0][0] : "Save failed";
        toast.error(msg);
    } finally {
        saving.value = false;
    }
};

const showConfirmModal = ref(false);
const experienceToDelete = ref(null);

const confirmDelete = (id) => {
    experienceToDelete.value = id;
    showConfirmModal.value = true;
};

const executeDelete = async () => {
    if (!experienceToDelete.value) return;
    
    try {
        await api.delete(`/job-seeker-experiences/${experienceToDelete.value}`);
        toast.success("Experience record removed successfully");
        await fetchData();
    } catch (e) {
        toast.error("Failed to delete record");
    } finally {
        showConfirmModal.value = false;
        experienceToDelete.value = null;
    }
};

const getDuration = (start, end) => {
    const startDate = new Date(start);
    const endDate = end ? new Date(end) : new Date();
    
    let years = endDate.getFullYear() - startDate.getFullYear();
    let months = endDate.getMonth() - startDate.getMonth();

    if (months < 0) {
        years--;
        months += 12;
    }

    const yearPart = years > 0 ? `${years} yr${years > 1 ? 's' : ''}` : '';
    const monthPart = months > 0 ? `${months} mo${months > 1 ? 's' : ''}` : '';
    
    return [yearPart, monthPart].filter(Boolean).join(' ') || '1 mo';
};

const formatYear = (dateString) => {
    if (!dateString) return 'Present';
    return new Date(dateString).getFullYear();
};

onMounted(fetchData);
</script>

<template>
    <div class="max-w-5xl mx-auto py-10 px-4 md:px-0 space-y-6 animate-in fade-in duration-500">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <button @click="router.push(isAdmin ? '/admin/job-seeker-profiles' : '/seeker/profile')" 
                    class="h-11 w-11 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-400 hover:text-indigo-600 hover:border-indigo-100 hover:shadow-sm transition-all">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div class="text-left">
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Experience History</h1>
                    <p v-if="profile" class="text-sm text-gray-500 mt-0.5">
                        <template v-if="isAdmin">
                            Managing work records for 
                            <span class="font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg ml-1">
                                {{ profile.user?.first_name }} {{ profile.user?.last_name }}
                            </span>
                        </template>
                        <template v-else>
                            Showcase your professional journey and career highlights.
                        </template>
                    </p>
                </div>
            </div>
            <button @click="openCreateModal"
                class="bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 transition-all flex items-center justify-center gap-2">
                <i class="fa-solid fa-plus text-xs"></i>
                Add Experience
            </button>
        </div>

        <div class="relative">
            <div v-if="loading" class="py-24 text-center">
                <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-500"></i>
            </div>

            <div v-else-if="experiences.length === 0" 
                class="bg-white rounded-[2rem] border-2 border-dashed border-gray-100 p-20 text-center">
                <div class="h-20 w-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fa-solid fa-briefcase text-gray-300 text-2xl"></i>
                </div>
                <h3 class="text-gray-900 font-bold text-lg">No history recorded</h3>
                <p class="text-gray-500 mt-1 max-w-xs mx-auto text-sm">
                    {{ isAdmin ? "This candidate hasn't listed any professional experience yet." : "Add your previous roles to improve your profile's strength." }}
                </p>
                <button @click="openCreateModal" class="mt-6 text-indigo-600 font-bold text-sm hover:underline">Add first job</button>
            </div>

            <div v-else class="space-y-4">
                <div v-for="exp in experiences" :key="exp.id" 
                    class="group bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-100 transition-all animate-in slide-in-from-bottom-2 text-left">
                    
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex gap-5 items-center w-full md:w-1/3">
                            <div class="h-14 w-14 rounded-2xl bg-white flex items-center justify-center text-indigo-500 shrink-0 border border-gray-100 shadow-sm group-hover:border-indigo-200 group-hover:bg-indigo-50/30 transition-all duration-500">
                                <i class="fa-solid fa-briefcase text-xl"></i>
                            </div>
                            <div class="space-y-0.5 overflow-hidden">
                                <h3 class="font-bold text-gray-900 text-lg group-hover:text-indigo-600 transition-colors truncate">
                                    {{ exp.job_title }}
                                </h3>
                                <p class="text-gray-600 font-medium flex items-center gap-1.5 text-sm">
                                    {{ exp.company_name }}
                                    <i v-if="exp.is_verified" class="fa-solid fa-circle-check text-emerald-400 text-[12px]" title="Verified Employer"></i>
                                </p>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">
                                    {{ formatYear(exp.start_date) }} — {{ formatYear(exp.end_date) }}
                                </div>
                            </div>
                        </div>

                        <div class="hidden md:flex flex-1 flex-col items-center justify-center px-10 relative">
                            <div class="mb-2 flex items-center gap-1.5 px-3 py-1 bg-indigo-50/50 rounded-full border border-indigo-100/50 group-hover:bg-indigo-600 group-hover:border-indigo-600 transition-all duration-500">
                                <i class="fa-solid fa-hourglass-half text-[9px] text-indigo-400 group-hover:text-white transition-colors"></i>
                                <span class="text-[10px] font-black text-indigo-600 group-hover:text-white uppercase tracking-wider transition-colors">
                                    {{ getDuration(exp.start_date, exp.end_date) }}
                                </span>
                            </div>
                            <div class="w-full h-[1.5px] bg-gray-100 relative">
                                <div class="absolute inset-0 bg-gradient-to-r from-indigo-200 via-indigo-500 to-indigo-200 w-0 group-hover:w-full transition-all duration-700 ease-in-out"></div>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2 min-w-[160px] md:w-1/3">
                            <div class="flex items-center gap-3 h-9">
                                <span v-if="!exp.end_date" 
                                    class="bg-emerald-50 text-emerald-600 text-[10px] font-black px-3 py-1.5 rounded-xl uppercase border border-emerald-100 whitespace-nowrap order-1">
                                    Current Role
                                </span>

                                <div class="flex items-center gap-2 opacity-0 translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-500 order-2">
                                    <button @click="openEditModal(exp)" class="h-9 w-9 flex items-center justify-center text-gray-400 hover:text-indigo-600 bg-gray-50 rounded-xl transition-all">
                                        <i class="fa-solid fa-pencil text-sm"></i>
                                    </button>
                                    <button @click="confirmDelete(exp.id)" class="h-9 w-9 flex items-center justify-center text-gray-400 hover:text-red-500 bg-gray-50 rounded-xl transition-all">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1">
                                <div class="px-2 py-0.5 rounded-lg border text-[10px] font-black uppercase tracking-tighter bg-indigo-50 text-indigo-600 border-indigo-100">
                                    {{ exp.employment_type }}
                                </div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-tight flex items-center gap-1">
                                    {{ exp.location || 'Remote' }}
                                    <i class="fa-solid fa-location-dot text-[9px]"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="exp.description" class="mt-5 pt-5 border-t border-gray-50">
                        <p class="text-sm text-gray-500 leading-relaxed whitespace-pre-line">{{ exp.description }}</p>
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
            <div v-if="showConfirmModal" class="fixed inset-0 z-[150] flex items-center justify-center p-6 text-center">
                <div @click="showConfirmModal = false" class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm"></div>
                <div class="relative max-w-sm w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
                    <div class="p-8">
                        <div class="bg-red-50 text-red-600 h-16 w-16 rounded-2xl flex items-center justify-center mb-4 text-2xl mx-auto shadow-inner">
                            <i class="fa-solid fa-trash-can"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">Remove Experience?</h3>
                        <p class="text-sm text-gray-500 mt-2">This work record will be permanently deleted from the profile.</p>
                        <div class="mt-8 flex gap-3">
                            <button @click="showConfirmModal = false" class="flex-1 px-4 py-3 rounded-xl text-xs font-black uppercase tracking-widest text-gray-500 bg-gray-50 hover:bg-gray-100 transition-colors">Discard</button>
                            <button @click="executeDelete" class="flex-1 px-4 py-3 rounded-xl text-xs font-black uppercase tracking-widest text-white bg-red-500 hover:bg-red-600 shadow-lg shadow-red-100 transition-all active:scale-95">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <JobSeekerExperienceModal 
            :is-open="showModal"
            :experience-data="selectedExperience"
            :profile-id="profileId"
            :loading="saving"
            @close="showModal = false"
            @save="handleSave"
        />
    </div>
</template>