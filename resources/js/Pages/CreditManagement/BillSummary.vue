<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    bills: Array,
    totals: Object,
    filters: Object,
});

const form = ref({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const search = () => {
    router.get(route('credit-management.bill-summary'), form.value, { preserveState: true });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

const print = () => window.print();

const exportExcel = () => {
    const params = new URLSearchParams(form.value);
    window.location.href = route('credit-management.bill-summary.export') + '?' + params.toString();
};
</script>

<template>

    <Head title="Credit Bill Summary" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-teal-600 to-cyan-600 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">Credit Bill Summary</h1>
                <p class="text-teal-100 text-sm">Bill-wise credit and recovery summary</p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex gap-4 items-end">
                    <div>
                        <InputLabel value="From Date" />
                        <input type="date" v-model="form.date_from"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <InputLabel value="To Date" />
                        <input type="date" v-model="form.date_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <PrimaryButton @click="search" class="bg-teal-600 hover:bg-teal-700">Search</PrimaryButton>
                    <button @click="exportExcel"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                        Export
                    </button>
                    <button @click="print"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                        Print
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Invoice #</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Van</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Recovered</th>
                            <th class="px-4 py-3 text-right">Pending</th>
                            <th class="px-4 py-3 text-center">Recoveries</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="bill in bills" :key="bill.id" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 font-medium text-teal-600">{{ bill.invoice_number }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ formatDate(bill.date) }}</td>
                            <td class="px-4 py-3">{{ bill.customer_code }} - {{ bill.customer_name }}</td>
                            <td class="px-4 py-3">{{ bill.van }}</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(bill.amount) }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ formatCurrency(bill.recovered) }}</td>
                            <td class="px-4 py-3 text-right font-bold"
                                :class="bill.pending > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatCurrency(bill.pending) }}
                            </td>
                            <td class="px-4 py-3 text-center">{{ bill.recovery_count }}</td>
                        </tr>
                        <tr v-if="bills.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">No bills found.</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right">Total:</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(totals.amount) }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ formatCurrency(totals.recovered) }}</td>
                            <td class="px-4 py-3 text-right text-red-600">{{ formatCurrency(totals.pending) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </DashboardLayout>
</template>
<style scoped>
@media print {

    nav,
    header,
    aside,
    .fixed,
    .sticky,
    button,
    .no-print {
        display: none !important;
    }

    body,
    #app,
    main,
    .min-h-screen {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        overflow: visible !important;
    }

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
