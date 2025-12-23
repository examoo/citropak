<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    category: {
        type: Object,
        default: null
    },
    distributions: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'saved']);

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const isEditing = computed(() => !!props.category);

const form = useForm({
    name: '',
    status: 'active',
    distribution_id: null
});

// Reset form when modal opens/closes
watch(() => props.show, (newValue) => {
    if (newValue) {
        if (props.category) {
            form.name = props.category.name;
            form.status = props.category.status;
            form.distribution_id = props.category.distribution_id;
        } else {
            form.reset();
            form.status = 'active';
            form.distribution_id = currentDistribution.value?.id || null;
        }
    }
});

const closeModal = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const validateForm = () => {
    form.clearErrors();
    let isValid = true;

    if (!form.name) {
        form.setError('name', 'The name field is required.');
        isValid = false;
    }

    return isValid;
};

const submit = () => {
    if (!validateForm()) return;

    if (isEditing.value) {
        form.put(route('categories.update', props.category.id), {
            onSuccess: () => {
                emit('saved', form.name);
                closeModal();
            },
        });
    } else {
        form.post(route('categories.store'), {
            onSuccess: () => {
                emit('saved', form.name);
                closeModal();
            },
        });
    }
};
</script>

<template>
    <Modal :show="show" @close="closeModal" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">
                {{ isEditing ? 'Edit' : 'Add New' }} Category
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Distribution Select (Only if Global View and Creating) -->
                <div v-if="!currentDistribution?.id && !isEditing">
                    <InputLabel value="Distribution (Optional)" />
                    <select 
                        v-model="form.distribution_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option :value="null">Global (All Distributions)</option>
                        <option v-for="dist in distributions" :key="dist.id" :value="dist.id">
                            {{ dist.name }} ({{ dist.code }})
                        </option>
                    </select>
                    <div v-if="form.errors.distribution_id" class="text-xs text-red-600 mt-1">{{ form.errors.distribution_id }}</div>
                </div>

                <div>
                    <InputLabel value="Category Name" />
                    <TextInput 
                        v-model="form.name" 
                        type="text" 
                        class="mt-1 block w-full" 
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.name }"
                        placeholder="e.g. Electronics, Clothing"
                    />
                    <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                    <InputLabel value="Status" />
                    <select 
                        v-model="form.status" 
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                    <PrimaryButton 
                        :disabled="form.processing"
                        class="bg-gradient-to-r from-violet-600 to-purple-600 border-0"
                    >
                        {{ form.processing ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
