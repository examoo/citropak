<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    chillers: Array,
    filters: Object,
    chillerTypes: Array,
    orderBookers: Array,
    customers: Array
});

const filters = ref({
    chiller_type_id: props.filters.chiller_type_id || '',
    order_booker_id: props.filters.order_booker_id || '',
    customer_id: props.filters.customer_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || ''
});

const applyFilters = () => {
    router.get(route('chillers.report'), filters.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const resetFilters = () => {
    filters.value = {
        chiller_type_id: '',
        order_booker_id: '',
        customer_id: '',
        date_from: '',
        date_to: ''
    };
    applyFilters();
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const printReport = () => {
    window.print();
};

// Calculate totals
const totalSale = ref(0);
watch(() => props.chillers, (newChillers) => {
    totalSale.value = newChillers?.reduce((sum, c) => sum + (parseFloat(c.total_sale) || 0), 0) || 0;
}, { immediate: true });
</script>

<template>
    <Head title="Chiller Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Chiller Report</h1>
                    <p class="text-gray-500 mt-1">View chillers with customer details and sales.</p>
                </div>
                <div class="flex gap-2">
                    <Link 
                        :href="route('chillers.index')" 
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                    >
                        Back to Chillers
                    </Link>
                    <button 
                        @click="printReport" 
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                    >
                        Print Report
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 print:hidden">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Chiller Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chiller Type</label>
                        <select 
                            v-model="filters.chiller_type_id"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                        >
                            <option value="">All Types</option>
                            <option v-for="type in chillerTypes" :key="type.id" :value="type.id">
                                {{ type.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Order Booker -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order Booker</label>
                        <select 
                            v-model="filters.order_booker_id"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                        >
                            <option value="">All Order Bookers</option>
                            <option v-for="booker in orderBookers" :key="booker.id" :value="booker.id">
                                {{ booker.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sale Date From</label>
                        <input 
                            v-model="filters.date_from"
                            type="date"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sale Date To</label>
                        <input 
                            v-model="filters.date_to"
                            type="date"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                        />
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-end gap-2">
                        <button 
                            @click="applyFilters"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700"
                        >
                            Apply
                        </button>
                        <button 
                            @click="resetFilters"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden print:shadow-none print:border-none">
                <!-- Print Header -->
                <div class="hidden print:block p-4 border-b">
                    <h1 class="text-xl font-bold text-center">Chiller Report</h1>
                    <p class="text-center text-sm text-gray-600">
                        Generated on {{ new Date().toLocaleDateString() }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chiller Code</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer Code</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer Name</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Booker</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sale</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in chillers" :key="item.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-mono font-medium text-indigo-600">{{ item.chiller_code || '-' }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-mono">{{ item.customer?.customer_code || '-' }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">{{ item.customer?.shop_name || '-' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600">{{ item.customer?.address || '-' }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm text-gray-600">{{ item.order_booker?.name || '-' }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="text-sm font-medium text-green-600">{{ formatCurrency(item.total_sale) }}</span>
                                </td>
                            </tr>
                            <tr v-if="chillers.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                    No chillers found with assigned customers.
                                </td>
                            </tr>
                        </tbody>
                        <!-- Totals Row -->
                        <tfoot v-if="chillers.length > 0" class="bg-gray-100">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-right text-sm font-semibold text-gray-900">
                                    Total Sale:
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-green-700">
                                    {{ formatCurrency(totalSale) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Summary -->
                <div v-if="chillers.length > 0" class="p-4 border-t border-gray-200 bg-gray-50 print:hidden">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Total Chillers: <strong>{{ chillers.length }}</strong></span>
                        <span class="text-gray-600">Total Sale: <strong class="text-green-600">{{ formatCurrency(totalSale) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style>
@media print {
    @page {
        size: landscape;
        margin: 1cm;
    }
    body {
        font-size: 12px;
    }
}
</style>
