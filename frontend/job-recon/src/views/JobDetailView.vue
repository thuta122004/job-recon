<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { useToast } from 'vue-toastification';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const loading = ref(true);
const applying = ref(false);
const job = ref(null);
const hasApplied = ref(false);
const showCopyTooltip = ref(false);

const showApplyModal = ref(false);
const cover_letter = ref('');
const seekerId = localStorage.getItem('job_seeker_profile_id');

const fetchJobDetail = async () => {
    loading.value = true;
    try {
        const response = await api.get(`/seeker/jobs/${route.params.slug}`);
        job.value = response.data;
        if (seekerId && job.value) {
            const checkResponse = await api.get(`/seeker/applications/check/${job.value.id}/${seekerId}`);
            hasApplied.value = checkResponse.data.applied;
        }
    } catch (error) {
        console.error("Error fetching job details:", error);
    } finally {
        loading.value = false;
    }
};

const submitApplication = async () => {
    if (!seekerId) {
        toast.error("Please complete your seeker profile first.");
        return;
    }

    applying.value = true;
    try {
        await api.post('/seeker/applications', {
            job_post_id: job.value.id,
            job_seeker_id: seekerId,
            cover_letter: cover_letter.value
        });
        
        toast.success("Application submitted successfully!");
        hasApplied.value = true;
        showApplyModal.value = false;
    } catch (error) {
        const message = error.response?.data?.message || "Failed to submit application";
        toast.error(message);
    } finally {
        applying.value = false;
    }
};

const handleShare = async () => {
    const shareData = {
        title: job.value?.title || 'Job Opportunity',
        text: `Check out this ${job.value?.title} position at ${job.value?.employer?.company_name || 'JobRecon'}!`,
        url: window.location.href,
    };
    try {
        if (navigator.share) {
            await navigator.share(shareData);
        } else {
            await navigator.clipboard.writeText(window.location.href);
            showCopyTooltip.value = true;
            setTimeout(() => (showCopyTooltip.value = false), 2000);
        }
    } catch (err) { console.error('Share failed:', err); }
};

const daysRemaining = computed(() => {
    if (!job.value?.expires_at) return null;
    const diff = new Date(job.value.expires_at) - new Date();
    const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
    return days > 0 ? days : 0;
});

const formatCurrency = (value) => Number(value).toLocaleString();

onMounted(fetchJobDetail);
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] selection:bg-indigo-100 selection:text-indigo-700">
        <transition name="fade">
            <div v-if="showCopyTooltip" class="fixed top-24 left-1/2 -translate-x-1/2 z-[60] bg-slate-900 text-white px-6 py-3 rounded-2xl text-[10px] font-black tracking-[0.2em] shadow-2xl">
                LINK COPIED TO CLIPBOARD
            </div>
        </transition>

        <nav class="bg-white/80 backdrop-blur-md border-b border-slate-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                <button @click="router.back()" class="group flex items-center gap-2 text-slate-400 hover:text-indigo-600 font-bold text-xs uppercase tracking-widest transition-all">
                    <i class="fa-solid fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> 
                    Back to Listings
                </button>
                <div class="flex items-center gap-3">
                    <button class="h-11 w-11 flex items-center justify-center rounded-xl border border-slate-100 text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 transition-all">
                        <i class="fa-regular fa-bookmark text-lg"></i>
                    </button>
                    <button @click="handleShare" class="h-11 px-6 bg-slate-900 text-white text-[10px] font-black uppercase tracking-[0.15em] rounded-xl hover:bg-indigo-600 transition-all active:scale-95 flex items-center gap-2">
                        <i class="fa-solid fa-share-nodes"></i>
                        Share Job
                    </button>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto px-6 py-12">
            <div v-if="loading" class="animate-pulse space-y-8">
                <div class="h-64 bg-white rounded-[2.5rem] border border-slate-100"></div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <div class="lg:col-span-2 h-[600px] bg-white rounded-[2.5rem]"></div>
                    <div class="h-96 bg-white rounded-[2.5rem]"></div>
                </div>
            </div>

            <div v-else-if="job" class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
                
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white p-10 md:p-12 rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 h-40 w-40 bg-indigo-50/30 rounded-full -mr-20 -mt-20 blur-3xl"></div>
                        <div class="relative z-10">
                            <div class="flex flex-wrap gap-2 mb-8">
                                <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-tighter rounded-md">{{ job.employment_type }}</span>
                                <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-tighter rounded-md">{{ job.workplace_type }}</span>
                                <span class="px-3 py-1 bg-slate-50 text-slate-500 text-[10px] font-black uppercase tracking-tighter rounded-md">{{ job.experience_level }}</span>
                            </div>

                            <h1 class="text-4xl md:text-6xl font-black text-slate-900 tracking-tighter leading-[1.1] mb-8">
                                {{ job.title }}
                            </h1>

                            <div class="flex flex-wrap gap-8 items-center pt-8 border-t border-slate-50">
                                <div class="flex items-center gap-4">
                                    <div class="relative">
                                        <div class="h-14 w-14 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-50 group-hover:bg-white group-hover:border-indigo-100 transition-all duration-500 overflow-hidden">
                                            <img 
                                                v-if="job.employer?.company_logo_url" 
                                                :src="job.employer.company_logo_url" 
                                                :alt="job.employer?.company_name"
                                                class="h-full w-full object-cover"
                                            />
                                            <i v-else class="fa-solid fa-briefcase text-slate-300 group-hover:text-indigo-500 text-xl"></i>
                                        </div>

                                        <div 
                                            v-if="job.employer?.is_verified == 1 || job.employer?.is_verified == true" 
                                            class="absolute -bottom-1 -right-1 bg-white rounded-full p-0.5 shadow-sm z-30 flex items-center justify-center"
                                            title="Verified Employer"
                                        >
                                            <i class="fa-solid fa-circle-check text-emerald-400 text-[14px]"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Hiring Company</p>
                                        <p class="font-bold text-slate-800 text-lg">
                                            {{ job.employer?.company_name || 'Incognito Entity' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <div class="h-16 w-16 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100">
                                        <i class="fa-solid fa-location-dot text-slate-400 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Location</p>
                                        <p class="font-bold text-slate-800 text-lg">{{ job.location }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <section class="bg-white p-10 md:p-12 rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                            <div class="absolute top-0 left-0 w-1.5 h-1/2 bg-indigo-600 rounded-full translate-y-1/2 opacity-0 group-hover:opacity-100 transition-all duration-500"></div>
                            <div class="flex items-center gap-4 mb-8">
                                <div class="h-10 w-10 bg-indigo-50 rounded-xl flex items-center justify-center">
                                    <i class="fa-solid fa-circle-info text-indigo-600 text-sm"></i>
                                </div>
                                <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.3em]">About the Role</h2>
                            </div>
                            <div class="text-slate-600 text-lg leading-relaxed whitespace-pre-line font-medium">{{ job.description }}</div>
                        </section>

                        <section v-if="job.responsibilities" class="bg-white p-10 md:p-12 rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                            <div class="absolute top-0 left-0 w-1.5 h-1/2 bg-indigo-600 rounded-full translate-y-1/2 opacity-0 group-hover:opacity-100 transition-all duration-500"></div>
                            <div class="flex items-center gap-4 mb-8">
                                <div class="h-10 w-10 bg-indigo-50 rounded-xl flex items-center justify-center">
                                    <i class="fa-solid fa-list-check text-indigo-600 text-sm"></i>
                                </div>
                                <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.3em]">Main Responsibilities</h2>
                            </div>
                            <div class="text-slate-600 text-lg leading-relaxed whitespace-pre-line font-medium">{{ job.responsibilities }}</div>
                        </section>

                        <section class="bg-white p-10 md:p-12 rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                            <div class="absolute top-0 left-0 w-1.5 h-1/2 bg-indigo-600 rounded-full translate-y-1/2 opacity-0 group-hover:opacity-100 transition-all duration-500"></div>
                            <div class="flex items-center gap-4 mb-8">
                                <div class="h-10 w-10 bg-indigo-50 rounded-xl flex items-center justify-center">
                                    <i class="fa-solid fa-graduation-cap text-indigo-600 text-sm"></i>
                                </div>
                                <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.3em]">Requirements</h2>
                            </div>
                            <div v-if="job.skills?.length" class="mb-10">
                                <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest mb-4">Stack & Tools</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="skill in job.skills" :key="skill.id" class="px-5 py-2.5 bg-slate-50 border border-slate-100 rounded-2xl text-[10px] font-black text-slate-600 hover:border-indigo-600 hover:text-indigo-600 transition-all shadow-sm cursor-default">{{ skill.name }}</span>
                                </div>
                            </div>
                            <div class="bg-slate-50/50 p-8 rounded-[2rem] border border-slate-100/50">
                                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Qualifications</h3>
                                <p class="text-slate-600 text-lg leading-relaxed whitespace-pre-line font-medium">{{ job.qualifications }}</p>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="space-y-6 lg:sticky lg:top-32">
                    <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-xl shadow-indigo-100/50">
                        <p class="text-[13px] font-black text-slate-500 uppercase tracking-widest mb-4">Financial Package</p>
                        <div class="mb-10">
                            <template v-if="job.salary_visible && job.salary_min !== null">
                                <span class="text-4xl font-black">
                                    {{ job.currency }} {{ formatCurrency(job.salary_min) }} - {{ formatCurrency(job.salary_max) }}
                                </span>
                                <p class="text-slate-500 text-xs font-bold mt-2">Monthly Gross • Full Benefits Included</p>
                            </template>
                            <template v-else-if="job.salary_visible">
                                <span class="text-3xl font-black text-slate-400 italic">Negotiable</span>
                                <p class="text-slate-500 text-xs font-bold mt-2">Contact employer for details</p>
                            </template>
                            <template v-else>
                                <span class="text-3xl font-black italic text-slate-400">Competitive</span>
                                <p class="text-slate-500 text-xs font-bold mt-2">Salary disclosed during interview</p>
                            </template>
                        </div>
                        <button 
                            v-if="!hasApplied"
                            @click="showApplyModal = true"
                            class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl transition-all shadow-lg shadow-indigo-100 hover:shadow-indigo-200 active:scale-[0.98] mb-4"
                        >
                            APPLY FOR THIS ROLE
                        </button>

                        <button 
                            v-else
                            disabled
                            class="w-full py-4 bg-emerald-50 text-emerald-600 border border-emerald-100 text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl mb-4 flex items-center justify-center gap-2 cursor-not-allowed"
                        >
                            <i class="fa-solid fa-circle-check"></i>
                            APPLICATION SUBMITTED
                        </button>
                        <p class="text-[11px] text-center text-slate-500 font-bold uppercase tracking-widest">Process takes ~2 mins</p>
                    </div>

                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm space-y-6">
                        <div class="flex justify-between items-center pb-4 border-b border-slate-50">
                            <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Category</span>
                            <span class="text-xs font-black text-slate-700">{{ job.category?.name }}</span>
                        </div>

                        <div v-if="job.expires_at" class="flex justify-between items-center pb-4 border-b border-slate-50">
                            <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Closing In</span>
                            <span :class="daysRemaining <= 5 ? 'text-rose-500' : 'text-emerald-500'" class="text-xs font-black uppercase">
                                {{ daysRemaining > 0 ? daysRemaining + ' Days Left' : 'Closing Today' }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center pb-4 border-b border-slate-50">
                            <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Reference</span>
                            <span class="text-xs font-black text-slate-700 uppercase">{{ job.slug }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Posted On</span>
                            <span class="text-xs font-black text-slate-700">{{ new Date(job.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</span>
                        </div>
                    </div>

                    <div class="p-8 rounded-[2.5rem] bg-indigo-50/50 border border-indigo-100 text-center">
                        <p class="text-xs font-bold text-indigo-900 mb-2">Need clarification?</p>
                        <a href="#" class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] hover:underline">Contact Candidate Support</a>
                    </div>
                </div>

            </div>

            <div v-else class="text-center py-24 bg-white rounded-[3rem] border border-slate-100 max-w-2xl mx-auto shadow-sm">
                <div class="h-20 w-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fa-solid fa-ghost text-3xl text-slate-200"></i>
                </div>
                <h2 class="text-2xl font-black text-slate-900">This role is no longer active.</h2>
                <p class="text-slate-400 mt-2 mb-8 font-medium">The position might have been filled or the listing expired.</p>
                <button @click="router.push('/seeker/home')" class="px-10 py-4 bg-slate-900 text-white text-xs font-black uppercase tracking-widest rounded-2xl">Return to Discovery</button>
            </div>
            <transition name="modal">
                <div v-if="showApplyModal" class="fixed inset-0 z-[100] flex items-center justify-center p-6">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showApplyModal = false"></div>
                    
                    <div class="bg-white w-full max-w-xl rounded-[2.5rem] shadow-2xl relative z-10 overflow-hidden animate-in fade-in zoom-in duration-300">
                        <div class="p-8 md:p-12">
                            <div class="flex items-center justify-between mb-8">
                                <div>
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tighter">Finalize Application</h3>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Applying for {{ job.title }}</p>
                                </div>
                                <button @click="showApplyModal = false" class="h-10 w-10 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:text-rose-500 transition-colors">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>

                            <div class="space-y-6">
                                <div class="p-6 bg-indigo-50/50 rounded-2xl border border-indigo-100/50 flex items-start gap-4">
                                    <i class="fa-solid fa-file-pdf text-indigo-500 text-2xl mt-1"></i>
                                    <div>
                                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">System Attachment</p>
                                        <p class="text-sm font-bold text-slate-700">Your profile resume will be attached automatically.</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Cover Letter (Optional)</label>
                                    <textarea 
                                        v-model="cover_letter"
                                        rows="5"
                                        placeholder="Briefly explain why you're a great fit for this role..."
                                        class="w-full p-6 bg-slate-50 border border-slate-100 rounded-2xl text-slate-600 font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none resize-none"
                                    ></textarea>
                                </div>

                                <button 
                                    @click="submitApplication"
                                    :disabled="applying"
                                    class="w-full py-5 bg-slate-900 text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-indigo-600 transition-all shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3"
                                >
                                    <i v-if="applying" class="fa-solid fa-circle-notch fa-spin"></i>
                                    {{ applying ? 'SUBMITTING...' : 'CONFIRM APPLICATION' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </main>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translate(-50%, -10px); }
.whitespace-pre-line { word-break: break-word; }
</style>