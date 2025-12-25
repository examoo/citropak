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
    subAddress: {
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

const isEditing = computed(() => !!props.subAddress);

const form = useForm({
    name: '',
    status: 'active',
    distribution_id: ''
});

// Reset form when modal opens/closes
watch(() => props.show, (newValue) => {
    if (newValue) {
        if (props.subAddress) {
            form.name = props.subAddress.name;
            form.status = props.subAddress.status;
            form.distribution_id = props.subAddress.distribution_id || '';
        } else {
            form.reset();
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
        form.put(route('sub-addresses.update', props.subAddress.id), {
            onSuccess: () => {
                emit('saved', form.name);
                closeModal();
            },
        });
    } else {
        form.post(route('sub-addresses.store'), {
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
                {{ isEditing ? 'Edit' : 'Add New' }} Sub Address
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Distribution Select (Only if Global View and Creating) -->
                <div v-if="!currentDistribution?.id && !isEditing">
                    <SearchableSelect 
                        v-model="form.distribution_id"
                        label="Distribution (Optional)"
                        :options="distributions"
                        option-value="id"
                        option-label="value"
                        placeholder="Global (All Distributions)"
                        :error="form.errors.distribution_id"
                    />
                </div>

                <div>
                    <InputLabel value="Sub Address Name" />
                    <TextInput 
                        v-model="form.name" 
                        type="text" 
                        class="mt-1 block w-full" 
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.name }"
                        placeholder="e.g. Block A, Sector 5"
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
                        class="bg-gradient-to-r from-amber-600 to-orange-600 border-0"
                    >
                        {{ form.processing ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
