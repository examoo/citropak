<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    customerSummary: Array,
    filters: Object,
    years: Array,
});

const months = [
    { id: 1, name: 'January' }, { id: 2, name: 'February' }, { id: 3, name: 'March' },
    { id: 4, name: 'April' }, { id: 5, name: 'May' }, { id: 6, name: 'June' },
    { id: 7, name: 'July' }, { id: 8, name: 'August' }, { id: 9, name: 'September' },
    { id: 10, name: 'October' }, { id: 11, name: 'November' }, { id: 12, name: 'December' },
];

const form = ref({
    month: props.filters.month,
    year: props.filters.year,
});

const search = () => {
    router.get(route('credit-management.summary'), form.value, { preserveState: true });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const getMonthName = (m) => months.find(x => x.id === m)?.name || '';

const totals = {
    credit: props.customerSummary.reduce((s, c) => s + c.total_credit, 0),
    recovered: props.customerSummary.reduce((s, c) => s + c.total_recovered, 0),
    pending: props.customerSummary.reduce((s, c) => s + c.pending, 0),
    pending: props.customerSummary.reduce((s, c) => s + c.pending, 0),
};

const print = () => window.print();

const exportExcel = () => {
    const params = new URLSearchParams(form.value);
    window.location.href = route('credit-management.summary.export') + '?' + params.toString();
};
</script>

<template>

    <Head title="Credit Summary" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">Credit Summary</h1>
                <p class="text-purple-100 text-sm">Customer-wise credit summary for {{ getMonthName(filters.month) }} {{
                    filters.year }}</p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex gap-4 items-end">
                    <div>
                        <InputLabel value="Month" />
                        <select v-model="form.month" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option v-for="m in months" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Year" />
                        <select v-model="form.year" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <PrimaryButton @click="search" class="bg-purple-600 hover:bg-purple-700">Generate</PrimaryButton>
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
                            <th class="px-4 py-3">Customer Code</th>
                            <th class="px-4 py-3">Customer Name</th>
                            <th class="px-4 py-3 text-center">Invoices</th>
                            <th class="px-4 py-3 text-right">Total Credit</th>
                            <th class="px-4 py-3 text-right">Recovered</th>
                            <th class="px-4 py-3 text-right">Pending</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in customerSummary" :key="c.customer_code" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 font-medium">{{ c.customer_code }}</td>
                            <td class="px-4 py-3">{{ c.customer_name }}</td>
                            <td class="px-4 py-3 text-center">{{ c.invoice_count }}</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(c.total_credit) }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ formatCurrency(c.total_recovered) }}</td>
                            <td class="px-4 py-3 text-right font-bold"
                                :class="c.pending > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatCurrency(c.pending) }}
                            </td>
                        </tr>
                        <tr v-if="customerSummary.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">No data found.</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right">Total:</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(totals.credit) }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ formatCurrency(totals.recovered) }}</td>
                            <td class="px-4 py-3 text-right text-red-600">{{ formatCurrency(totals.pending) }}</td>
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
