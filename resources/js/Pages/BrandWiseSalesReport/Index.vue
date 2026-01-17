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
    brands: Array,
});

const form = ref({
    brand_ids: props.filters.brand_ids || [],
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const selectedBrandId = ref(props.filters.brand_ids && props.filters.brand_ids.length > 0 ? props.filters.brand_ids[0] : '');

watch(selectedBrandId, (newId) => {
    form.value.brand_ids = newId ? [newId] : [];
});

const search = () => {
    router.get(route('brand-wise-sales-reports.index'), form.value, {
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

const print = () => window.print();

const exportExcel = () => {
    const params = new URLSearchParams(form.value);
    window.location.href = route('brand-wise-sales-reports.export') + '?' + params.toString();
};
</script>

<template>

    <Head title="Brand Wise Sales Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-cyan-600 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">Brand Wise Sales Report</h1>
                <p class="text-blue-100 text-sm mt-1">Analyze sales performance by brand.</p>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex flex-col md:flex-row gap-4 items-end flex-wrap">
                    <div class="min-w-[250px] flex-1">
                        <InputLabel value="Filter by Brand (Optional)" />
                        <SearchableSelect v-model="selectedBrandId" :options="brands" option-value="id"
                            option-label="name" placeholder="All Brands" class="mt-1 block w-full" />
                    </div>

                    <div>
                        <InputLabel value="Start Date" />
                        <input type="date" v-model="form.date_from"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <InputLabel value="End Date" />
                        <input type="date" v-model="form.date_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <PrimaryButton @click="search" class="bg-blue-600 hover:bg-blue-700">
                        <span class="mr-1">✓</span> Generate Report
                    </PrimaryButton>
                    <button @click="exportExcel"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                        Export Excel
                    </button>
                    <button @click="print"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                        Print / PDF
                    </button>
                </div>
            </div>

            <!-- Report Content -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Sales Data</h3>
                    <div class="text-sm text-gray-500">
                        Date Range: <span class="font-medium text-gray-700">{{ formatDate(filters.date_from) }}</span>
                        to <span class="font-medium text-gray-700">{{ formatDate(filters.date_to) }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Brand Name</th>
                                <th class="px-4 py-3 text-right">Free Qty</th>
                                <th class="px-4 py-3 text-right">Total Quantity</th>
                                <th class="px-4 py-3 text-right">Gross Amount</th>
                                <th class="px-4 py-3 text-right">Total Discount</th>
                                <th class="px-4 py-3 text-right">Net Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in reportData" :key="item.brand_id" class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 font-medium text-gray-700">{{ item.brand_name }}</td>
                                <td class="px-4 py-3 text-right font-medium text-orange-600">{{ item.free_quantity }}
                                </td>
                                <td class="px-4 py-3 text-right">{{ item.total_quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(item.total_gross_amount) }}</td>
                                <td class="px-4 py-3 text-right text-red-600 font-medium">{{
                                    formatCurrency(item.total_discount_amount) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-blue-600">{{
                                    formatCurrency(item.total_net_amount) }}</td>
                            </tr>
                            <tr v-if="reportData.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400">No data found for the
                                    selected criteria.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold border-t border-gray-200">
                            <tr>
                                <td class="px-4 py-3 text-right">TOTALS</td>
                                <td class="px-4 py-3 text-right">{{ totals.free_quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ totals.quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(totals.gross_amount) }}</td>
                                <td class="px-4 py-3 text-right text-red-700">{{ formatCurrency(totals.discount_amount)
                                    }}</td>
                                <td class="px-4 py-3 text-right text-blue-700">{{ formatCurrency(totals.net_amount) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
<style scoped>
@media print {

    /* Hide non-printable elements */
    .no-print,
    nav,
    header,
    aside,
    .fixed,
    .sticky {
        display: none !important;
    }

    /* Reset layout for print */
    body,
    #app,
    main,
    .min-h-screen {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow: visible !important;
    }

    /* Overwrite container styles */
    .bg-white,
    .shadow-sm,
    .rounded-xl,
    .border,
    .bg-gradient-to-r,
    .shadow-lg {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        color: black !important;
    }

    .text-white {
        color: black !important;
    }

    /* Ensure table fits */
    .overflow-x-auto,
    .overflow-hidden {
        overflow: visible !important;
        height: auto !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 10px !important;
    }

    th,
    td {
        white-space: normal !important;
        padding: 4px !important;
        border: 1px solid #ddd !important;
    }

    thead th {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
