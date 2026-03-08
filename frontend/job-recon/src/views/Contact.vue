<script setup>
import { reactive, ref } from 'vue';
import { useToast } from "vue-toastification";

const toast = useToast();
const loading = ref(false);

const form = reactive({
  name: '',
  email: '',
  subject: 'General Inquiry',
  message: ''
});

const handleSubmit = () => {
  if (!form.name || !form.email || !form.message) {
    toast.error("Please fill in all required fields.");
    return;
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(form.email)) {
    toast.error("Please enter a valid email address.");
    return;
  }

  loading.value = true;
  
  setTimeout(() => {
    toast.success("Message sent successfully! We'll get back to you soon.");
    
    form.name = '';
    form.email = '';
    form.subject = 'General Inquiry';
    form.message = '';
    
    loading.value = false;
  }, 1500);
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-10 px-4 py-12 relative overflow-hidden font-sans rounded-xl">
    <div class="absolute top-[-10%] left-[-10%] h-[40%] w-[40%] bg-indigo-100/40 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-10%] h-[40%] w-[40%] bg-blue-50/50 rounded-full blur-[120px]"></div>

    <div class="max-w-5xl w-full grid md:grid-cols-5 gap-0 relative z-10 bg-white rounded-3xl shadow-xl shadow-indigo-100/50 border border-gray-100 overflow-hidden">
      
      <div class="md:col-span-2 bg-indigo-600 p-10 text-white flex flex-col justify-between">
        <div>
          <h2 class="text-3xl font-bold mb-4">Get in touch</h2>
          <p class="text-indigo-100 text-sm leading-relaxed mb-8">
            Whether you are an employer looking for talent or a job seeker needing assistance, our team is here to help you navigate the JobRecon portal.
          </p>

          <div class="space-y-6">
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 bg-indigo-500/50 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-phone text-sm"></i>
              </div>
              <span class="text-sm font-medium">+95 9 123 456 789</span>
            </div>
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 bg-indigo-500/50 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-envelope text-sm"></i>
              </div>
              <span class="text-sm font-medium">support@jobrecon.com</span>
            </div>
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 bg-indigo-500/50 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-location-dot text-sm"></i>
              </div>
              <span class="text-sm font-medium">Yangon, Myanmar</span>
            </div>
          </div>
        </div>

        <div class="mt-12 flex gap-4">
          <a href="#" class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center hover:bg-white hover:text-indigo-600 transition-colors"><i class="fa-brands fa-facebook-f text-xs"></i></a>
          <a href="#" class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center hover:bg-white hover:text-indigo-600 transition-colors"><i class="fa-brands fa-linkedin-in text-xs"></i></a>
          <a href="#" class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center hover:bg-white hover:text-indigo-600 transition-colors"><i class="fa-brands fa-twitter text-xs"></i></a>
        </div>
      </div>

      <div class="md:col-span-3 p-10">
        <form @submit.prevent="handleSubmit" class="space-y-5">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest ml-1">Full Name</label>
              <input v-model="form.name" type="text" placeholder="John Doe" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest ml-1">Email</label>
              <input v-model="form.email" type="text" placeholder="john@example.com" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" />
            </div>
          </div>
          
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest ml-1">Subject</label>
            <div class="relative">
              <select v-model="form.subject" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all appearance-none">
                <option>General Inquiry</option>
                <option>Account Support</option>
                <option>Employer Partnerships</option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-400">
                <i class="fa-solid fa-chevron-down text-xs"></i>
              </div>
            </div>
          </div>

          <div class="space-y-1">
            <label class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest ml-1">Message</label>
            <textarea v-model="form.message" rows="4" placeholder="How can we help you?" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all resize-none"></textarea>
          </div>

          <button :disabled="loading" type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-100 transition-all transform active:scale-95 mt-4 disabled:opacity-70 disabled:cursor-not-allowed">
            <i v-if="loading" class="fa-solid fa-circle-notch animate-spin mr-2"></i>
            {{ loading ? 'Sending...' : 'Send Message' }}
          </button>
        </form>
      </div>

    </div>
  </div>
</template>