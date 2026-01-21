<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    customerId: {
        type: Number,
        default: null
    }
});

const emit = defineEmits(['close']);

const loading = ref(false);
const saving = ref(false);
const customer = ref(null);
const brands = ref([]);

const loadDiscounts = async () => {
    if (!props.customerId) return;
    
    loading.value = true;
    try {
        const response = await axios.get(route('customers.discounts', props.customerId));
        customer.value = response.data.customer;
        brands.value = response.data.brands;
    } catch (error) {
        console.error('Failed to load discounts:', error);
        Swal.fire('Error', 'Failed to load brand discounts', 'error');
    } finally {
        loading.value = false;
    }
};

const saveDiscounts = async () => {
    saving.value = true;
    try {
        const discounts = brands.value.map(b => ({
            brand_id: b.brand_id,
            percentage: parseFloat(b.percentage) || 0
        }));
        
        await axios.post(route('customers.save-discounts', props.customerId), {
            discounts: discounts
        });
        
        Swal.fire({
            title: 'Success',
            text: 'Discounts saved successfully',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        });
        
        emit('close');
    } catch (error) {
        console.error('Failed to save discounts:', error);
        Swal.fire('Error', 'Failed to save discounts', 'error');
    } finally {
        saving.value = false;
    }
};

const close = () => {
    emit('close');
};

watch(() => props.show, (newVal) => {
    if (newVal && props.customerId) {
        loadDiscounts();
    }
});
</script>

<template>
    <Modal :show="show" max-width="3xl" @close="close">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Brand Discounts</h2>
                    <p v-if="customer" class="text-gray-500 mt-1">
                        {{ customer.shop_name }} 
                        <span v-if="customer.customer_code" class="text-gray-400">({{ customer.customer_code }})</span>
                    </p>
                </div>
                <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-3 text-gray-600">Loading brands...</span>
            </div>

            <!-- Brands List -->
            <div v-else class="max-h-[60vh] overflow-y-auto">
                <div v-if="brands.length === 0" class="text-center py-8 text-gray-500">
                    No active brands found.
                </div>
                
                <div v-else class="space-y-3">
                    <div 
                        v-for="brand in brands" 
                        :key="brand.brand_id"
                        class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                {{ brand.brand_name.charAt(0).toUpperCase() }}
                            </div>
                            <span class="font-medium text-gray-900">{{ brand.brand_name }}</span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <input 
                                v-model="brand.percentage"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                class="w-24 px-3 py-2 text-right border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="0.00"
                            />
                            <span class="text-gray-500 font-medium">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
                <SecondaryButton @click="close" :disabled="saving">
                    Cancel
                </SecondaryButton>
                <PrimaryButton 
                    @click="saveDiscounts" 
                    :disabled="loading || saving"
                    class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700"
                >
                    <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ saving ? 'Saving...' : 'Save Discounts' }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
