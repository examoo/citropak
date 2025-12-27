<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    invoices: Array,
    totals: Object,
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
    router.get(route('credit-management.sales-sheet'), form.value, { preserveState: true });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
};

const getMonthName = (m) => months.find(x => x.id === m)?.name || '';

const print = () => window.print();
</script>

<template>
    <Head title="Credit Sales Sheet" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-rose-600 to-pink-600 text-white p-4 rounded-xl shadow-lg no-print">
                <h1 class="text-xl font-bold">Credit Sales Sheet</h1>
                <p class="text-rose-100 text-sm">{{ getMonthName(filters.month) }} {{ filters.year }}</p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 no-print">
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
                    <PrimaryButton @click="search" class="bg-rose-600 hover:bg-rose-700">Generate</PrimaryButton>
                    <PrimaryButton @click="print" class="bg-gray-600 hover:bg-gray-700">🖨 Print</PrimaryButton>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="hidden print:block p-4 border-b">
                    <h1 class="text-xl font-bold text-center">Credit Sales Sheet</h1>
                    <p class="text-center text-gray-600">{{ getMonthName(filters.month) }} {{ filters.year }}</p>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Invoice #</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Customer Code</th>
                            <th class="px-4 py-3">Customer Name</th>
                            <th class="px-4 py-3">Van</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                            <th class="px-4 py-3 text-right">Discount</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="inv in invoices" :key="inv.invoice_number" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 font-medium text-rose-600">{{ inv.invoice_number }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ formatDate(inv.date) }}</td>
                            <td class="px-4 py-3">{{ inv.customer_code }}</td>
                            <td class="px-4 py-3">{{ inv.customer_name }}</td>
                            <td class="px-4 py-3">{{ inv.van }}</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(inv.subtotal) }}</td>
                            <td class="px-4 py-3 text-right text-orange-600">{{ formatCurrency(inv.discount) }}</td>
                            <td class="px-4 py-3 text-right font-bold">{{ formatCurrency(inv.total) }}</td>
                        </tr>
                        <tr v-if="invoices.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">No credit sales for this month.</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right">Total:</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(totals.subtotal) }}</td>
                            <td class="px-4 py-3 text-right text-orange-600">{{ formatCurrency(totals.discount) }}</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(totals.total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print { .no-print { display: none !important; } }
</style>
