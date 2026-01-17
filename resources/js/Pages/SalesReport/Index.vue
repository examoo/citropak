<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    invoices: Array,
    totalAmount: Number,
    filters: Object,
    vans: Array,
    orderBookers: Array,
});

const form = ref({
    van_id: props.filters.van_id || '',
    order_booker_id: props.filters.order_booker_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const search = () => {
    router.get(route('sales-reports.index'), form.value, {
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
    window.location.href = route('sales-reports.export') + '?' + params.toString();
};
</script>

<template>

    <Head title="Sales Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header & Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 no-print">
                <div class="flex flex-col md:flex-row gap-4 items-end flex-wrap">
                    <div class="min-w-[180px]">
                        <InputLabel value="Van" />
                        <SearchableSelect v-model="form.van_id" :options="vans" option-value="id" option-label="name"
                            placeholder="All Vans" class="mt-1 block w-full" />
                    </div>
                    <div class="min-w-[180px]">
                        <InputLabel value="Order Booker" />
                        <SearchableSelect v-model="form.order_booker_id" :options="orderBookers" option-value="id"
                            option-label="name" placeholder="All Bookers" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Date From" />
                        <input type="date" v-model="form.date_from"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <InputLabel value="Date To" />
                        <input type="date" v-model="form.date_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="flex gap-2 pb-0.5">
                        <PrimaryButton @click="search">Search</PrimaryButton>
                        <button @click="exportExcel"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                            Export Excel
                        </button>
                        <SecondaryButton @click="print">Print</SecondaryButton>
                    </div>
                </div>
            </div>

            <!-- Report Header (Print Only) -->
            <div class="hidden print:block mb-6">
                <h1 class="text-2xl font-bold text-center">Sales Report</h1>
                <p class="text-center text-gray-600">{{ form.date_from }} to {{ form.date_to }}</p>
            </div>

            <!-- Invoices Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 bg-[#0089BA] text-white">
                    <h2 class="font-bold text-lg">Sales Report</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Invoice #</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Order Booker</th>
                                <th class="px-4 py-3">Van</th>
                                <th class="px-4 py-3">Customer Code</th>
                                <th class="px-4 py-3">Customer Name</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 font-medium text-[#0089BA]">{{ inv.invoice_number }}</td>
                                <td class="px-4 py-3">{{ formatDate(inv.invoice_date) }}</td>
                                <td class="px-4 py-3">{{ inv.order_booker }}</td>
                                <td class="px-4 py-3">{{ inv.van }}</td>
                                <td class="px-4 py-3">{{ inv.customer_code }}</td>
                                <td class="px-4 py-3">{{ inv.customer_name }}</td>
                                <td class="px-4 py-3 text-right font-bold">{{ formatCurrency(inv.total_amount) }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="inv.is_credit"
                                        class="px-2 py-1 rounded text-xs font-bold bg-amber-100 text-amber-700">Credit</span>
                                    <span v-else
                                        class="px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-700">Cash</span>
                                </td>
                            </tr>
                            <tr v-if="invoices.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">No invoices found.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold text-gray-900">
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-right">Total:</td>
                                <td class="px-4 py-3 text-right text-green-600">{{ formatCurrency(totalAmount) }}</td>
                                <td></td>
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

    /* Hide colored status badges for print if needed, or adjust colors */
}
</style>
