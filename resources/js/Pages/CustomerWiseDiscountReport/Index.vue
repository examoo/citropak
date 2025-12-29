<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    reportData: Array,
    totals: Object,
    filters: Object,
    customers: Array,
});

const form = ref({
    customer_ids: props.filters.customer_ids || [], 
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

// We will use a single select for now as SearchableSelect is likely single-value. 
// If we want multiple, we'd need a different component. 
// For this report, often users want "All" or "Specific One". 
// I'll assume single select for filtering for now to match existing patterns, 
// but allow clearing it to show all.
const selectedCustomerId = ref(props.filters.customer_ids && props.filters.customer_ids.length > 0 ? props.filters.customer_ids[0] : '');

watch(selectedCustomerId, (newId) => {
    form.value.customer_ids = newId ? [newId] : [];
});

const search = () => {
    router.get(route('customer-wise-discount-reports.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head title="Customer Wise Discount Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">Customer Wise Discount Report</h1>
                <p class="text-emerald-100 text-sm mt-1">View discounts and net sales per customer.</p>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex flex-col md:flex-row gap-4 items-end flex-wrap">
                    <div class="min-w-[250px] flex-1">
                        <InputLabel value="Filter by Customer (Optional)" />
                        <SearchableSelect 
                            v-model="selectedCustomerId" 
                            :options="customers" 
                            option-value="id"
                            option-label="name" 
                            placeholder="All Customers" 
                            class="mt-1 block w-full" 
                        />
                    </div>
                    
                    <div>
                        <InputLabel value="Start Date" />
                        <input type="date" v-model="form.date_from"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                    </div>
                    <div>
                        <InputLabel value="End Date" />
                        <input type="date" v-model="form.date_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                    </div>
                    <PrimaryButton @click="search" class="bg-emerald-600 hover:bg-emerald-700">
                        <span class="mr-1">✓</span> Generate Report
                    </PrimaryButton>
                </div>
            </div>

            <!-- Report Content -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Report Data</h3>
                    <div class="text-sm text-gray-500">
                        Date Range: <span class="font-medium text-gray-700">{{ formatDate(filters.date_from) }}</span> to <span class="font-medium text-gray-700">{{ formatDate(filters.date_to) }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Customer Code</th>
                                <th class="px-4 py-3">Customer Name</th>
                                <th class="px-4 py-3 text-right">Total Gross Amount</th>
                                <th class="px-4 py-3 text-right">Total Discount</th>
                                <th class="px-4 py-3 text-right">Net Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in reportData" :key="item.customer_id" class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 font-medium text-gray-700">{{ item.customer_code }}</td>
                                <td class="px-4 py-3">{{ item.customer_name }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(item.total_gross_amount) }}</td>
                                <td class="px-4 py-3 text-right text-red-600 font-medium">{{ formatCurrency(item.total_discount_amount) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ formatCurrency(item.total_net_amount) }}</td>
                            </tr>
                            <tr v-if="reportData.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400">No data found for the selected criteria.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold border-t border-gray-200">
                            <tr>
                                <td class="px-4 py-3 text-right" colspan="2">TOTALS</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(totals.gross_amount) }}</td>
                                <td class="px-4 py-3 text-right text-red-700">{{ formatCurrency(totals.discount_amount) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-700">{{ formatCurrency(totals.net_amount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
