<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    subDistribution: {
        type: Object,
        default: null
    },
    distributions: {
        type: Array,
        default: () => []
    },
    parentDistributionId: {
        type: [String, Number],
        default: ''
    }
});

const emit = defineEmits(['close', 'saved']);

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const isEditing = computed(() => !!props.subDistribution);

const form = useForm({
    name: '',
    is_fbr: true,
    status: 'active',
    distribution_id: ''
});

watch(() => props.show, (newValue) => {
    if (newValue) {
        if (props.subDistribution) {
            form.name = props.subDistribution.name;
            form.is_fbr = props.subDistribution.is_fbr ?? true;
            form.status = props.subDistribution.status;
            form.distribution_id = props.subDistribution.distribution_id || '';
        } else {
            form.reset();
            form.is_fbr = true;
            form.status = 'active';
            // Use parent distribution if provided, otherwise use current distribution
            form.distribution_id = props.parentDistributionId || currentDistribution.value?.id || '';
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
        form.put(route('sub-distributions.update', props.subDistribution.id), {
            onSuccess: () => {
                emit('saved', form.name);
                closeModal();
            },
        });
    } else {
        form.post(route('sub-distributions.store'), {
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
                {{ isEditing ? 'Edit' : 'Add New' }} Sub Distribution
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Distribution Select (Global View) -->
                <div v-if="!currentDistribution?.id">
                    <InputLabel value="Distribution (Optional)" />
                    <select 
                        v-model="form.distribution_id" 
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        :disabled="isEditing"
                    >
                        <option value="">Global (All Distributions)</option>
                        <option v-for="dist in distributions" :key="dist.id" :value="dist.id">{{ dist.name }} ({{ dist.code }})</option>
                    </select>
                    <p v-if="isEditing" class="text-xs text-gray-500 mt-1">Distribution cannot be changed after creation.</p>
                    <div v-if="form.errors.distribution_id" class="text-xs text-red-600 mt-1">{{ form.errors.distribution_id }}</div>
                </div>

                <div>
                    <InputLabel value="Sub Distribution Name" />
                    <TextInput 
                        v-model="form.name" 
                        type="text" 
                        class="mt-1 block w-full" 
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.name }"
                        placeholder="e.g. Region A, Zone 1"
                    />
                    <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                </div>

                <!-- FBR Radio Buttons -->
                <div>
                    <InputLabel value="FBR Type" class="mb-2" />
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="radio" 
                                v-model="form.is_fbr" 
                                :value="true"
                                class="w-4 h-4 text-cyan-600 border-gray-300 focus:ring-cyan-500"
                            >
                            <span class="text-sm font-medium text-gray-700">FBR</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="radio" 
                                v-model="form.is_fbr" 
                                :value="false"
                                class="w-4 h-4 text-gray-600 border-gray-300 focus:ring-gray-500"
                            >
                            <span class="text-sm font-medium text-gray-700">Non-FBR</span>
                        </label>
                    </div>
                    <div v-if="form.errors.is_fbr" class="text-xs text-red-600 mt-1">{{ form.errors.is_fbr }}</div>
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
                        class="bg-gradient-to-r from-cyan-600 to-blue-600 border-0"
                    >
                        {{ form.processing ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
