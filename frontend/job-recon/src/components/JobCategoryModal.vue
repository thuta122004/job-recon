<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    categoryData: Object,
    isEditing: Boolean,
    loading: Boolean
});

const emit = defineEmits(['close', 'save']);

const form = reactive({
    id: null,
    name: '',
    description: '',
    icon_class: 'fa-solid fa-layer-group'
});

watch(() => props.isOpen, (newVal) => {
    if (newVal && !props.isEditing) {
        Object.assign(form, { 
            id: null, 
            name: '', 
            description: '',
            icon_class: 'fa-solid fa-layer-group'
        });
    } 
    else if (newVal && props.isEditing && props.categoryData) {
        Object.assign(form, {
            id: props.categoryData.id,
            name: props.categoryData.name,
            description: props.categoryData.description,
            icon_class: props.categoryData.icon_class
        });
    }
});

const handleSubmit = () => {
    emit('save', { ...form });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
        <div @click="$emit('close')" class="absolute inset-0 bg-indigo-900/20 backdrop-blur-sm transition-opacity"></div>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 overflow-hidden border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">{{ isEditing ? 'Update Category' : 'Create Industry Category' }}</h3>
                    <p class="text-xs text-gray-500">Classify jobs into industry sectors for better filtering.</p>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 p-2 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-8 space-y-8">
                <div class="relative">
                    <input type="text" id="cat_name" v-model="form.name" placeholder=" "
                        class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                    <label for="cat_name" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Category Name</label>
                </div>

                <div class="relative">
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center bg-indigo-50 rounded-lg text-indigo-600 border border-indigo-100 transition-all">
                        <i :class="form.icon_class || 'fa-solid fa-question'"></i>
                    </div>
                    <input type="text" id="cat_icon" v-model="form.icon_class" placeholder=" "
                        class="floating-input block w-full pl-4 pr-16 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all" />
                    <label for="cat_icon" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">FontAwesome Icon Class</label>
                </div>

                <div class="relative">
                    <textarea id="cat_desc" v-model="form.description" rows="3" placeholder=" "
                        class="floating-input block w-full px-4 py-4 text-sm text-gray-900 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none peer appearance-none transition-all resize-none"></textarea>
                    <label for="cat_desc" class="floating-label absolute left-4 transition-all duration-200 pointer-events-none text-gray-400 text-sm">Full Description</label>
                </div>
            </div>

            <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button @click="$emit('close')" 
                    class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                    Discard
                </button>
                <button @click="handleSubmit" :disabled="loading"
                    class="bg-indigo-600 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 active:scale-95 disabled:opacity-50 transition-all flex items-center gap-2">
                    <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
                    {{ isEditing ? 'Save Changes' : 'Confirm & Create' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.floating-input:focus ~ .floating-label,
.floating-input:not(:placeholder-shown) ~ .floating-label {
    top: 0.5rem;
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #6366f1;
}

.floating-label {
    top: 1.15rem;
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>