<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { useToast } from 'vue-toastification';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const loading = ref(true);
const isProcessing = ref(false);
const applications = ref([]);
const jobDetails = ref(null);
const filters = ref({ status: '', search: '' });

const showConfirmModal = ref(false);
const rejectionReason = ref(''); 
const pendingAction = ref({ id: null, status: null });

const showProfileModal = ref(false);
const selectedSeeker = ref(null);
const loadingProfile = ref(false);

const showInterviewModal = ref(false);
const interviewForm = ref({
    job_application_id: null,
    title: '',
    scheduled_at: '',
    type: 'ONLINE',
    location_url: ''
});

const openInterviewModal = async (app) => {
    interviewForm.value = {
        job_application_id: app.id,
        title: `Interview for ${jobDetails.value?.title}`,
        scheduled_at: '',
        type: 'ONLINE',
        location_url: ''
    };

    if (app.status === 'INTERVIEW_SCHEDULED') {
        try {
            const res = await api.get(`/seeker/applications/${app.id}/interviews`);
            if (res.data.data.length > 0) {
                const latest = res.data.data[0];
                
                let formattedDate = '';
                if (latest.scheduled_at) {
                    const dateObj = new Date(latest.scheduled_at);
                    formattedDate = dateObj.toISOString().slice(0, 16);
                }

                interviewForm.value = {
                    ...interviewForm.value,
                    scheduled_at: formattedDate,
                    type: latest.type,
                    location_url: latest.location_url
                };
            }
        } catch (e) {
            toast.error("Failed to load existing interview data");
        }
    }

    showInterviewModal.value = true;
};

const handleScheduleInterview = async () => {
    if (!interviewForm.value.scheduled_at || !interviewForm.value.location_url) {
        toast.warning("Please fill in all interview details.");
        return;
    }

    isProcessing.value = true;
    try {
        await api.post('/employer/interviews', interviewForm.value);
        toast.success("Interview scheduled successfully");
        showInterviewModal.value = false;
        await fetchApplications();
    } catch (e) {
        toast.error(e.response?.data?.message || "Failed to schedule interview");
    } finally {
        isProcessing.value = false;
    }
};

const getImageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    const baseUrl = import.meta.env.VITE_API_BASE_URL?.replace('/api', '') || 'http://localhost:8000';
    const cleanPath = path.startsWith('/') ? path.substring(1) : path;
    return `${baseUrl}/storage/${cleanPath}`;
};

const fetchApplications = async () => {
    loading.value = true;
    const jobId = route.params.jobId;
    try {
        const res = await api.get(`/employer/jobs/${jobId}/applications`);
        applications.value = res.data.data;
        
        if (applications.value.length > 0) {
            jobDetails.value = applications.value[0].job_post;
        } else {
            const jobRes = await api.get(`/job-posts/${jobId}`);
            jobDetails.value = jobRes.data.data;
        }
    } catch (e) {
        toast.error("Failed to load applicants");
    } finally {
        loading.value = false;
    }
};

const openProfileModal = async (application) => {
    const userId = application.job_seeker.user_id;
    selectedSeeker.value = null;
    showProfileModal.value = true;
    loadingProfile.value = true;
    
    try {
        const res = await api.get(`/seeker/my-profile/${userId}`);
        if (res.data.status) {
            selectedSeeker.value = res.data.data;
            
            if (application.status === 'PENDING') {
                await api.patch(`/employer/applications/${application.id}/status`, { 
                    status: 'REVIEWING' 
                });
                await fetchApplications();
            }
        }
    } catch (e) {
        toast.error("Error loading profile");
    } finally {
        loadingProfile.value = false;
    }
};

const getStatusTheme = (status) => {
    const themes = {
        'PENDING': { icon: 'fa-solid fa-clock', color: 'text-amber-600', bg: 'bg-amber-50', border: 'border-amber-100', label: 'New' },
        'REVIEWING': { icon: 'fa-solid fa-eye', color: 'text-blue-600', bg: 'bg-blue-50', border: 'border-blue-100', label: 'Reviewing' },
        'SHORTLISTED': { icon: 'fa-solid fa-star', color: 'text-indigo-600', bg: 'bg-indigo-50', border: 'border-indigo-100', label: 'Shortlisted' },
        'INTERVIEW_SCHEDULED': { icon: 'fa-solid fa-calendar', color: 'text-purple-600', bg: 'bg-purple-50', border: 'border-purple-100', label: 'Interviewing' },
        'INTERVIEWED': { icon: 'fa-solid fa-clipboard-check', color: 'text-cyan-600', bg: 'bg-cyan-50', border: 'border-cyan-100', label: 'Interviewed' },
        'OFFERED': { icon: 'fa-solid fa-handshake', color: 'text-emerald-600', bg: 'bg-emerald-50', border: 'border-emerald-100', label: 'Offered' },
        'REJECTED': { icon: 'fa-solid fa-xmark', color: 'text-rose-600', bg: 'bg-rose-50', border: 'border-rose-100', label: 'Rejected' },
        'WITHDRAWN': { icon: 'fa-solid fa-user-minus', color: 'text-slate-400', bg: 'bg-slate-50', border: 'border-slate-100', label: 'Withdrawn' },
    };
    return themes[status] || { icon: 'fa-solid fa-circle', color: 'text-slate-400', bg: 'bg-slate-50', border: 'border-slate-100', label: status };
};

const confirmStatusChange = (id, status) => {
    pendingAction.value = { id, status };
    rejectionReason.value = ''; 
    showConfirmModal.value = true;
};

const executeStatusUpdate = async () => {
    if (pendingAction.value.status === 'REJECTED' && !rejectionReason.value.trim()) {
        toast.warning("Please provide a reason for rejection.");
        return;
    }

    isProcessing.value = true;
    try {
        await api.patch(`/employer/applications/${pendingAction.value.id}/status`, { 
            status: pendingAction.value.status,
            rejection_reason: pendingAction.value.status === 'REJECTED' ? rejectionReason.value : null,
        });
        toast.success(`Candidate moved to ${pendingAction.value.status}`);
        showConfirmModal.value = false;
        await fetchApplications();
    } catch (e) {
        toast.error("Update failed");
    } finally {
        isProcessing.value = false;
    }
};

const filteredApplications = computed(() => {
    return applications.value.filter(app => {
        const matchesStatus = !filters.value.status || app.status === filters.value.status;
        const name = `${app.job_seeker?.user?.first_name} ${app.job_seeker?.user?.last_name}`.toLowerCase();
        return matchesStatus && name.includes(filters.value.search.toLowerCase());
    });
});

const showCompleteModal = ref(false);
const completionForm = ref({
    application_id: null,
    interview_id: null,
    feedback: ''
});

const openCompleteModal = async (app) => {
    try {
        const res = await api.get(`/seeker/applications/${app.id}/interviews`);
        if (res.data.data.length > 0) {
            const latest = res.data.data[0];
            completionForm.value = {
                application_id: app.id,
                interview_id: latest.id,
                feedback: latest.feedback || ''
            };
            showCompleteModal.value = true;
        } else {
            toast.error("No active interview record found.");
        }
    } catch (e) {
        toast.error("Error loading interview details");
    }
};

const executeInterviewCompletion = async () => {
    if (!completionForm.value.feedback.trim()) {
        toast.warning("Please enter your interview notes/feedback.");
        return;
    }

    isProcessing.value = true;
    try {
        await api.patch(`/employer/interviews/${completionForm.value.interview_id}/status`, {
            interview_status: 'COMPLETED',
            feedback: completionForm.value.feedback
        });
        
        toast.success("Interview marked as completed");
        showCompleteModal.value = false;
        await fetchApplications();
    } catch (e) {
        toast.error("Failed to save feedback");
    } finally {
        isProcessing.value = false;
    }
};

onMounted(fetchApplications);
</script>

<template>
    <div class="max-w-7xl mx-auto pb-24 pt-8 px-4 sm:px-6">
        
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
            <div class="space-y-4">
                <button @click="router.back()" class="group flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 transition-colors">
                    <i class="fa-solid fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                    Back to Jobs
                </button>
                <div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tighter leading-none mb-3">
                        Applicant Pipeline
                    </h1>
                    <div class="flex items-center gap-3 bg-white border border-slate-100 py-1.5 px-3 rounded-full w-fit shadow-sm">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ jobDetails?.title || 'Loading...' }}</p>
                    </div>
                </div>
            </div>

            <button @click="fetchApplications" :disabled="loading" class="h-14 px-6 bg-white border border-slate-100 rounded-2xl flex items-center gap-3 hover:bg-slate-50 transition-all group active:scale-95 shadow-sm">
                <i class="fa-solid fa-arrows-rotate text-indigo-600 group-hover:rotate-180 transition-transform duration-700" :class="{'fa-spin': loading}"></i>
                <span class="text-[11px] font-black uppercase tracking-widest text-slate-600">Sync Data</span>
            </button>
        </header>

        <div class="flex flex-col lg:flex-row gap-10">
            <aside class="w-full lg:w-72 space-y-6">
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-50/30 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative z-10">
                        <h2 class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] mb-8">Summary</h2>
                        <div class="p-6 bg-slate-50 rounded-3xl">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Received</p>
                            <p class="text-3xl font-black text-slate-900 tracking-tighter">{{ applications.length }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-white rounded-[2rem] border border-slate-100 shadow-sm space-y-6">
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-3 ml-1">Search Name</label>
                        <div class="relative group">
                            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-indigo-500 transition-colors"></i>
                            <input v-model="filters.search" type="text" placeholder="TYPE NAME..." class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border-none rounded-xl text-[10px] font-black uppercase focus:ring-2 focus:ring-indigo-500/20" />
                        </div>
                    </div>

                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-3 ml-1">Status Filter</label>
                        <div class="grid grid-cols-1 gap-2">
                            <button v-for="s in ['', 'PENDING', 'REVIEWING', 'SHORTLISTED', 'INTERVIEW_SCHEDULED', 'OFFERED', 'REJECTED']" :key="s" 
                                @click="filters.status = s"
                                :class="filters.status === s ? 'bg-indigo-600 text-white shadow-lg' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'"
                                class="w-full text-left px-4 py-3 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all">
                                {{ s === '' ? 'All Applications' : s.replace('_', ' ') }}
                            </button>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="flex-1 space-y-4">
                <div v-if="loading" class="space-y-4">
                    <div v-for="i in 3" :key="i" class="h-40 bg-slate-100/50 animate-pulse rounded-[2rem]"></div>
                </div>

                <div v-else-if="filteredApplications.length === 0" class="py-24 text-center bg-white rounded-[3rem] border-2 border-dashed border-slate-100">
                    <div class="h-20 w-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-user-slash text-2xl text-slate-200"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 tracking-tighter">No candidates found</h3>
                    <p class="text-slate-400 text-sm mt-1">Try adjusting your filters or search term</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="app in filteredApplications" :key="app.id" 
                         class="group bg-white p-6 sm:p-8 rounded-[2.5rem] border border-slate-100 hover:border-indigo-200 hover:shadow-2xl hover:shadow-indigo-500/5 transition-all duration-500 flex flex-col md:flex-row md:items-center gap-8">
                        
                        <div class="shrink-0 relative">
                            <div class="h-20 w-20 bg-indigo-50 rounded-[1.5rem] overflow-hidden flex items-center justify-center text-indigo-600 text-2xl font-black group-hover:scale-105 transition-transform duration-500 shadow-sm border border-indigo-100/50">
                                <img v-if="app.job_seeker?.profile_picture_url" 
                                    :src="getImageUrl(app.job_seeker.profile_picture_url)" 
                                    class="h-full w-full object-cover" 
                                    @error="(e) => e.target.style.display = 'none'" />
                                <span v-else class="tracking-tighter">
                                    {{ app.job_seeker?.user?.first_name?.[0] }}{{ app.job_seeker?.user?.last_name?.[0] }}
                                </span>
                            </div>
                            <div :class="getStatusTheme(app.status).bg" class="absolute -bottom-1 -right-1 h-7 w-7 rounded-full border-2 border-white flex items-center justify-center shadow-md">
                                <i :class="[getStatusTheme(app.status).icon, getStatusTheme(app.status).color]" class="text-[9px]"></i>
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <span :class="[getStatusTheme(app.status).bg, getStatusTheme(app.status).color, getStatusTheme(app.status).border]" 
                                      class="px-2.5 py-1 rounded-md text-[8px] font-black uppercase border tracking-widest">
                                    {{ getStatusTheme(app.status).label }}
                                </span>
                                <span class="text-[10px] font-medium text-slate-300">{{ new Date(app.created_at).toLocaleDateString() }}</span>
                            </div>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tighter group-hover:text-indigo-600 transition-colors truncate">
                                {{ app.job_seeker?.user?.first_name }} {{ app.job_seeker?.user?.last_name }}
                            </h3>
                            <div class="flex flex-wrap gap-4 mt-2 text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                <span class="flex items-center gap-1.5"><i class="fa-solid fa-envelope opacity-30"></i> {{ app.job_seeker?.user?.email }}</span>
                                <span v-if="app.job_seeker?.phone" class="flex items-center gap-1.5"><i class="fa-solid fa-phone opacity-30"></i> {{ app.job_seeker?.phone }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 md:pl-8 md:border-l border-slate-50">
                            <a v-if="app.job_seeker?.resume_url" :href="app.job_seeker.resume_url" target="_blank" class="h-12 w-12 flex items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="View Resume">
                                <i class="fa-solid fa-file-pdf text-sm"></i>
                            </a>

                            <div v-if="!['REJECTED', 'WITHDRAWN', 'OFFERED'].includes(app.status)" class="flex gap-2">
    
                                <button v-if="app.status === 'PENDING'" 
                                        @click="confirmStatusChange(app.id, 'REVIEWING')" 
                                        class="h-12 px-5 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 hover:-translate-y-0.5 transition-all shadow-lg shadow-blue-100">
                                    Review
                                </button>

                                <button v-if="app.status === 'REVIEWING'" 
                                        @click="confirmStatusChange(app.id, 'SHORTLISTED')" 
                                        class="h-12 px-5 bg-indigo-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 hover:-translate-y-0.5 transition-all shadow-lg shadow-indigo-100">
                                    Shortlist
                                </button>
                                
                                <button v-if="app.status === 'SHORTLISTED'" 
                                        @click="openInterviewModal(app)" 
                                        class="h-12 px-5 bg-purple-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-purple-700 hover:-translate-y-0.5 transition-all shadow-lg shadow-purple-100">
                                    Schedule Interview
                                </button>

                                <template v-if="app.status === 'INTERVIEW_SCHEDULED'">
                                    <button @click="openCompleteModal(app)" 
                                            class="h-12 px-5 bg-cyan-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-700 hover:-translate-y-0.5 transition-all shadow-lg shadow-cyan-100">
                                        Complete Interview
                                    </button>
                                    <button @click="openInterviewModal(app)" 
                                            class="h-12 w-12 flex items-center justify-center rounded-2xl bg-purple-50 text-purple-600 hover:bg-purple-600 hover:text-white transition-all" title="Reschedule">
                                        <i class="fa-solid fa-calendar-day"></i>
                                    </button>
                                </template>

                                <button v-if="app.status === 'INTERVIEWED'" 
                                        @click="confirmStatusChange(app.id, 'OFFERED')" 
                                        class="h-12 px-5 bg-emerald-500 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 hover:-translate-y-0.5 transition-all shadow-lg shadow-emerald-100">
                                    Send Offer
                                </button>

                                <button @click="confirmStatusChange(app.id, 'REJECTED')" 
                                        class="h-12 w-12 flex items-center justify-center rounded-2xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all" title="Reject">
                                    <i class="fa-solid fa-user-xmark text-sm"></i>
                                </button>
                            </div>

                            <button 
                                @click="openProfileModal(app)" 
                                class="h-12 w-12 flex items-center justify-center rounded-2xl bg-slate-50 text-slate-400 hover:bg-white hover:text-indigo-600 hover:shadow-md transition-all"
                                title="View Profile"
                            >
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="showCompleteModal" class="fixed inset-0 z-[140] flex items-center justify-center p-6">
                <div @click="showCompleteModal = false" class="absolute inset-0 bg-slate-900/80 backdrop-blur-md"></div>
                <div class="relative max-w-md w-full bg-white rounded-[3rem] shadow-2xl p-10">
                    <div class="h-16 w-16 bg-cyan-100 text-cyan-600 rounded-2xl flex items-center justify-center mb-6 text-2xl">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    
                    <h3 class="text-3xl font-black text-slate-900 tracking-tighter mb-2">Interview Notes</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-6">Summarize the candidate's performance</p>

                    <div class="space-y-4">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block ml-1">Employer Notes / Feedback</label>
                        <textarea 
                            v-model="completionForm.feedback" 
                            placeholder="How did the interview go? Mention strengths, weaknesses, or technical assessment results..." 
                            class="w-full p-5 bg-slate-50 border-none rounded-2xl text-[11px] font-bold h-40 resize-none focus:ring-2 focus:ring-cyan-500/20"
                        ></textarea>
                    </div>

                    <div class="mt-8 flex gap-3">
                        <button @click="showCompleteModal = false" class="flex-1 py-4 rounded-2xl text-[10px] font-black uppercase text-slate-400 bg-slate-50 hover:bg-slate-100 transition-all">Cancel</button>
                        <button @click="executeInterviewCompletion" :disabled="isProcessing" class="flex-1 py-4 bg-cyan-600 text-white rounded-2xl text-[10px] font-black uppercase shadow-xl shadow-cyan-100 hover:bg-cyan-700 transition-all">
                            {{ isProcessing ? 'Saving...' : 'Mark as Interviewed' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="showConfirmModal" class="fixed inset-0 z-[110] flex items-center justify-center p-6">
                <div @click="showConfirmModal = false" class="absolute inset-0 bg-slate-900/80 backdrop-blur-md"></div>
                <div class="relative max-w-sm w-full bg-white rounded-[3rem] shadow-2xl p-10 text-center">
                    <div :class="[
                        pendingAction.status === 'REJECTED' ? 'bg-rose-50 text-rose-600' : 
                        pendingAction.status === 'OFFERED' ? 'bg-emerald-50 text-emerald-600' : 
                        pendingAction.status === 'REVIEWING' ? 'bg-blue-50 text-blue-600' : 'bg-indigo-50 text-indigo-600'
                    ]" class="h-20 w-20 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-3xl">
                        <i :class="[
                            pendingAction.status === 'REJECTED' ? 'fa-solid fa-user-xmark' : 
                            pendingAction.status === 'OFFERED' ? 'fa-solid fa-handshake' : 
                            pendingAction.status === 'REVIEWING' ? 'fa-solid fa-eye' : 'fa-solid fa-star'
                        ]"></i>
                    </div>
                    
                    <h3 class="text-3xl font-black text-slate-900 tracking-tighter mb-4">
                        {{ 
                            pendingAction.status === 'REJECTED' ? 'Reject Application?' : 
                            pendingAction.status === 'OFFERED' ? 'Send Offer?' : 
                            pendingAction.status === 'REVIEWING' ? 'Move to Reviewing?' : 'Shortlist Candidate?' 
                        }}
                    </h3>

                    <div v-if="pendingAction.status === 'REJECTED'" class="mb-6">
                        <textarea v-model="rejectionReason" placeholder="Reason for rejection..." class="w-full p-5 bg-slate-50 border-none rounded-2xl text-[11px] font-bold h-32 resize-none focus:ring-2 focus:ring-rose-500/20"></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button @click="showConfirmModal = false" class="flex-1 py-4 rounded-2xl text-[10px] font-black uppercase text-slate-400 bg-slate-50 hover:bg-slate-100 transition-all">Cancel</button>
                        <button @click="executeStatusUpdate" :disabled="isProcessing" 
                                :class="[
                                    pendingAction.status === 'REJECTED' ? 'bg-rose-500 hover:bg-rose-600' : 
                                    pendingAction.status === 'OFFERED' ? 'bg-emerald-500 hover:bg-emerald-600' : 
                                    pendingAction.status === 'REVIEWING' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-indigo-600 hover:bg-indigo-700'
                                ]" 
                                class="flex-1 py-4 rounded-2xl text-[10px] font-black uppercase text-white shadow-xl transition-all shadow-indigo-200">
                            {{ isProcessing ? 'Saving...' : 'Confirm' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition 
            enter-active-class="transition duration-300 ease-out" 
            enter-from-class="opacity-0" 
            enter-to-class="opacity-100" 
            leave-active-class="transition duration-200 ease-in" 
            leave-from-class="opacity-100" 
            leave-to-class="opacity-0"
        >
            <div v-if="showProfileModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4 sm:p-6">
                <div @click="showProfileModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-md"></div>
                <div class="relative w-full max-w-4xl max-h-[90vh] bg-white rounded-[3rem] shadow-2xl overflow-hidden flex flex-col">
                    
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tighter">Candidate Profile</h2>
                        </div>
                        <button @click="showProfileModal = false" class="h-10 w-10 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-8">
                        <div v-if="loadingProfile" class="flex flex-col items-center justify-center py-20 space-y-4">
                            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-600"></i>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Loading Resume...</p>
                        </div>

                        <div v-else-if="selectedSeeker" class="grid grid-cols-1 md:grid-cols-3 gap-10">
                            <div class="space-y-8">
                                <div class="text-center md:text-left">
                                    <div class="h-32 w-32 bg-slate-50 rounded-[2.5rem] overflow-hidden mx-auto md:mx-0 mb-4 shadow-inner border-4 border-white">
                                        <img v-if="selectedSeeker.profile_picture_url" :src="getImageUrl(selectedSeeker.profile_picture_url)" class="w-full h-full object-cover">
                                        <div v-else class="w-full h-full flex items-center justify-center text-indigo-300 text-3xl font-black bg-indigo-50/50">
                                            {{ selectedSeeker.user?.first_name?.[0] }}{{ selectedSeeker.user?.last_name?.[0] }}
                                        </div>
                                    </div>
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ selectedSeeker.user?.first_name }} {{ selectedSeeker.user?.last_name }}</h3>
                                    <p class="text-indigo-600 font-bold text-sm">{{ selectedSeeker.headline || 'Professional Candidate' }}</p>
                                </div>
                                
                                <div v-if="selectedSeeker.skills?.length">
                                    <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Technical Skills</h4>
                                    <div class="space-y-3">
                                        <div v-for="skill in selectedSeeker.skills" :key="skill.id" class="space-y-1">
                                            <div class="flex justify-between text-[10px] font-bold text-slate-600">
                                                <span>{{ skill.name }}</span>
                                                <span class="text-indigo-500">{{ skill.pivot?.proficiency }}%</span>
                                            </div>
                                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-indigo-500 rounded-full" :style="{ width: skill.pivot?.proficiency + '%' }"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 bg-slate-50 rounded-2xl">
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Location</p>
                                    <p class="text-sm font-bold text-slate-700">{{ selectedSeeker.location || 'Remote / Not Specified' }}</p>
                                </div>
                            </div>

                            <div class="md:col-span-2 space-y-10">
                                <section>
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                        <span class="h-1 w-6 bg-indigo-600 rounded-full"></span> Professional Summary
                                    </h4>
                                    <p class="text-slate-600 leading-relaxed font-medium">{{ selectedSeeker.summary || 'No summary provided.' }}</p>
                                </section>

                                <section v-if="selectedSeeker.experiences?.length">
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                                        <span class="h-1 w-6 bg-emerald-500 rounded-full"></span> Work Experience
                                    </h4>
                                    <div class="space-y-8 ml-2 border-l-2 border-slate-50 pl-6">
                                        <div v-for="exp in selectedSeeker.experiences" :key="exp.id" class="relative">
                                            <div class="absolute -left-[31px] top-1 h-3 w-3 rounded-full bg-white border-2 border-emerald-500"></div>
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <h5 class="text-sm font-black text-slate-900 uppercase leading-none">{{ exp.job_title }}</h5>
                                                    <span class="text-[8px] font-bold text-slate-400 uppercase">{{ exp.employment_type }}</span>
                                                </div>
                                                <span class="text-[9px] font-black text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                                                    {{ new Date(exp.start_date).getFullYear() }} - {{ exp.end_date ? new Date(exp.end_date).getFullYear() : 'Present' }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] font-bold text-indigo-600 mb-1">{{ exp.company_name }} <span class="text-slate-300 mx-1">•</span> {{ exp.location }}</p>
                                            <p class="text-xs text-slate-500 leading-relaxed">{{ exp.description }}</p>
                                        </div>
                                    </div>
                                </section>

                                <section v-if="selectedSeeker.educations?.length">
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                                        <span class="h-1 w-6 bg-amber-500 rounded-full"></span> Education
                                    </h4>
                                    <div class="space-y-8 ml-2 border-l-2 border-slate-50 pl-6">
                                        <div v-for="edu in selectedSeeker.educations" :key="edu.id" class="relative">
                                            <div class="absolute -left-[31px] top-1 h-3 w-3 rounded-full bg-white border-2 border-amber-500"></div>
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <h5 class="text-sm font-black text-slate-900 uppercase leading-none">{{ edu.qualification_name }}</h5>
                                                    <span class="text-[8px] font-bold text-amber-600 uppercase">{{ edu.education_level }}</span>
                                                </div>
                                                <span class="text-[9px] font-black text-slate-400 bg-slate-50 px-2 py-1 rounded-lg">
                                                    {{ edu.start_year }} - {{ edu.end_year || 'Ongoing' }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] font-bold text-indigo-600 mb-1">{{ edu.institution }} <span v-if="edu.field_of_study" class="text-slate-300 mx-1">•</span> {{ edu.field_of_study }}</p>
                                            <p class="text-xs text-slate-500 leading-relaxed">{{ edu.description }}</p>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 bg-slate-50 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                        <button @click="showProfileModal = false" class="px-8 py-3 rounded-xl text-[10px] font-black uppercase text-slate-400 bg-white border border-slate-200">Close</button>
                        <a :href="'mailto:' + selectedSeeker?.user?.email" class="px-8 py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 transition-all">Contact Candidate</a>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="showInterviewModal" class="fixed inset-0 z-[130] flex items-center justify-center p-6">
                <div @click="showInterviewModal = false" class="absolute inset-0 bg-slate-900/80 backdrop-blur-md"></div>
                <div class="relative max-w-md w-full bg-white rounded-[3rem] shadow-2xl p-10 overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-purple-50 rounded-full -mr-16 -mt-16"></div>
                    
                    <div class="relative">
                        <div class="h-16 w-16 bg-purple-600 text-white rounded-2xl flex items-center justify-center mb-8 text-2xl shadow-xl shadow-purple-200">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </div>
                        
                        <h3 class="text-3xl font-black text-slate-900 tracking-tighter mb-2">Schedule Interview</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-8">Set the meeting details below</p>

                        <div class="space-y-5">
                            <div>
                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2 ml-1">Meeting Type</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button v-for="t in ['ONLINE', 'IN-PERSON', 'PHONE']" :key="t" 
                                        @click="interviewForm.type = t"
                                        :class="interviewForm.type === t ? 'bg-purple-600 text-white' : 'bg-slate-50 text-slate-400'"
                                        class="py-3 rounded-xl text-[9px] font-black uppercase transition-all">
                                        {{ t }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2 ml-1">Date & Time</label>
                                <input v-model="interviewForm.scheduled_at" type="datetime-local" class="w-full p-4 bg-slate-50 border-none rounded-2xl text-[11px] font-bold focus:ring-2 focus:ring-purple-500/20" />
                            </div>

                            <div>
                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2 ml-1">
                                    {{ 
                                        interviewForm.type === 'ONLINE' ? 'Meeting Link' : 
                                        interviewForm.type === 'PHONE' ? 'Phone Number' : 'Location Address' 
                                    }}
                                </label>
                                <input v-model="interviewForm.location_url" type="text" :placeholder="interviewForm.type === 'ONLINE' ? 'https://zoom.us/...' : 'Office Building, Floor 4...'" class="w-full p-4 bg-slate-50 border-none rounded-2xl text-[11px] font-bold focus:ring-2 focus:ring-purple-500/20" />
                            </div>
                        </div>

                        <div class="mt-10 flex gap-3">
                            <button @click="showInterviewModal = false" class="flex-1 py-4 rounded-2xl text-[10px] font-black uppercase text-slate-400 bg-slate-50 hover:bg-slate-100 transition-all">Cancel</button>
                            <button @click="handleScheduleInterview" :disabled="isProcessing" class="flex-1 py-4 bg-purple-600 text-white rounded-2xl text-[10px] font-black uppercase shadow-xl shadow-purple-100 hover:bg-purple-700 transition-all">
                                {{ isProcessing ? 'Scheduling...' : 'Confirm Meeting' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        </div>
</template>

<style scoped>
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>