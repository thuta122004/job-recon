<script setup>
import { reactive, watch, ref } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    educationData: Object,
    profileId: [String, Number],
    loading: Boolean
});

const emit = defineEmits(['close', 'save']);

const isCurrentlyStudying = ref(false);

const form = reactive({
    id: null,
    job_seeker_profile_id: '',
    institution: '',
    qualification_name: '',
    field_of_study: '',
    education_level: 'BACHELOR',
    start_year: new Date().getFullYear(),
    end_year: '',
    description: ''
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.educationData) {
            Object.assign(form, { 
                ...props.educationData,
                start_year: Number(props.educationData.start_year),
                end_year: props.educationData.end_year ? Number(props.educationData.end_year) : ''
            });
            isCurrentlyStudying.value = !props.educationData.end_year;
        } else {
            Object.assign(form, {
                id: null,
                job_seeker_profile_id: props.profileId,
                institution: '',
                qualification_name: '',
                field_of_study: '',
                education_level: 'BACHELOR',
                start_year: new Date().getFullYear(),
                end_year: '',
                description: ''
            });
            isCurrentlyStudying.value = false;
        }
    }
});

const handleSave = () => {
    const payload = { 
        ...form,
        job_seeker_profile_id: props.profileId
    };
    
    if (isCurrentlyStudying.value) {
        payload.end_year = null;
    }
    
    emit('save', payload);
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
        <div @click="$emit('close')" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden border border-gray-100 animate-in zoom-in-95 duration-200">
            <div class="px-8 py-4 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
                <div>
                    <h3 class="font-black text-gray-900 text-xl tracking-tight">
                        {{ educationData ? 'Update Education' : 'Add Education' }}
                    </h3>
                    <p class="text-xs text-gray-500 font-medium mt-0.5 uppercase tracking-widest">Academic Details</p>
                </div>
                <button @click="$emit('close')" class="h-10 w-10 flex items-center justify-center rounded-xl hover:bg-white hover:shadow-sm text-gray-400 hover:text-indigo-600 transition-all">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-8 space-y-5 max-h-[70vh] overflow-y-auto custom-scrollbar">
                <div class="grid grid-cols-1 gap-5">
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Institution / University</label>
                        <input type="text" v-model="form.institution" placeholder="University of Yangon"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Qualification / Degree Name</label>
                        <input type="text" v-model="form.qualification_name" placeholder="Bachelor of Computer Science"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Field of Study</label>
                        <input type="text" v-model="form.field_of_study" placeholder="Software Engineering"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Education Level</label>
                        <select v-model="form.education_level"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all appearance-none cursor-pointer">
                            <option value="CERTIFICATE">Certificate</option>
                            <option value="DIPLOMA">Diploma</option>
                            <option value="BACHELOR">Bachelor</option>
                            <option value="MASTER">Master</option>
                            <option value="PHD">PHD</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Start Year</label>
                        <input type="number" v-model.number="form.start_year" placeholder="2020"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">End Year</label>
                        <input type="number" v-model.number="form.end_year" :disabled="isCurrentlyStudying"
                            placeholder="2027"
                            :class="isCurrentlyStudying ? 'opacity-30 grayscale cursor-not-allowed' : 'bg-gray-50'"
                            class="w-full px-4 py-3 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all" />
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100/50">
                    <input type="checkbox" v-model="isCurrentlyStudying" id="current_edu" class="w-5 h-5 rounded-lg border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="current_edu" class="text-sm font-bold text-indigo-900 cursor-pointer">I am currently studying here</label>
                </div>

                <div class="space-y-2">
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.1em] ml-1">Description (Optional)</label>
                    <textarea v-model="form.description" rows="3" placeholder="Honors, activities, or specific focus..."
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all resize-none"></textarea>
                </div>
            </div>

            <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-50 flex justify-end gap-3">
                <button @click="$emit('close')" 
                    class="px-6 py-3 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                    Cancel
                </button>
                <button @click="handleSave" :disabled="loading"
                    class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 text-white px-8 py-3 rounded-2xl text-sm font-bold shadow-xl shadow-indigo-100 transition-all flex items-center gap-2 active:scale-95">
                    <i v-if="loading" class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                    {{ educationData ? 'Update Education' : 'Add Education' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>