<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { ref } from 'vue';

import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    reportData: Array, // Flat array for this report (Brand summary)
    totals: Object,
    filters: Object,
    brands: Array,
    subDistributions: Array,
});

const filters = ref({
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    sub_distribution_id: props.filters.sub_distribution_id,
    brand_ids: props.filters.brand_ids || [],
});

const applyFilters = () => {
    router.get(route('sub-distributor-brand-sales-reports.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

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
    <Head title="Sub-Distributor Brand Sales Report" />

    <DashboardLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sub-Distributor Brand Wise Sales Report</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
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
                        <div>
                            <InputLabel value="Brands" />
                            <div class="mt-1 relative">
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
                        <div v-if="reportData.length === 0" class="text-center py-8 text-gray-500">
                            No data found.
                        </div>

                        <div v-else>
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand Name</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Qty Sold</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Free Qty</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Amount</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Net Sales</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="item in reportData" :key="item.brand_id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r">{{ item.brand_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ item.total_quantity }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 text-right">{{ item.free_quantity }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(item.total_gross_amount) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-right">({{ formatCurrency(item.total_discount_amount) }})</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold bg-green-50/50">{{ formatCurrency(item.total_net_amount) }}</td>
                                    </tr>
                                    <!-- Grand Total Row IN TABLE -->
                                    <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                                        <td class="px-6 py-4 text-right border-r uppercase text-xs">Total:</td>
                                        <td class="px-6 py-4 text-right">{{ totals.quantity }}</td>
                                        <td class="px-6 py-4 text-right">{{ totals.free_quantity }}</td>
                                        <td class="px-6 py-4 text-right">{{ formatCurrency(totals.gross_amount) }}</td>
                                        <td class="px-6 py-4 text-right text-red-700">({{ formatCurrency(totals.discount_amount) }})</td>
                                        <td class="px-6 py-4 text-right text-green-800">{{ formatCurrency(totals.net_amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
