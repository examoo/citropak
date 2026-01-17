<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    date: String,
    creditInvoices: Array,
    recoveries: Array,
    totals: Object,
});

const form = ref({
    date: props.date,
});

const search = () => {
    router.get(route('credit-management.daily-report'), form.value, { preserveState: true });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const formatDateDisplay = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    return new Date(dateStr).toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

const exportExcel = () => {
    const params = new URLSearchParams(form.value);
    window.location.href = route('credit-management.daily-report.export') + '?' + params.toString();
};
</script>

<template>

    <Head title="Daily Credit Report" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-amber-500 to-yellow-500 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">Daily Credit Report</h1>
                <p class="text-amber-100 text-sm">{{ formatDateDisplay(date) }}</p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex gap-4 items-end">
                    <div>
                        <InputLabel value="Select Date" />
                        <input type="date" v-model="form.date"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <input type="date" v-model="form.date"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <PrimaryButton @click="search" class="bg-amber-600 hover:bg-amber-700">View Report</PrimaryButton>
                <button @click="exportExcel"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                    Export
                </button>
                <button @click="() => window.print()"
                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                    Print
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-orange-500 text-white rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-semibold">Credit Sales</h3>
                <p class="text-3xl font-bold">{{ formatCurrency(totals.credit) }}</p>
                <p class="text-orange-200 text-sm">{{ creditInvoices.length }} invoices</p>
            </div>
            <div class="bg-green-500 text-white rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-semibold">Recovery</h3>
                <p class="text-3xl font-bold">{{ formatCurrency(totals.recovery) }}</p>
                <p class="text-green-200 text-sm">{{ recoveries.length }} payments</p>
            </div>
        </div>

        <!-- Credit Invoices -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-orange-500 text-white px-4 py-3">
                <h3 class="font-bold">Credit Invoices</h3>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Invoice #</th>
                        <th class="px-4 py-3">Customer Code</th>
                        <th class="px-4 py-3">Customer Name</th>
                        <th class="px-4 py-3">Van</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="inv in creditInvoices" :key="inv.invoice_number" class="hover:bg-gray-50/50">
                        <td class="px-4 py-3 font-medium text-orange-600">{{ inv.invoice_number }}</td>
                        <td class="px-4 py-3">{{ inv.customer_code }}</td>
                        <td class="px-4 py-3">{{ inv.customer_name }}</td>
                        <td class="px-4 py-3">{{ inv.van }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(inv.amount) }}</td>
                    </tr>
                    <tr v-if="creditInvoices.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">No credit invoices for this date.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Recoveries -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-green-500 text-white px-4 py-3">
                <h3 class="font-bold">Recoveries</h3>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Invoice #</th>
                        <th class="px-4 py-3">Customer Code</th>
                        <th class="px-4 py-3">Customer Name</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="rec in recoveries" :key="rec.invoice_number" class="hover:bg-gray-50/50">
                        <td class="px-4 py-3 font-medium text-green-600">{{ rec.invoice_number }}</td>
                        <td class="px-4 py-3">{{ rec.customer_code }}</td>
                        <td class="px-4 py-3">{{ rec.customer_name }}</td>
                        <td class="px-4 py-3 text-right font-bold text-green-600">{{ formatCurrency(rec.amount) }}</td>
                    </tr>
                    <tr v-if="recoveries.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">No recoveries for this date.</td>
                    </tr>
                </tbody>
            </table>
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

    .grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 1rem !important;
    }
}
</style>
