<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { watch, computed } from 'vue';

const props = defineProps({
    show: Boolean,
    item: Object,
    action: {
        type: String,
        default: 'move' // 'move' or 'return'
    },
    customers: {
        type: Array,
        default: () => []
    },
    orderBookers: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close']);

const form = useForm({
    customer_id: '',
    order_booker_id: '',
    notes: ''
});

const isReturn = computed(() => props.action === 'return');

const modalTitle = computed(() => {
    if (isReturn.value) {
        return 'Return Chiller';
    }
    return props.item?.customer_id ? 'Move Chiller' : 'Assign Chiller';
});

watch(() => props.show, (newShow) => {
    if (newShow) {
        form.reset();
        form.order_booker_id = props.item?.order_booker_id || '';
    }
});

const submit = () => {
    const routeName = isReturn.value ? 'chillers.return' : 'chillers.move';
    
    form.post(route(routeName, props.item.id), {
        onSuccess: () => {
            emit('close');
        },
    });
};

const close = () => {
    emit('close');
    form.clearErrors();
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-2">
                {{ modalTitle }}
            </h2>
            
            <p v-if="item" class="text-sm text-gray-600 mb-4">
                <span class="font-semibold">{{ item.chiller_code || item.name }}</span>
                <span v-if="item.customer"> - Currently at: {{ item.customer.shop_name }}</span>
            </p>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Target Customer (for move/assign only) -->
                <div v-if="!isReturn">
                    <InputLabel value="Move to Customer" />
                    <select 
                        v-model="form.customer_id" 
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        required
                    >
                        <option value="">Select Customer</option>
                        <option 
                            v-for="customer in customers" 
                            :key="customer.id" 
                            :value="customer.id"
                            :disabled="customer.id === item?.customer_id"
                        >
                            {{ customer.name }} ({{ customer.code }})
                        </option>
                    </select>
                    <div v-if="form.errors.customer_id" class="text-xs text-red-600 mt-1">{{ form.errors.customer_id }}</div>
                </div>

                <!-- Return Confirmation (for return action) -->
                <div v-if="isReturn" class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <p class="text-yellow-800">
                        <strong>Confirm Return:</strong> This chiller will be removed from 
                        <span class="font-semibold">{{ item?.customer?.shop_name }}</span> 
                        and marked as unassigned.
                    </p>
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

                <!-- Notes -->
                <div>
                    <InputLabel value="Notes (Optional)" />
                    <textarea 
                        v-model="form.notes" 
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        rows="3"
                        placeholder="Add any notes about this movement..."
                    ></textarea>
                    <div v-if="form.errors.notes" class="text-xs text-red-600 mt-1">{{ form.errors.notes }}</div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <SecondaryButton @click="close">Cancel</SecondaryButton>
                    <PrimaryButton 
                        :disabled="form.processing"
                        :class="isReturn ? 'bg-orange-600 hover:bg-orange-700' : ''"
                    >
                        {{ form.processing ? 'Processing...' : (isReturn ? 'Confirm Return' : 'Confirm Move') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
