<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { useToast } from 'vue-toastification';

const router = useRouter();
const toast = useToast();

const loading = ref(true);
const isProcessing = ref(false);
const applications = ref([]);
const seekerId = localStorage.getItem('job_seeker_profile_id');

const showConfirmModal = ref(false);
const selectedAppId = ref(null);

const fetchApplications = async () => {
    if (!seekerId) {
        toast.error("Profile ID not found.");
        return;
    }
    loading.value = true;
    try {
        const response = await api.get(`/seeker/applications/${seekerId}`);
        let apps = response.data.data;

        apps = await Promise.all(apps.map(async (app) => {
            try {
                const interviewRes = await api.get(`/seeker/applications/${app.id}/interviews`);
                app.interview = interviewRes.data.data[0] || null;
            } catch (e) {
                app.interview = null;
            }
            return app;
        }));

        applications.value = apps;
    } catch (error) {
        toast.error("Failed to load application history");
    } finally {
        loading.value = false;
    }
};

const getStatusTheme = (status) => {
    const themes = {
        'PENDING': { icon: 'fa-solid fa-paper-plane', color: 'text-amber-500', bg: 'bg-amber-50', label: 'Submitted' },
        'REVIEWING': { icon: 'fa-solid fa-eye', color: 'text-blue-600', bg: 'bg-blue-50', label: 'In Review' },
        'SHORTLISTED': { icon: 'fa-solid fa-star', color: 'text-indigo-600', bg: 'bg-indigo-50', label: 'Shortlisted' },
        'INTERVIEW_SCHEDULED': { icon: 'fa-solid fa-calendar-check', color: 'text-purple-600', bg: 'bg-purple-50', label: 'Interview Set' },
        'INTERVIEWED': { icon: 'fa-solid fa-clipboard-check', color: 'text-cyan-600', bg: 'bg-cyan-50', label: 'Interviewed' },
        'OFFERED': { icon: 'fa-solid fa-handshake', color: 'text-emerald-600', bg: 'bg-emerald-50', label: 'Offer Received' },
        'REJECTED': { icon: 'fa-solid fa-circle-xmark', color: 'text-rose-600', bg: 'bg-rose-50', label: 'Not Selected' },
        'WITHDRAWN': { icon: 'fa-solid fa-ban', color: 'text-slate-400', bg: 'bg-slate-50', label: 'Withdrawn' }
    };
    return themes[status] || { icon: 'fa-solid fa-circle-question', color: 'text-slate-400', bg: 'bg-slate-50', label: status };
};

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'N/A';
    const date = new Date(dateTimeString);
    return date.toLocaleString('en-US', {
        weekday: 'short', month: 'short', day: 'numeric',
        hour: 'numeric', minute: 'numeric',
        timeZone: 'UTC'
    });
};

const promptWithdraw = (id) => {
    selectedAppId.value = id;
    showConfirmModal.value = true;
};

const executeWithdraw = async () => {
    isProcessing.value = true;
    try {
        await api.post(`/seeker/applications/${selectedAppId.value}/withdraw`);
        toast.success("Application withdrawn");
        showConfirmModal.value = false;
        await fetchApplications();
    } catch (error) {
        toast.error(error.response?.data?.message || "Withdrawal failed");
    } finally {
        isProcessing.value = false;
        selectedAppId.value = null;
    }
};

const handleReapply = async (id) => {
    isProcessing.value = true;
    try {
        await api.post(`/seeker/applications/${id}/reapply`);
        toast.success("Application re-activated");
        await fetchApplications();
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to re-activate");
    } finally {
        isProcessing.value = false;
    }
};

onMounted(fetchApplications);
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] selection:bg-indigo-100 selection:text-indigo-700">
        
        <nav class="bg-white/80 backdrop-blur-md border-b border-slate-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                <button @click="router.push({ name: 'seeker-home' })" class="group flex items-center gap-2 text-slate-400 hover:text-indigo-600 font-bold text-xs uppercase tracking-widest transition-all">
                    <i class="fa-solid fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> 
                    Back to Home
                </button>
                <div class="flex items-center gap-3">
                    <div class="h-2 w-2 rounded-full bg-indigo-500 animate-pulse"></div>
                    <span class="text-[10px] font-black text-slate-900 uppercase tracking-[0.2em]">Live Tracking</span>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto px-6 py-12">
            <div class="flex flex-col lg:flex-row gap-12 items-start">
                
                <aside class="w-full lg:w-80 space-y-6 lg:sticky lg:top-32">
                    <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-50/30 rounded-full -mr-12 -mt-12"></div>
                        
                        <div class="relative z-10">
                            <h2 class="text-[10px] font-black text-slate-900 uppercase tracking-[0.3em] mb-10">Application Stats</h2>
                            
                            <div class="space-y-4">
                                <div class="p-5 bg-slate-50 rounded-2xl">
                                    <p class="text-[9px] font-black text-slate-300 uppercase tracking-widest mb-1">Total Submissions</p>
                                    <p class="text-2xl font-black text-slate-900">{{ applications.length }}</p>
                                </div>
                                
                                <button @click="router.push({ name: 'job-listings' })" class="w-full py-4 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all active:scale-95">
                                    Browse More Roles
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-10 rounded-[3rem] bg-white border border-slate-100 shadow-sm">
                        <p class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                            <i class="fa-solid fa-circle-info"></i>
                            Quick Tips
                        </p>
                        <p class="text-xs font-bold text-slate-400 leading-relaxed uppercase tracking-tight">
                            You can re-activate withdrawn applications at any time unless the job has expired.
                        </p>
                    </div>
                </aside>

                <div class="flex-1 w-full space-y-8">
                    
                    <div v-if="loading" class="space-y-8">
                        <div v-for="i in 3" :key="i" class="h-64 bg-white animate-pulse rounded-[3rem] border border-slate-100"></div>
                    </div>

                    <div v-else-if="applications.length === 0" class="bg-white rounded-[3rem] p-24 text-center border border-slate-100 shadow-sm">
                        <div class="h-20 w-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fa-solid fa-paper-plane text-3xl text-slate-200"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tighter">No Applications Found</h3>
                        <p class="text-slate-400 font-medium mt-2">You haven't applied to any positions yet.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-6">
                        <div v-for="app in applications" :key="app.id" 
                            class="bg-white p-10 rounded-[3rem] border border-slate-100 hover:border-indigo-200 shadow-sm hover:shadow-2xl hover:shadow-indigo-100/40 transition-all duration-500 group relative overflow-hidden flex flex-col gap-8">
                            
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/0 to-indigo-50/0 group-hover:to-indigo-50/50 transition-colors duration-500 pointer-events-none"></div>

                            <div class="flex flex-col md:flex-row md:items-center gap-10 z-10">
                                <div class="relative shrink-0">
                                    <div class="h-20 w-20 bg-slate-50 rounded-[1.5rem] flex items-center justify-center border border-slate-50 group-hover:bg-white group-hover:border-indigo-100 transition-all duration-500 overflow-hidden">
                                        <img v-if="app.job_post.employer?.company_logo_url" :src="app.job_post.employer.company_logo_url" class="h-full w-full object-cover" />
                                        <i v-else class="fa-solid fa-briefcase text-slate-300 group-hover:text-indigo-500 text-2xl"></i>
                                    </div>
                                </div>

                                <div class="flex-1 space-y-3 relative">
                                    <div class="flex flex-wrap gap-2">
                                        <div :class="[getStatusTheme(app.status).bg, getStatusTheme(app.status).color]" 
                                            class="px-3 py-1 text-[9px] font-black uppercase rounded-lg flex items-center gap-2">
                                            <i :class="getStatusTheme(app.status).icon"></i>
                                            {{ getStatusTheme(app.status).label }}
                                        </div>
                                        <span class="px-3 py-1 bg-slate-50 text-slate-400 text-[9px] font-black uppercase rounded-lg">
                                            Applied {{ new Date(app.created_at).toLocaleDateString() }}
                                        </span>
                                    </div>
                                    <h3 @click="router.push({ name: 'job-detail', params: { slug: app.job_post.slug } })" 
                                        class="text-3xl font-black text-slate-900 group-hover:text-indigo-600 transition-colors leading-tight tracking-tighter cursor-pointer">
                                        {{ app.job_post.title }}
                                    </h3>
                                    <p class="text-slate-400 font-bold text-xs uppercase tracking-tight">
                                        {{ app.job_post.employer?.company_name }} • {{ app.job_post.location }}
                                    </p>
                                </div>

                                <div class="shrink-0 text-left md:text-right flex flex-row md:flex-col justify-between items-center md:items-end gap-6 border-t md:border-t-0 md:border-l border-slate-50 pt-8 md:pt-0 md:pl-10 relative">
                                    <div class="flex flex-col items-end gap-2">
                                        <button @click="router.push({ name: 'job-detail', params: { slug: app.job_post.slug } })" 
                                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-indigo-600 transition-colors">
                                            View Details
                                        </button>
                                        
                                        <button v-if="['PENDING', 'REVIEWING', 'SHORTLISTED'].includes(app.status)" 
                                            @click="promptWithdraw(app.id)"
                                            class="text-[10px] font-black text-rose-500 uppercase tracking-widest hover:bg-rose-50 px-3 py-1 rounded-lg transition-all">
                                            <i class="fa-solid fa-trash-can mr-1"></i> Retract
                                        </button>

                                        <button v-else-if="app.status === 'WITHDRAWN'" 
                                            @click="handleReapply(app.id)"
                                            :disabled="isProcessing"
                                            class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:bg-indigo-50 px-3 py-1 rounded-lg transition-all">
                                            <i class="fa-solid fa-rotate-right mr-1" :class="{'fa-spin': isProcessing}"></i> Re-activate
                                        </button>
                                    </div>
                                    <div @click="router.push({ name: 'job-detail', params: { slug: app.job_post.slug } })" 
                                        class="h-12 w-12 rounded-full bg-slate-900 text-white flex items-center justify-center group-hover:bg-indigo-600 group-hover:rotate-[-45deg] transition-all duration-500 shadow-lg shadow-slate-200 group-hover:shadow-indigo-200 cursor-pointer">
                                        <i class="fa-solid fa-arrow-right text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="app.interview || app.status === 'OFFERED' || app.status === 'REJECTED'" class="border-t border-slate-100 pt-8 z-10 space-y-4">
                                
                                <div v-if="app.status === 'INTERVIEW_SCHEDULED' && app.interview" class="bg-purple-50/50 p-6 rounded-2xl border border-purple-100">
                                    <div class="flex items-center justify-between gap-4 mb-4">
                                        <h4 class="text-xs font-black text-purple-900 uppercase tracking-widest flex items-center gap-2">
                                            <i class="fa-solid fa-calendar-alt"></i> Interview Scheduled
                                        </h4>
                                        <span class="text-xs font-bold text-purple-700 bg-white px-3 py-1 rounded-full uppercase">
                                            {{ app.interview.type }}
                                        </span>
                                    </div>
                                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p class="text-xs text-purple-600 font-bold uppercase tracking-wider">Date & Time</p>
                                            <p class="font-bold text-purple-950">{{ formatDateTime(app.interview.scheduled_at) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-purple-600 font-bold uppercase tracking-wider">Location / Link</p>
                                            <a v-if="app.interview.type === 'ONLINE'" :href="app.interview.location_url" target="_blank" class="font-bold text-indigo-600 hover:underline flex items-center gap-1.5">
                                                <i class="fa-solid fa-video"></i> Join Meeting
                                            </a>
                                            <p v-else class="font-bold text-purple-950">{{ app.interview.location_url }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-else-if="app.status === 'INTERVIEWED' && app.interview" class="bg-cyan-50/50 p-6 rounded-2xl border border-cyan-100">
                                    <p class="text-xs text-cyan-800 font-black uppercase tracking-wider mb-2 flex items-center gap-2">
                                        <i class="fa-solid fa-comment-dots"></i> Interview Feedback
                                    </p>
                                    <p class="text-sm text-cyan-950 leading-relaxed">{{ app.interview.feedback || 'Waiting for employer feedback...' }}</p>
                                </div>
                                
                                <div v-else-if="app.status === 'OFFERED'" class="bg-emerald-50/50 p-6 rounded-2xl border border-emerald-100 text-center">
                                    <i class="fa-solid fa-circle-check text-4xl text-emerald-500 mb-3"></i>
                                    <h4 class="text-lg font-black text-emerald-900 uppercase tracking-widest">Congratulations!</h4>
                                    <p class="text-sm text-emerald-800 mt-1">You have received an offer for this position.</p>
                                </div>

                                <div v-else-if="app.status === 'REJECTED'" class="bg-rose-50/50 p-6 rounded-2xl border border-rose-100">
                                    <p class="text-xs text-rose-800 font-black uppercase tracking-wider mb-2 flex items-center gap-2">
                                        <i class="fa-solid fa-envelope-open-text"></i> Update
                                    </p>
                                    <p class="text-sm text-rose-950 leading-relaxed">Thank you for your interest. Unfortunately, we will not be moving forward with your application at this time.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
        
        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="showConfirmModal" class="fixed inset-0 z-[110] flex items-center justify-center p-6">
                <div @click="showConfirmModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="relative max-w-sm w-full bg-white rounded-[3rem] shadow-2xl p-10 text-center border border-slate-100">
                    <div class="h-20 w-20 bg-rose-50 text-rose-500 rounded-3xl flex items-center justify-center mx-auto mb-8 text-3xl">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 class="text-3xl font-black text-slate-900 tracking-tighter leading-tight">Confirm <br/> Retraction?</h3>
                    <p class="text-slate-500 mt-4 text-sm font-medium leading-relaxed">This will remove your candidacy. You can re-activate it later from this page.</p>
                    <div class="mt-10 flex gap-3">
                        <button @click="showConfirmModal = false" class="flex-1 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-400 bg-slate-50 hover:bg-slate-100">Discard</button>
                        <button @click="executeWithdraw" :disabled="isProcessing" class="flex-1 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest text-white bg-rose-500 hover:bg-rose-600 shadow-xl shadow-rose-100 transition-all">
                            {{ isProcessing ? 'Retracting...' : 'Confirm' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>