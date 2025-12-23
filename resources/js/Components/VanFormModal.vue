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
    van: {
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

const isEditing = computed(() => !!props.van);

const form = useForm({
    code: '',
    status: 'active',
    distribution_id: null
});

// Reset form when modal opens/closes or van changes
watch(() => props.show, (newValue) => {
    if (newValue) {
        if (props.van) {
            form.code = props.van.code;
            form.status = props.van.status;
            form.distribution_id = props.van.distribution_id;
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

    if (!form.code) {
        form.setError('code', 'The code field is required.');
        isValid = false;
    }

    return isValid;
};

const submit = () => {
    if (!validateForm()) return;

    if (isEditing.value) {
        form.put(route('vans.update', props.van.id), {
            onSuccess: () => {
                emit('saved', form.code);
                closeModal();
            },
        });
    } else {
        form.post(route('vans.store'), {
            onSuccess: () => {
                emit('saved', form.code);
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
                {{ isEditing ? 'Edit' : 'Add New' }} Van
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Distribution Select (Only if Global View and Creating) -->
                <div v-if="!currentDistribution?.id && !isEditing">
                    <InputLabel value="Distribution" />
                    <select 
                        v-model="form.distribution_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        required
                    >
                        <option :value="null" disabled>Select Distribution</option>
                        <option v-for="dist in distributions" :key="dist.id" :value="dist.id">
                            {{ dist.name }} ({{ dist.code }})
                        </option>
                    </select>
                    <div v-if="form.errors.distribution_id" class="text-xs text-red-600 mt-1">{{ form.errors.distribution_id }}</div>
                </div>

                <div>
                    <InputLabel value="Van Code" />
                    <TextInput 
                        v-model="form.code" 
                        type="text" 
                        class="mt-1 block w-full" 
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.code }"
                        placeholder="e.g. VAN001"
                    />
                    <div v-if="form.errors.code" class="text-xs text-red-600 mt-1">{{ form.errors.code }}</div>
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
                        class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0"
                    >
                        {{ form.processing ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
