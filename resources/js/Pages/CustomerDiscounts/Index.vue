<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    customers: {
        type: Array,
        required: true
    }
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const selectedCustomerId = ref(null);
const loading = ref(false);
const saving = ref(false);
const customer = ref(null);
const brands = ref([]);

const customerOptions = computed(() => {
    return props.customers.map(c => ({
        id: c.id,
        value: `${c.shop_name} (${c.customer_code || 'N/A'})`
    }));
});

const loadDiscounts = async () => {
    if (!selectedCustomerId.value) {
        customer.value = null;
        brands.value = [];
        return;
    }
    
    loading.value = true;
    try {
        const response = await axios.get(route('customers.discounts', selectedCustomerId.value));
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
    if (!selectedCustomerId.value) return;
    
    saving.value = true;
    try {
        const discounts = brands.value.map(b => ({
            brand_id: b.brand_id,
            percentage: parseFloat(b.percentage) || 0
        }));
        
        await axios.post(route('customers.save-discounts', selectedCustomerId.value), {
            discounts: discounts
        });
        
        Swal.fire({
            title: 'Success',
            text: 'Discounts saved successfully',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        });
    } catch (error) {
        console.error('Failed to save discounts:', error);
        Swal.fire('Error', 'Failed to save discounts', 'error');
    } finally {
        saving.value = false;
    }
};

watch(selectedCustomerId, () => {
    loadDiscounts();
});
</script>

<template>
    <Head title="Customer Brand Discounts" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Customer Brand Discounts</h1>
                    <p class="text-gray-500 mt-1">Set discount percentages for each brand per customer</p>
                </div>
            </div>

            <!-- Customer Selection -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="max-w-md">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Customer</label>
                    <SearchableSelect
                        v-model="selectedCustomerId"
                        :options="customerOptions"
                        placeholder="Search and select a customer..."
                        :disabled="loading"
                    />
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12">
                <div class="flex items-center justify-center">
                    <svg class="animate-spin h-8 w-8 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-3 text-gray-600">Loading brands...</span>
                </div>
            </div>

            <!-- Brands List -->
            <div v-else-if="customer && brands.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Customer Info Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-emerald-500 to-teal-500">
                    <h3 class="text-lg font-semibold text-white">{{ customer.shop_name }}</h3>
                    <p class="text-emerald-100 text-sm">{{ customer.customer_code || 'No Code' }}</p>
                </div>

                <!-- Brands Table -->
                <div class="p-6">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
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
                                    class="w-20 px-3 py-2 text-right border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    placeholder="0.00"
                                />
                                <span class="text-gray-500 font-medium">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="mt-6 flex justify-end">
                        <PrimaryButton 
                            @click="saveDiscounts" 
                            :disabled="saving"
                            class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 px-6"
                        >
                            <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ saving ? 'Saving...' : 'Save Discounts' }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="!customer" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12">
                <div class="flex flex-col items-center gap-4 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <p class="font-medium text-gray-600">Select a customer to manage brand discounts</p>
                    <p class="text-sm text-gray-400">Choose a customer from the dropdown above to view and edit their brand discount percentages.</p>
                </div>
            </div>

            <!-- No Brands -->
            <div v-else-if="brands.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12">
                <div class="flex flex-col items-center gap-4 text-center">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <p class="font-medium text-gray-600">No active brands found</p>
                    <p class="text-sm text-gray-400">Please add some brands in the Product Management section first.</p>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
