<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { watch, ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    item: Object,
    customers: {
        type: Array,
        default: () => []
    },
    chillerTypes: {
        type: Array,
        default: () => []
    },
    orderBookers: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name: '',
    chiller_code: '',
    chiller_type_id: '',
    status: 'active',
    customer_id: '',
    order_booker_id: ''
});

const isEditing = ref(false);

// Inline Chiller Type Creation
const showNewTypeInput = ref(false);
const newTypeName = ref('');
const creatingType = ref(false);
const localChillerTypes = ref([...props.chillerTypes]);

// Watch for props changes to update local list
watch(() => props.chillerTypes, (newTypes) => {
    localChillerTypes.value = [...newTypes];
}, { immediate: true });

const handleTypeChange = (event) => {
    if (event.target.value === 'add_new') {
        showNewTypeInput.value = true;
        form.chiller_type_id = '';
    } else {
        showNewTypeInput.value = false;
        form.chiller_type_id = event.target.value;
    }
};

const createNewType = async () => {
    if (!newTypeName.value.trim()) return;
    
    creatingType.value = true;
    try {
        const response = await axios.post(route('chiller-types.store'), {
            name: newTypeName.value.trim(),
            status: 'active'
        });
        
        // Add to local list
        const newType = { id: response.data.id, name: newTypeName.value.trim() };
        localChillerTypes.value.push(newType);
        
        // Select the new type
        form.chiller_type_id = response.data.id;
        
        // Reset
        newTypeName.value = '';
        showNewTypeInput.value = false;
    } catch (error) {
        console.error('Error creating chiller type:', error);
        alert(error.response?.data?.message || 'Failed to create chiller type');
    } finally {
        creatingType.value = false;
    }
};

const cancelNewType = () => {
    showNewTypeInput.value = false;
    newTypeName.value = '';
};

watch(() => props.item, (newItem) => {
    if (newItem) {
        isEditing.value = true;
        form.name = newItem.name;
        form.chiller_code = newItem.chiller_code || '';
        form.chiller_type_id = newItem.chiller_type_id || '';
        form.status = newItem.status;
        form.customer_id = newItem.customer_id || '';
        form.order_booker_id = newItem.order_booker_id || '';
    } else {
        isEditing.value = false;
        form.reset();
        form.status = 'active';
    }
    showNewTypeInput.value = false;
    newTypeName.value = '';
}, { immediate: true });

watch(() => props.show, (newShow) => {
    if (newShow && !props.item) {
        isEditing.value = false;
        form.reset();
        form.status = 'active';
        showNewTypeInput.value = false;
        newTypeName.value = '';
    }
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('chillers.update', props.item.id), {
            preserveScroll: false,
            onSuccess: () => {
                form.reset();
                emit('saved', props.item);
                emit('close');
            },
            onError: (errors) => {
                console.log('Validation errors:', errors);
            }
        });
    } else {
        form.post(route('chillers.store'), {
            preserveScroll: false,
            onSuccess: () => {
                form.reset();
                emit('saved', null);
                emit('close');
            },
            onError: (errors) => {
                console.log('Validation errors:', errors);
            }
        });
    }
};

const close = () => {
    emit('close');
    form.clearErrors();
    showNewTypeInput.value = false;
    newTypeName.value = '';
};
</script>

<template>
    <Modal :show="show" @close="close" max-width="lg">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                {{ isEditing ? 'Edit Chiller' : 'Add New Chiller' }}
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Chiller Code -->
                    <div>
                        <InputLabel value="Chiller Code" />
                        <TextInput 
                            v-model="form.chiller_code" 
                            type="text" 
                            class="mt-1 block w-full" 
                            placeholder="Auto-generated if empty"
                        />
                        <p class="text-xs text-gray-500 mt-1">Leave empty for auto-generation</p>
                        <div v-if="form.errors.chiller_code" class="text-xs text-red-600 mt-1">{{ form.errors.chiller_code }}</div>
                    </div>

                    <!-- Name -->
                    <div>
                        <InputLabel value="Name" />
                        <TextInput 
                            v-model="form.name" 
                            type="text" 
                            class="mt-1 block w-full" 
                            placeholder="e.g. Main Freezer"
                            required
                        />
                        <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Chiller Type with Inline Add -->
                    <div>
                        <InputLabel value="Chiller Type" />
                        
                        <!-- Dropdown or New Type Input -->
                        <div v-if="!showNewTypeInput">
                            <select 
                                :value="form.chiller_type_id"
                                @change="handleTypeChange"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="">Select Type</option>
                                <option v-for="type in localChillerTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                                <option value="add_new" class="text-indigo-600 font-medium">+ Add New Type</option>
                            </select>
                        </div>
                        
                        <!-- New Type Input -->
                        <div v-else class="mt-1 space-y-2">
                            <div class="flex gap-2">
                                <TextInput 
                                    v-model="newTypeName" 
                                    type="text" 
                                    class="block w-full" 
                                    placeholder="Enter new type name..."
                                    @keyup.enter="createNewType"
                                />
                            </div>
                            <div class="flex gap-2">
                                <button 
                                    type="button"
                                    @click="createNewType"
                                    :disabled="creatingType || !newTypeName.trim()"
                                    class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                                >
                                    {{ creatingType ? 'Saving...' : 'Save Type' }}
                                </button>
                                <button 
                                    type="button"
                                    @click="cancelNewType"
                                    class="px-3 py-1 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                        
                        <div v-if="form.errors.chiller_type_id" class="text-xs text-red-600 mt-1">{{ form.errors.chiller_type_id }}</div>
                    </div>

                    <!-- Status -->
                    <div>
                        <InputLabel value="Status" />
                        <select 
                            v-model="form.status" 
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div v-if="form.errors.status" class="text-xs text-red-600 mt-1">{{ form.errors.status }}</div>
                    </div>

                    <!-- Assign to Customer -->
                    <div>
                        <InputLabel value="Assign to Customer" />
                        <select 
                            v-model="form.customer_id" 
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="">None (Unassigned)</option>
                            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                {{ customer.name }} ({{ customer.code }})
                            </option>
                        </select>
                        <div v-if="form.errors.customer_id" class="text-xs text-red-600 mt-1">{{ form.errors.customer_id }}</div>
                    </div>

                    <!-- Order Booker -->
                    <div>
                        <InputLabel value="Order Booker" />
                        <select 
                            v-model="form.order_booker_id" 
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="">Select Order Booker</option>
                            <option v-for="booker in orderBookers" :key="booker.id" :value="booker.id">
                                {{ booker.name }} ({{ booker.code }})
                            </option>
                        </select>
                        <div v-if="form.errors.order_booker_id" class="text-xs text-red-600 mt-1">{{ form.errors.order_booker_id }}</div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <SecondaryButton @click="close">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
