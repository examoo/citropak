<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    shelves: Array,
    filters: Object,
    orderBookers: Array,
    customers: Array,
    years: Array,
    currentYear: Number
});

const filters = ref({
    order_booker_id: props.filters.order_booker_id || '',
    customer_id: props.filters.customer_id || '',
    year: props.filters.year || props.currentYear
});

const months = [
    { num: 1, name: 'Jan' },
    { num: 2, name: 'Feb' },
    { num: 3, name: 'Mar' },
    { num: 4, name: 'Apr' },
    { num: 5, name: 'May' },
    { num: 6, name: 'Jun' },
    { num: 7, name: 'Jul' },
    { num: 8, name: 'Aug' },
    { num: 9, name: 'Sep' },
    { num: 10, name: 'Oct' },
    { num: 11, name: 'Nov' },
    { num: 12, name: 'Dec' }
];

const applyFilters = () => {
    router.get(route('shelves.report'), filters.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const resetFilters = () => {
    filters.value = {
        order_booker_id: '',
        customer_id: '',
        year: props.currentYear
    };
    applyFilters();
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK').format(value || 0);
};

const printReport = () => {
    window.print();
};

// Calculate totals
const totals = computed(() => {
    const result = {
        rent: 0,
        months: 0,
        total: 0,
        incentive: 0,
        sales: 0,
        monthlySales: {}
    };
    
    for (let m = 1; m <= 12; m++) {
        result.monthlySales[m] = 0;
    }
    
    props.shelves?.forEach(shelf => {
        result.rent += parseFloat(shelf.rent_amount) || 0;
        result.months += parseInt(shelf.contract_months) || 0;
        result.total += (parseFloat(shelf.rent_amount) || 0) * (parseInt(shelf.contract_months) || 0);
        result.incentive += parseFloat(shelf.incentive_amount) || 0;
        result.sales += parseFloat(shelf.total_sales) || 0;
        
        for (let m = 1; m <= 12; m++) {
            result.monthlySales[m] += parseFloat(shelf.monthly_sales?.[m]) || 0;
        }
    });
    
    return result;
});
</script>

<template>
    <Head title="Shelf Rent Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Shelf Rent Report</h1>
                    <p class="text-gray-500 mt-1">View shelf rentals with month-wise sales performance.</p>
                </div>
                <div class="flex gap-2">
                    <Link 
                        :href="route('shelves.index')" 
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                    >
                        Back to Shelves
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
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Year -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                        <select 
                            v-model="filters.year"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                        >
                            <option v-for="year in years" :key="year" :value="year">
                                {{ year }}
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

                    <!-- Customer -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                        <select 
                            v-model="filters.customer_id"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                        >
                            <option value="">All Customers</option>
                            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                {{ customer.name }} ({{ customer.code }})
                            </option>
                        </select>
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
                    <h1 class="text-xl font-bold text-center">Shelf Rent Report - {{ currentYear }}</h1>
                    <p class="text-center text-sm text-gray-600">
                        Generated on {{ new Date().toLocaleDateString() }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase">Customer</th>
                                <th class="px-2 py-2 text-right font-medium text-gray-500 uppercase">Rent/M</th>
                                <th class="px-2 py-2 text-center font-medium text-gray-500 uppercase">Months</th>
                                <th class="px-2 py-2 text-right font-medium text-gray-500 uppercase">Total</th>
                                <th 
                                    v-for="month in months" 
                                    :key="month.num"
                                    class="px-2 py-2 text-right font-medium text-gray-500 uppercase"
                                >
                                    {{ month.name }}
                                </th>
                                <th class="px-2 py-2 text-right font-medium text-gray-500 uppercase">Total Sale</th>
                                <th class="px-2 py-2 text-right font-medium text-gray-500 uppercase">Incentive</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in shelves" :key="item.id" class="hover:bg-gray-50">
                                <td class="px-2 py-2 whitespace-nowrap font-mono text-indigo-600">
                                    {{ item.shelf_code || '-' }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap">
                                    <div class="font-medium">{{ item.customer?.customer_code }}</div>
                                    <div class="text-gray-500 text-xs">{{ item.customer?.shop_name }}</div>
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-right">
                                    {{ formatCurrency(item.rent_amount) }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-center">
                                    {{ item.contract_months || '-' }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-right font-medium">
                                    {{ formatCurrency((item.rent_amount || 0) * (item.contract_months || 0)) }}
                                </td>
                                <td 
                                    v-for="month in months" 
                                    :key="month.num"
                                    class="px-2 py-2 whitespace-nowrap text-right"
                                    :class="item.monthly_sales?.[month.num] > 0 ? 'text-green-600' : 'text-gray-400'"
                                >
                                    {{ formatCurrency(item.monthly_sales?.[month.num] || 0) }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-right font-semibold text-green-700">
                                    {{ formatCurrency(item.total_sales) }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-right font-medium text-blue-600">
                                    {{ formatCurrency(item.incentive_amount) }}
                                </td>
                            </tr>
                            <tr v-if="shelves.length === 0">
                                <td :colspan="17" class="px-4 py-12 text-center text-gray-500">
                                    No shelves found with assigned customers.
                                </td>
                            </tr>
                        </tbody>
                        <!-- Totals Row -->
                        <tfoot v-if="shelves.length > 0" class="bg-gray-100">
                            <tr class="font-semibold">
                                <td class="px-2 py-2" colspan="2">Totals</td>
                                <td class="px-2 py-2 text-right">{{ formatCurrency(totals.rent) }}</td>
                                <td class="px-2 py-2 text-center">-</td>
                                <td class="px-2 py-2 text-right">{{ formatCurrency(totals.total) }}</td>
                                <td 
                                    v-for="month in months" 
                                    :key="month.num"
                                    class="px-2 py-2 text-right"
                                >
                                    {{ formatCurrency(totals.monthlySales[month.num]) }}
                                </td>
                                <td class="px-2 py-2 text-right text-green-700">{{ formatCurrency(totals.sales) }}</td>
                                <td class="px-2 py-2 text-right text-blue-600">{{ formatCurrency(totals.incentive) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Summary -->
                <div v-if="shelves.length > 0" class="p-4 border-t border-gray-200 bg-gray-50 print:hidden">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Total Shelves: <strong>{{ shelves.length }}</strong></span>
                        <span class="text-gray-600">Total Rent: <strong>Rs. {{ formatCurrency(totals.total) }}</strong></span>
                        <span class="text-gray-600">Total Sales: <strong class="text-green-600">Rs. {{ formatCurrency(totals.sales) }}</strong></span>
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
        margin: 0.5cm;
    }
    body {
        font-size: 10px;
    }
    table {
        font-size: 9px;
    }
}
</style>
