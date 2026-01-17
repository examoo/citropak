<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    invoices: Array,
    totals: Object,
    filters: Object,
});

const form = ref({
    month: props.filters.month || '',
    year: props.filters.year || '',
});

const months = [
    { value: '01', label: 'January' },
    { value: '02', label: 'February' },
    { value: '03', label: 'March' },
    { value: '04', label: 'April' },
    { value: '05', label: 'May' },
    { value: '06', label: 'June' },
    { value: '07', label: 'July' },
    { value: '08', label: 'August' },
    { value: '09', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

// Generate last 5 years
const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => currentYear - i);

const search = () => {
    router.get(route('sale-tax-invoices-reports.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const exportExcel = () => {
    const params = new URLSearchParams(form.value);
    window.location.href = route('sale-tax-invoices-reports.export') + '?' + params.toString();
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const getMonthName = (monthNum) => {
    return months.find(m => m.value == monthNum)?.label || monthNum;
};
</script>

<template>

    <Head title="Sale Tax Invoices Monthly Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Back Link -->
            <div class="no-print">
                <Link href="/dashboard" class="text-blue-600 hover:text-blue-800 text-sm">
                    ← Back to Dashboard
                </Link>
            </div>

            <!-- Header -->
            <div class="text-center">
                <h1 class="text-2xl font-bold text-gray-800">Sale Tax Invoices Monthly Report</h1>
                <p class="text-gray-500">For the Month of {{ getMonthName(filters.month) }} {{ filters.year }}</p>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 no-print">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/4">
                        <InputLabel value="Select Month" />
                        <select v-model="form.month"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div class="w-full md:w-1/4">
                        <InputLabel value="Select Year" />
                        <select v-model="form.year"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <PrimaryButton @click="search" class="bg-blue-600 hover:bg-blue-700">
                        Generate Report
                    </PrimaryButton>
                    <button @click="exportExcel"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                        Export Excel
                    </button>
                    <button onclick="window.print()"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                        Print / PDF
                    </button>
                </div>
            </div>

            <!-- Report Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-indigo-50 text-gray-700 uppercase font-semibold">
                            <tr>
                                <th class="px-3 py-3">Sr #</th>
                                <th class="px-3 py-3">Date</th>
                                <th class="px-3 py-3">Invoice No</th>
                                <th class="px-3 py-3">Code</th>
                                <th class="px-3 py-3">Buyer Name</th>
                                <th class="px-3 py-3">Address</th>
                                <th class="px-3 py-3">Phone</th>
                                <th class="px-3 py-3">NTN</th>
                                <th class="px-3 py-3">CNIC</th>
                                <th class="px-3 py-3">STN</th>
                                <th class="px-3 py-3 text-right">Gross Amount</th>
                                <th class="px-3 py-3 text-right">Discount</th>
                                <th class="px-3 py-3 text-right">Value Excl. Tax</th>
                                <th class="px-3 py-3 text-right">Sales Tax</th>
                                <th class="px-3 py-3 text-right">Further Tax</th>
                                <th class="px-3 py-3 text-right">Value Incl. Tax</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(inv, idx) in invoices" :key="inv.id" class="hover:bg-gray-50/50">
                                <td class="px-3 py-2 text-center">{{ idx + 1 }}</td>
                                <td class="px-3 py-2">{{ inv.invoice_date }}</td>
                                <td class="px-3 py-2 font-medium text-indigo-600">{{ inv.invoice_number }}</td>
                                <td class="px-3 py-2 text-xs">{{ inv.customer_code }}</td>
                                <td class="px-3 py-2 font-semibold">{{ inv.buyer_name }}</td>
                                <td class="px-3 py-2 text-xs truncate max-w-xs">{{ inv.address }}</td>
                                <td class="px-3 py-2">{{ inv.phone }}</td>
                                <td class="px-3 py-2">{{ inv.buyer_ntn }}</td>
                                <td class="px-3 py-2">{{ inv.cnic }}</td>
                                <td class="px-3 py-2">{{ inv.sales_tax_number }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency(inv.subtotal) }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency(inv.discount) }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency(inv.taxable_value) }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency(inv.sales_tax) }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency(inv.further_tax) }}</td>
                                <td class="px-3 py-2 text-right font-bold text-gray-800">{{
                                    formatCurrency(inv.total_value) }}</td>
                            </tr>
                            <tr v-if="invoices.length === 0">
                                <td colspan="16" class="px-6 py-12 text-center text-gray-400">
                                    No invoices found for this month.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="invoices.length > 0"
                            class="bg-gray-50 font-bold text-gray-800 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="10" class="px-3 py-3 text-right uppercase">Total</td>
                                <td class="px-3 py-3 text-right">{{ formatCurrency(totals.subtotal) }}</td>
                                <td class="px-3 py-3 text-right">{{ formatCurrency(totals.discount) }}</td>
                                <td class="px-3 py-3 text-right">{{ formatCurrency(totals.taxable_value) }}</td>
                                <td class="px-3 py-3 text-right">{{ formatCurrency(totals.sales_tax) }}</td>
                                <td class="px-3 py-3 text-right">{{ formatCurrency(totals.further_tax) }}</td>
                                <td class="px-3 py-3 text-right">{{ formatCurrency(totals.total_value) }}</td>
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
    .border {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
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
        /* Smaller font to fit many columns */
    }

    th,
    td {
        white-space: normal !important;
        /* Allow wrapping */
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
