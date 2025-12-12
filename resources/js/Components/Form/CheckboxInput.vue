<script setup>
const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    label: {
        type: String,
        default: ''
    },
    description: {
        type: String,
        default: ''
    },
    error: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    id: {
        type: String,
        default: () => `checkbox-${Math.random().toString(36).substr(2, 9)}`
    }
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="space-y-1">
        <div class="flex items-start gap-3">
            <div class="flex items-center h-5">
                <input
                    :id="id"
                    type="checkbox"
                    :checked="modelValue"
                    :disabled="disabled"
                    class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500/20 focus:ring-2 transition-colors duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                    @change="emit('update:modelValue', $event.target.checked)"
                />
            </div>
            <div class="flex flex-col">
                <label 
                    v-if="label" 
                    :for="id"
                    class="text-sm font-medium text-gray-700 cursor-pointer select-none"
                >
                    {{ label }}
                </label>
                <p v-if="description" class="text-sm text-gray-500">
                    {{ description }}
                </p>
            </div>
        </div>
        
        <p v-if="error" class="text-sm text-red-500 flex items-center gap-1 ml-8">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ error }}
        </p>
    </div>
</template>
