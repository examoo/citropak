<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { watch, ref, computed } from 'vue';

const props = defineProps({
    show: Boolean,
    item: Object,
    customers: {
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
    shelf_code: '',
    status: 'active',
    customer_id: '',
    rent_amount: '',
    contract_months: '',
    start_date: '',
    incentive_amount: '',
    order_booker_id: ''
});

const isEditing = ref(false);

// Calculate total amount
const totalAmount = computed(() => {
    const rent = parseFloat(form.rent_amount) || 0;
    const months = parseInt(form.contract_months) || 0;
    return rent * months;
});

watch(() => props.item, (newItem) => {
    if (newItem) {
        isEditing.value = true;
        form.name = newItem.name;
        form.shelf_code = newItem.shelf_code || '';
        form.status = newItem.status;
        form.customer_id = newItem.customer_id || '';
        form.rent_amount = newItem.rent_amount || '';
        form.contract_months = newItem.contract_months || '';
        form.start_date = newItem.start_date ? newItem.start_date.split('T')[0] : '';
        form.incentive_amount = newItem.incentive_amount || '';
        form.order_booker_id = newItem.order_booker_id || '';
    } else {
        isEditing.value = false;
        form.reset();
        form.status = 'active';
    }
}, { immediate: true });

watch(() => props.show, (newShow) => {
    if (newShow && !props.item) {
        isEditing.value = false;
        form.reset();
        form.status = 'active';
    }
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('shelves.update', props.item.id), {
            preserveScroll: false,
            onSuccess: () => {
                form.reset();
                emit('saved', props.item);
                emit('close');
            },
        });
    } else {
        form.post(route('shelves.store'), {
            preserveScroll: false,
            onSuccess: () => {
                form.reset();
                emit('saved', null);
                emit('close');
            },
        });
    }
};

const close = () => {
    emit('close');
    form.clearErrors();
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK').format(value || 0);
};
</script>

<template>
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                {{ isEditing ? 'Edit Shelf' : 'Add New Shelf' }}
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Shelf Code -->
                    <div>
                        <InputLabel value="Shelf Code" />
                        <TextInput 
                            v-model="form.shelf_code" 
                            type="text" 
                            class="mt-1 block w-full" 
                            placeholder="Auto-generated"
                        />
                        <p class="text-xs text-gray-500 mt-1">Leave empty for auto-generation</p>
                        <div v-if="form.errors.shelf_code" class="text-xs text-red-600 mt-1">{{ form.errors.shelf_code }}</div>
                    </div>

                    <!-- Name -->
                    <div>
                        <InputLabel value="Name" />
                        <TextInput 
                            v-model="form.name" 
                            type="text" 
                            class="mt-1 block w-full" 
                            placeholder="e.g. Display Rack"
                            required
                        />
                        <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
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

                    <!-- Customer -->
                    <div>
                        <InputLabel value="Customer" />
                        <select 
                            v-model="form.customer_id" 
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="">Select Customer</option>
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

                    <!-- Start Date -->
                    <div>
                        <InputLabel value="Start Date" />
                        <input 
                            v-model="form.start_date" 
                            type="date" 
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        />
                        <div v-if="form.errors.start_date" class="text-xs text-red-600 mt-1">{{ form.errors.start_date }}</div>
                    </div>
                </div>

                <!-- Rent Details Section -->
                <div class="border-t pt-4 mt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Rent Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Rent Amount P.M. -->
                        <div>
                            <InputLabel value="Rent Amount (P.M.)" />
                            <TextInput 
                                v-model="form.rent_amount" 
                                type="number" 
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full" 
                                placeholder="0.00"
                            />
                            <div v-if="form.errors.rent_amount" class="text-xs text-red-600 mt-1">{{ form.errors.rent_amount }}</div>
                        </div>

                        <!-- Contract Months -->
                        <div>
                            <InputLabel value="Contract Months" />
                            <TextInput 
                                v-model="form.contract_months" 
                                type="number" 
                                min="1"
                                max="120"
                                class="mt-1 block w-full" 
                                placeholder="12"
                            />
                            <div v-if="form.errors.contract_months" class="text-xs text-red-600 mt-1">{{ form.errors.contract_months }}</div>
                        </div>

                        <!-- Total Amount (Calculated) -->
                        <div>
                            <InputLabel value="Total Amount" />
                            <div class="mt-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-700 font-medium">
                                Rs. {{ formatCurrency(totalAmount) }}
                            </div>
                        </div>

                        <!-- Incentive Amount -->
                        <div>
                            <InputLabel value="Incentive Amount" />
                            <TextInput 
                                v-model="form.incentive_amount" 
                                type="number" 
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full" 
                                placeholder="0.00"
                            />
                            <div v-if="form.errors.incentive_amount" class="text-xs text-red-600 mt-1">{{ form.errors.incentive_amount }}</div>
                        </div>
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
