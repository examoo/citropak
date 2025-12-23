<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    options: {
        type: Array,
        default: () => []
    },
    optionValue: {
        type: String,
        default: 'id'
    },
    optionLabel: {
        type: String,
        default: 'name'
    },
    placeholder: {
        type: String,
        default: 'Select an option'
    },
    error: {
        type: String,
        default: ''
    },
    required: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const search = ref('');
const containerRef = ref(null);

const filteredOptions = computed(() => {
    if (!search.value) return props.options;
    const query = search.value.toLowerCase();
    return props.options.filter(option => 
        String(option[props.optionLabel] || option).toLowerCase().includes(query)
    );
});

const selectedOption = computed(() => {
    return props.options.find(option => 
        (option[props.optionValue] ?? option) == props.modelValue
    );
});

// Update search text when modelValue changes externally
watch(() => props.modelValue, () => {
    if (!props.modelValue) {
        search.value = '';
    }
});

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        // Focus search input roughly when opening? (Handled by binding to input usually)
    }
};

const selectOption = (option) => {
    emit('update:modelValue', option[props.optionValue] ?? option);
    isOpen.value = false;
    search.value = ''; // Reset search on select
};

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="space-y-1.5" ref="containerRef">
        <label 
            v-if="label" 
            class="block text-sm font-medium text-gray-700"
        >
            {{ label }}
            <span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>
        
        <div class="relative">
            <!-- Trigger Button -->
            <div 
                @click="toggleDropdown"
                class="w-full px-4 py-3 rounded-xl border transition-all duration-200 bg-white/50 backdrop-blur-sm focus:outline-none focus:ring-2 cursor-pointer flex items-center justify-between"
                :class="[
                    error ? 'border-red-400 focus:ring-red-500/20' : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-500/20',
                    disabled ? 'bg-gray-100 cursor-not-allowed opacity-60' : 'hover:border-indigo-300'
                ]"
            >
                <span :class="selectedOption ? 'text-gray-900' : 'text-gray-500'">
                    {{ selectedOption ? (selectedOption[optionLabel] ?? selectedOption) : placeholder }}
                </span>
                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <!-- Dropdown Menu -->
            <div 
                v-if="isOpen"
                class="absolute z-50 w-full mt-2 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden max-h-60 flex flex-col"
            >
                <!-- Search Box -->
                <div class="p-2 border-b border-gray-100 bg-gray-50/50 sticky top-0">
                    <input 
                        v-model="search"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Search..."
                        @click.stop
                    >
                </div>

                <!-- Options List -->
                <div class="overflow-y-auto flex-1">
                    <div 
                        v-for="option in filteredOptions"
                        :key="option[optionValue] ?? option"
                        @click="selectOption(option)"
                        class="px-4 py-2.5 text-sm hover:bg-indigo-50 hover:text-indigo-700 cursor-pointer transition-colors"
                        :class="{'bg-indigo-50 text-indigo-700 font-medium': (option[optionValue] ?? option) == modelValue}"
                    >
                        {{ option[optionLabel] ?? option }}
                    </div>
                    <div v-if="filteredOptions.length === 0" class="px-4 py-3 text-sm text-gray-500 text-center italic">
                        No results found
                    </div>
                </div>
            </div>
        </div>
        
        <p v-if="error" class="text-sm text-red-500 flex items-center gap-1">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ error }}
        </p>
    </div>
</template>
