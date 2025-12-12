<script setup>
import { computed } from 'vue';

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
    },
    id: {
        type: String,
        default: () => `select-${Math.random().toString(36).substr(2, 9)}`
    }
});

const emit = defineEmits(['update:modelValue']);

const selectClasses = computed(() => {
    const base = 'w-full px-4 py-3 rounded-xl border transition-all duration-200 bg-white/50 backdrop-blur-sm focus:outline-none focus:ring-2 appearance-none cursor-pointer';
    const normal = 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-500/20';
    const errorState = 'border-red-400 focus:border-red-500 focus:ring-red-500/20';
    const disabledState = 'bg-gray-100 cursor-not-allowed opacity-60';
    
    return `${base} ${props.error ? errorState : normal} ${props.disabled ? disabledState : ''}`;
});
</script>

<template>
    <div class="space-y-1.5">
        <label 
            v-if="label" 
            :for="id"
            class="block text-sm font-medium text-gray-700"
        >
            {{ label }}
            <span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>
        
        <div class="relative">
            <select
                :id="id"
                :value="modelValue"
                :disabled="disabled"
                :required="required"
                :class="selectClasses"
                @change="emit('update:modelValue', $event.target.value)"
            >
                <option value="" disabled>{{ placeholder }}</option>
                <option
                    v-for="option in options"
                    :key="option[optionValue] ?? option"
                    :value="option[optionValue] ?? option"
                >
                    {{ option[optionLabel] ?? option }}
                </option>
            </select>
            
            <!-- Dropdown Arrow -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
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
