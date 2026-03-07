<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { useToast } from "vue-toastification";

const router = useRouter();
const toast = useToast();
const loading = ref(false);

const form = reactive({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    role_id: 2,
    headline: '',
    location: '',
    company_name: '',
    industry: ''
});

const passwordsMatch = computed(() => {
    if (!form.password && !form.password_confirmation) return true;
    return form.password === form.password_confirmation;
});

const validateForm = () => {
    if (!form.first_name || !form.last_name) return "Full name is required.";
    
    if (!form.email) return "Email address is required.";
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(form.email)) return "Please enter a valid email address.";

    if (!form.phone) return "Phone number is required.";
    const phoneRegex = /^\d+$/;
    if (!phoneRegex.test(form.phone)) return "Phone number must contain only numbers.";
    if (form.phone.length < 9 || form.phone.length > 15) return "Please enter a valid phone number length.";

    if (form.role_id === 3) {
        if (!form.company_name || !form.industry) return "Company details are required for Employers.";
    } else {
        if (!form.headline || !form.location) return "Professional details are required for Seekers.";
    }
    
    if (!form.password) return "Password is required.";
    if (form.password.length < 8) return "Password must be at least 8 characters.";
    if (!passwordsMatch.value) return "Passwords do not match.";

    return null;
};

const handleRegister = async () => {
    const errorMsg = validateForm();
    if (errorMsg) {
        toast.error(errorMsg);
        return;
    }

    loading.value = true;
    try {
        const response = await api.post('/register', form);
        const { token, user } = response.data;

        localStorage.setItem('auth_token', token);
        localStorage.setItem('user_id', user.id);
        localStorage.setItem('user_role', user.role_id);
        localStorage.setItem('user_email', user.email);
        localStorage.setItem('user_name', `${user.first_name} ${user.last_name}`.trim());
        localStorage.setItem('user_profile_pic', user.profile?.profile_picture_url || '');

        if (user.role_id === 3) {
            const employerData = user.employer_profile || user.employerProfile;
            if (employerData) {
                localStorage.setItem('employer_profile_id', employerData.id || employerData.employer_profile_id);
                localStorage.setItem('company_name', employerData.company_name);
                localStorage.setItem('company_logo', employerData.company_logo_url);
                localStorage.setItem('user_is_verified', employerData.is_verified);
            }
            toast.success(`Welcome, ${user.first_name}!`);
            router.push({ name: 'employer-home' });
        } else {
            const seekerProfile = user.profile || user.seekerProfile;
            if (seekerProfile) {
                localStorage.setItem('job_seeker_profile_id', seekerProfile.id || user.job_seeker_profile_id);
            }
            toast.success(`Welcome, ${user.first_name}!`);
            router.push({ name: 'seeker-home' });
        }
    } catch (e) {
        if (e.response?.status === 422) {
            const validationErrors = e.response.data.errors;
            toast.error(Object.values(validationErrors)[0][0]);
        } else {
            toast.error(e.response?.data?.message || "Registration failed.");
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-12 relative overflow-hidden font-sans">
        <div class="absolute top-[-10%] left-[-10%] h-[40%] w-[40%] bg-indigo-100/40 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] h-[40%] w-[40%] bg-blue-50/50 rounded-full blur-[120px]"></div>

        <div class="max-w-xl w-full relative z-10">
            <div class="flex items-center justify-center mb-10">
                <div class="relative flex items-center">
                    <div class="absolute -left-4 h-14 w-14 bg-indigo-100/60 rounded-full blur-xl"></div>
                    <img src="@/assets/logo.svg" alt="Logo" class="relative z-10 h-12 w-12 object-contain" style="filter: invert(48%) sepia(13%) saturate(583%) hue-rotate(182deg) brightness(92%) contrast(88%);" />
                    <span class="relative z-10 text-3xl font-extrabold text-indigo-600 tracking-tighter -ml-2">
                        Job<span class="font-light text-gray-400">Recon</span>
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl shadow-indigo-100/50 border border-gray-100 p-8 md:p-10">
                <div class="mb-8 text-center md:text-left">
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Create Account</h2>
                    <p class="text-gray-500 text-sm mt-1 font-medium">Start your journey in the ecosystem.</p>
                </div>

                <form @submit.prevent="handleRegister" class="space-y-5" novalidate>
                    <div class="flex p-1 bg-gray-100 rounded-xl mb-6">
                        <button type="button" @click="form.role_id = 2" 
                            :class="form.role_id === 2 ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500'"
                            class="flex-1 py-2.5 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all">
                            Job Seeker
                        </button>
                        <button type="button" @click="form.role_id = 3" 
                            :class="form.role_id === 3 ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500'"
                            class="flex-1 py-2.5 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all">
                            Employer
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest ml-1">First Name</label>
                            <input v-model="form.first_name" type="text" placeholder="John" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest ml-1">Last Name</label>
                            <input v-model="form.last_name" type="text" placeholder="Doe" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest ml-1">Email Address</label>
                        <input v-model="form.email" type="email" placeholder="john@example.com" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest ml-1">Phone Number</label>
                        <input v-model="form.phone" type="text" placeholder="09123456789" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
                    </div>

                    <transition name="slide">
                        <div v-if="form.role_id === 3" class="space-y-4 pt-2">
                            <div class="h-px bg-gray-100"></div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-blue-500 uppercase tracking-widest ml-1">Company Name</label>
                                <input v-model="form.company_name" type="text" placeholder="KMD College" class="block w-full px-4 py-3 bg-blue-50/30 border border-blue-100 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-blue-500 uppercase tracking-widest ml-1">Industry</label>
                                <input v-model="form.industry" type="text" placeholder="Software Development" class="block w-full px-4 py-3 bg-blue-50/30 border border-blue-100 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
                            </div>
                        </div>

                        <div v-else class="space-y-4 pt-2">
                            <div class="h-px bg-gray-100"></div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest ml-1">Headline</label>
                                <input v-model="form.headline" type="text" placeholder="Full Stack Developer" class="block w-full px-4 py-3 bg-emerald-50/30 border border-emerald-100 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest ml-1">Location</label>
                                <input v-model="form.location" type="text" placeholder="Yangon, Myanmar" class="block w-full px-4 py-3 bg-emerald-50/30 border border-emerald-100 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all" />
                            </div>
                        </div>
                    </transition>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest ml-1">Password</label>
                            <input v-model="form.password" type="password" placeholder="••••••••" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest ml-1">Confirm</label>
                            <input v-model="form.password_confirmation" type="password" placeholder="••••••••" :class="{'border-red-300 ring-red-100': !passwordsMatch && form.password_confirmation}" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
                        </div>
                    </div>

                    <button :disabled="loading" type="submit" class="w-full flex items-center justify-center py-4 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-200 transition-all transform active:scale-95 disabled:opacity-70 mt-6">
                        <i v-if="loading" class="fa-solid fa-circle-notch animate-spin mr-2"></i>
                        {{ loading ? 'Creating Account...' : 'Get Started' }}
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <p class="text-gray-400 text-xs font-medium">
                        Already have an account? 
                        <router-link :to="{ name: 'login' }" class="text-indigo-600 font-bold hover:underline ml-1">Sign In</router-link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all 0.3s ease-out; }
.slide-enter-from { opacity: 0; transform: translateY(-10px); }
.slide-leave-to { opacity: 0; transform: translateY(10px); }
</style>