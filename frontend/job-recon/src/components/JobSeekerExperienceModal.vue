<script setup>
import { reactive, watch, ref } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    experienceData: Object,
    profileId: [String, Number],
    loading: Boolean
});

const emit = defineEmits(['close', 'save']);

const isCurrentWork = ref(false);

const form = reactive({
    id: null,
    job_seeker_profile_id: '',
    job_title: '',
    company_name: '',
    location: '',
    employment_type: 'FULL-TIME',
    start_date: '',
    end_date: '',
    description: ''
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.experienceData) {
            Object.assign(form, { ...props.experienceData });
            isCurrentWork.value = !props.experienceData.end_date;
        } else {
            Object.assign(form, {
                id: null,
                job_seeker_profile_id: props.profileId,
                job_title: '',
                company_name: '',
                location: '',   
                employment_type: 'FULL-TIME',
                start_date: '',
                end_date: '',
                description: ''
            });
            isCurrentWork.value = false;
        }
    }
});

const handleSave = () => {
    const payload = { 
        ...form,
        job_seeker_profile_id: props.profileId
    };
    
    if (isCurrentWork.value) {
        payload.end_date = null;
    }
    
    emit('save', payload);
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
        <div @click="$emit('close')" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden border border-gray-100 animate-in zoom-in-95 duration-200">
            <div class="px-8 py-3 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
                <div>
                    <h3 class="font-black text-gray-900 text-xl tracking-tight">
                        {{ experienceData ? 'Update Experience' : 'Add Experience' }}
                    </h3>
                    <p class="text-xs text-gray-500 font-medium mt-0.5 uppercase tracking-widest">Chronological record of career milestones and achievements.</p>
                </div>
                <button @click="$emit('close')" class="h-10 w-10 flex items-center justify-center rounded-xl hover:bg-white hover:shadow-sm text-gray-400 hover:text-indigo-600 transition-all">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 space-y-6">
                <div class="grid grid-cols-1 gap-5">
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Job Title</label>
                        <input type="text" v-model="form.job_title" placeholder="Instructor"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Company Name</label>
                        <input type="text" v-model="form.company_name" placeholder="KMD College"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Location</label>
                        <input type="text" v-model="form.location" placeholder="City, Country"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Employment Type</label>
                        <select v-model="form.employment_type"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all appearance-none cursor-pointer">
                            <option value="FULL-TIME">Full-Time</option>
                            <option value="PART-TIME">Part-Time</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Start Date</label>
                        <input type="date" v-model="form.start_date"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">End Date</label>
                        <input type="date" v-model="form.end_date" :disabled="isCurrentWork"
                            :class="isCurrentWork ? 'opacity-30 grayscale cursor-not-allowed' : 'bg-gray-50'"
                            class="w-full px-4 py-3 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all" />
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100/50">
                    <input type="checkbox" v-model="isCurrentWork" id="current_job" class="w-5 h-5 rounded-lg border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="current_job" class="text-sm font-bold text-indigo-900 cursor-pointer">I am currently working in this role</label>
                </div>

                <div class="space-y-2">
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Key Responsibilities</label>
                    <textarea v-model="form.description" rows="4" placeholder="Describe your achievements..."
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all resize-none"></textarea>
                </div>
            </div>

            <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-50 flex justify-end gap-3">
                <button @click="$emit('close')" 
                    class="px-6 py-3 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                    Discard
                </button>
                <button @click="handleSave" :disabled="loading"
                    class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 text-white px-8 py-3 rounded-2xl text-sm font-bold shadow-xl shadow-indigo-100 transition-all flex items-center gap-2 active:scale-95">
                    <i v-if="loading" class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                    {{ experienceData ? 'Update History' : 'Save Experience' }}
                </button>
            </div>
        </div>
    </div>
</template>