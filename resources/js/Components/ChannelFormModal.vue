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
    channel: {
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

const isEditing = computed(() => !!props.channel);

const form = useForm({
    name: '',
    status: 'active',
    atl: true,
    adv_tax_percent: '0.00',
    distribution_id: ''
});

// Reset form when modal opens/closes or channel changes
watch(() => props.show, (newValue) => {
    if (newValue) {
        if (props.channel) {
            form.name = props.channel.name;
            form.status = props.channel.status;
            form.atl = props.channel.atl;
            form.adv_tax_percent = props.channel.adv_tax_percent || '0.00';
            form.distribution_id = props.channel.distribution_id || '';
        } else {
            form.reset();
            form.status = 'active';
            form.atl = true;
            form.adv_tax_percent = '0.00';
            form.distribution_id = currentDistribution.value?.id || '';
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
        form.put(route('channels.update', props.channel.id), {
            onSuccess: () => {
                emit('saved', form.name);
                closeModal();
            },
        });
    } else {
        form.post(route('channels.store'), {
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
                {{ isEditing ? 'Edit' : 'Add New' }} Channel
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Distribution Select (Only if Global View and Creating) -->
                <div v-if="!currentDistribution?.id && !isEditing">
                    <SearchableSelect 
                        v-model="form.distribution_id"
                        label="Distribution (Optional)"
                        :options="distributions"
                        option-value="id"
                        option-label="name"
                        placeholder="Global (All Distributions)"
                        :error="form.errors.distribution_id"
                    />
                </div>

                <div>
                    <InputLabel value="Channel Name" />
                    <TextInput 
                        v-model="form.name" 
                        type="text" 
                        class="mt-1 block w-full" 
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.name }"
                        placeholder="e.g. Retail, Wholesale"
                    />
                    <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="ATL (Active Taxpayer)" />
                        <select 
                            v-model="form.atl" 
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option :value="true">Yes (Active)</option>
                            <option :value="false">No (Inactive)</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Advance Tax %" />
                        <TextInput 
                            v-model="form.adv_tax_percent" 
                            type="number" 
                            step="0.01"
                            min="0"
                            max="100"
                            class="mt-1 block w-full" 
                            placeholder="0.00"
                        />
                    </div>
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
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 border-0"
                    >
                        {{ form.processing ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
