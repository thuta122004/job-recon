<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from "vue-toastification";
import api from '@/services/api';

const router = useRouter();
const toast = useToast();

const form = ref({
  email: '',
  password: ''
});

const loading = ref(false);

const handleLogin = async () => {
  const email = form.value.email.trim();
  const password = form.value.password.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  
  if (!emailRegex.test(email)) {
    toast.error("Please enter a valid email address.");
    return;
  }

  if (!email || !password) {
    toast.error("Please enter both email and password.");
    return;
  }

  loading.value = true;

  try {
    const response = await api.post('/login', form.value);
    
    if (!response.data || !response.data.user) {
      throw new Error("Invalid server response");
    }

    const { token, user } = response.data;

    localStorage.setItem('auth_token', token);
    localStorage.setItem('user_id', user.id);
    localStorage.setItem('user_role', user.role_id);
    localStorage.setItem('user_email', user.email);
    localStorage.setItem('user_profile_pic', user.profile?.profile_picture_url);
    localStorage.setItem('job_seeker_profile_id', user.job_seeker_profile_id);

    
    const employerData = user.employerProfile;

    if (employerData) {
        localStorage.setItem('employer_profile_id', employerData.employer_profile_id);
        localStorage.setItem('company_name', employerData.company_name);
        localStorage.setItem('company_logo', employerData.company_logo_url);
        localStorage.setItem('user_is_verified', employerData.is_verified);
    }

    
    const firstName = user.first_name || 'User';
    const lastName = user.last_name || '';
    localStorage.setItem('user_name', `${firstName} ${lastName}`.trim());

    if (user.role_id === 1) {
      toast.success(`Admin access granted. Welcome, ${firstName}!`);
      router.push({ name: 'admin-dashboard' });
    } else if (user.role_id === 2) {
      toast.success(`Welcome back, ${firstName}!`);
      router.push({ name: 'seeker-home' });
    } else if (user.role_id === 3) {
      toast.success(`Employer Portal Active. Welcome, ${employerData?.company_name || firstName}!`);
      router.push({ name: 'employer-home' });
    } else {
      toast.error("Role not recognized. Please contact support.");
      localStorage.clear();
    }
  } catch (err) {
    const errorMsg = err.response?.data?.message || "Invalid credentials or server error.";
    toast.error(errorMsg);
  } finally {
    loading.value = false;
  }
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 relative overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] h-[40%] w-[40%] bg-indigo-100/40 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] h-[40%] w-[40%] bg-blue-50/50 rounded-full blur-[120px]"></div>

        <div class="max-w-md w-full relative z-10">
            <div class="flex items-center justify-center mb-10">
                <div class="relative flex items-center">
                    <div class="absolute -left-4 h-14 w-14 bg-indigo-100/60 rounded-full blur-xl"></div>
                    <img src="@/assets/logo.svg" 
                        alt="JobRecon Logo" 
                        class="relative z-10 h-14 w-14 object-contain"
                        style="filter: invert(48%) sepia(13%) saturate(583%) hue-rotate(182deg) brightness(92%) contrast(88%);" />
                    <span class="relative z-10 text-3xl font-extrabold text-indigo-600 tracking-tighter -ml-2.5">
                        Job<span class="font-light text-gray-400">Recon</span>
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl shadow-indigo-100/50 border border-gray-100 p-10">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Welcome Back</h2>
                    <p class="text-gray-500 text-sm mt-1 font-medium">Please enter your account credentials.</p>
                </div>

                <form @submit.prevent="handleLogin" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-indigo-500 uppercase tracking-widest mb-2">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fa-solid fa-envelope text-xs"></i>
                            </span>
                        <input 
                            v-model="form.email"
                            type="text" 
                            class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm transition-all focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none"
                            placeholder="guest@jobrecon.com"
                        />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-indigo-500 uppercase tracking-widest mb-2">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fa-solid fa-lock text-xs"></i>
                            </span>
                        <input 
                            v-model="form.password"
                            type="password" 
                            class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm transition-all focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none"
                            placeholder="••••••••"
                        />
                        </div>
                    </div>

                    <Transition name="fade">
                        <div v-if="error" class="flex items-center gap-2 p-3 bg-red-50 rounded-lg text-red-600 text-xs font-semibold border border-red-100">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        {{ error }}
                        </div>
                    </Transition>

                    <button 
                        type="submit" 
                        :disabled="loading"
                        class="w-full flex items-center justify-center py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-200 transition-all transform active:scale-[0.98] disabled:opacity-70"
                    >
                        <i v-if="loading" class="fa-solid fa-circle-notch animate-spin mr-2"></i>
                        {{ loading ? 'Authenticating...' : 'Sign In' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-gray-400 text-xs mt-8">
                &copy; 2026 JobRecon Management System. All rights reserved.
            </p>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
