<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { ref, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    groupedData: Object,
    totals: Object,
    filters: Object,
    brands: Array,
    subDistributions: Array,
});

const filters = ref({
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    sub_distribution_id: props.filters.sub_distribution_id,
    brand_ids: (Array.isArray(props.filters.brand_ids) ? props.filters.brand_ids : []) || [],
});

watch(() => props.filters, (newFilters) => {
    filters.value.date_from = newFilters.date_from;
    filters.value.date_to = newFilters.date_to;
    filters.value.sub_distribution_id = newFilters.sub_distribution_id;
    filters.value.brand_ids = (Array.isArray(newFilters.brand_ids) ? newFilters.brand_ids : []) || [];
}, { deep: true });

const applyFilters = () => {
    router.get(route('sub-distributor-stock-sales-reports.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>
    <Head title="Sub-Distributor Stock Sales Report" />

    <DashboardLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sub-Distributor Stock Wise Sales Report</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Filters -->
                <div class="bg-white overflow-visible shadow-sm sm:rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-1">
                            <InputLabel value="Sub Distributor" class="text-indigo-700 font-bold mb-1" />
                            <select v-model="filters.sub_distribution_id"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm bg-indigo-50">
                                <option value="">Select Sub Distributor</option>
                                <option v-for="sd in subDistributions" :key="sd.id" :value="sd.id">{{ sd.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Date From" />
                            <TextInput v-model="filters.date_from" type="date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Date To" />
                            <TextInput v-model="filters.date_to" type="date" class="mt-1 block w-full" />
                        </div>
                        <div class="relative z-50">
                            <InputLabel value="Brands" />
                            <div class="mt-1">
                                <SearchableSelect
                                    v-model="filters.brand_ids"
                                    :options="brands"
                                    multiple
                                    placeholder="All Brands"
                                />
                            </div>
                        </div>
                        <div class="md:col-span-4 flex justify-end mt-2">
                            <PrimaryButton @click="applyFilters">
                                Filter Report
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- Report Content -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="Object.keys(groupedData).length === 0" class="text-center py-8 text-gray-500">
                            No data found for the selected criteria.
                        </div>

                        <div v-else class="space-y-8">
                            <div v-for="(items, brandName) in groupedData" :key="brandName">
                                <h3 class="text-lg font-bold text-gray-800 mb-3 bg-gray-50 p-2 rounded border-l-4 border-indigo-500">
                                    {{ brandName }}
                                </h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Code</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50/50 text-yellow-800 border-l border-r border-yellow-200">Sold Qty</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50/50">Free Qty</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Amount</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Net Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="item in items" :key="item.product_id" class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.product_code }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.product_name }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold bg-yellow-50/30 border-l border-r border-yellow-100">{{ item.total_quantity }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 text-right bg-blue-50/30">{{ item.free_quantity }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(item.total_gross_amount) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-right">({{ formatCurrency(item.total_discount_amount) }})</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">{{ formatCurrency(item.total_net_amount) }}</td>
                                            </tr>
                                            <!-- Brand Subtotal -->
                                            <tr class="bg-indigo-50/30 font-semibold border-t border-gray-300">
                                                <td colspan="2" class="px-6 py-3 text-right text-indigo-800 uppercase text-xs">Total {{ brandName }}:</td>
                                                <td class="px-6 py-3 text-right text-indigo-800 bg-yellow-50/50 border-l border-r border-yellow-200">{{ items.reduce((sum, i) => sum + i.total_quantity, 0) }}</td>
                                                <td class="px-6 py-3 text-right text-indigo-800">{{ items.reduce((sum, i) => sum + i.free_quantity, 0) }}</td>
                                                <td class="px-6 py-3 text-right text-indigo-800">{{ formatCurrency(items.reduce((sum, i) => sum + i.total_gross_amount, 0)) }}</td>
                                                <td class="px-6 py-3 text-right text-red-600">({{ formatCurrency(items.reduce((sum, i) => sum + i.total_discount_amount, 0)) }})</td>
                                                <td class="px-6 py-3 text-right text-indigo-800">{{ formatCurrency(items.reduce((sum, i) => sum + i.total_net_amount, 0)) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Grand Total -->
                        <div v-if="Object.keys(groupedData).length > 0" class="mt-8 border-t-4 border-gray-200 pt-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">Grand Summary</h3>
                            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-5">
                                <div class="px-4 py-5 bg-yellow-50 border border-yellow-200 shadow rounded-lg overflow-hidden sm:p-6">
                                    <dt class="text-sm font-medium text-yellow-800 truncate">Total Sold Qty</dt>
                                    <dd class="mt-1 text-3xl font-semibold text-yellow-900">{{ totals.quantity }}</dd>
                                </div>
                                <div class="px-4 py-5 bg-blue-50 shadow rounded-lg overflow-hidden sm:p-6">
                                    <dt class="text-sm font-medium text-blue-500 truncate">Total Free Qty</dt>
                                    <dd class="mt-1 text-3xl font-semibold text-blue-900">{{ totals.free_quantity }}</dd>
                                </div>
                                <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                                    <dt class="text-sm font-medium text-gray-500 truncate">Gross Amount</dt>
                                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ formatCurrency(totals.gross_amount) }}</dd>
                                </div>
                                <div class="px-4 py-5 bg-red-50 shadow rounded-lg overflow-hidden sm:p-6">
                                    <dt class="text-sm font-medium text-red-500 truncate">Total Discount</dt>
                                    <dd class="mt-1 text-3xl font-semibold text-red-900">{{ formatCurrency(totals.discount_amount) }}</dd>
                                </div>
                                <div class="px-4 py-5 bg-green-50 shadow rounded-lg overflow-hidden sm:p-6">
                                    <dt class="text-sm font-medium text-green-500 truncate">Net Sales</dt>
                                    <dd class="mt-1 text-3xl font-semibold text-green-900">{{ formatCurrency(totals.net_amount) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
