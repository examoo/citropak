<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    label: {
        type: String,
        default: ''
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
    min: {
        type: String,
        default: ''
    },
    max: {
        type: String,
        default: ''
    },
    id: {
        type: String,
        default: () => `date-${Math.random().toString(36).substr(2, 9)}`
    }
});

const emit = defineEmits(['update:modelValue']);

const inputClasses = computed(() => {
    const base = 'w-full px-4 py-3 rounded-xl border transition-all duration-200 bg-white/50 backdrop-blur-sm focus:outline-none focus:ring-2';
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
        
        <input
            :id="id"
            type="date"
            :value="modelValue"
            :min="min"
            :max="max"
            :disabled="disabled"
            :required="required"
            :class="inputClasses"
            @input="emit('update:modelValue', $event.target.value)"
        />
        
        <p v-if="error" class="text-sm text-red-500 flex items-center gap-1">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ error }}
        </p>
    </div>
</template>
