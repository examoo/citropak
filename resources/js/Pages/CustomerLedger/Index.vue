<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    customer: Object,
    entries: Array,
    totals: Object,
    filters: Object,
    customers: Array,
});

const form = ref({
    customer_code: props.filters.customer_code || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const selectedCustomerId = ref('');

// When customer dropdown changes, update the code
watch(selectedCustomerId, (newId) => {
    if (newId) {
        const cust = props.customers.find(c => c.id === newId);
        if (cust) {
            form.value.customer_code = cust.code;
        }
    }
});

const search = () => {
    router.get(route('customer-ledgers.index'), form.value, {
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
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

const print = () => {
    window.print();
};
</script>

<template>

    <Head title="Customer Ledger" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white p-4 rounded-xl shadow-lg no-print">
                <h1 class="text-xl font-bold">Customer Ledger</h1>
                <p class="text-sm text-emerald-100">View customer account transactions and balance</p>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 no-print">
                <div class="flex flex-col md:flex-row gap-4 items-end flex-wrap">
                    <div class="min-w-[200px]">
                        <InputLabel value="Select Customer" />
                        <SearchableSelect v-model="selectedCustomerId" :options="customers" option-value="id"
                            option-label="name" placeholder="Search customer..." class="mt-1 block w-full" />
                    </div>
                    <div class="text-gray-400 self-center">OR</div>
                    <div class="flex-1">
                        <InputLabel value="Enter Customer Code" />
                        <TextInput type="text" v-model="form.customer_code" placeholder="CPSSGD01475"
                            class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="From Date" />
                        <input type="date" v-model="form.date_from"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                    </div>
                    <div>
                        <InputLabel value="To Date" />
                        <input type="date" v-model="form.date_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                    </div>
                    <PrimaryButton @click="search" class="bg-emerald-600 hover:bg-emerald-700">
                        <span class="mr-1">🔍</span> Generate Ledger
                    </PrimaryButton>
                </div>
            </div>

            <!-- Ledger Content -->
            <div v-if="customer" class="space-y-4">
                <!-- Customer Info Header -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white p-4 flex justify-between items-center">
                        <div>
                            <h2 class="font-bold text-lg">{{ customer.name }}</h2>
                            <p class="text-emerald-100 text-sm">Code: {{ customer.code }}</p>
                        </div>
                        <button @click="print"
                            class="bg-white text-emerald-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-100 transition no-print">
                            🖨 Print Ledger
                        </button>
                    </div>

                    <!-- Print Header (visible only on print) -->
                    <div class="hidden print:block p-4 border-b">
                        <h1 class="text-2xl font-bold text-center">Customer Ledger</h1>
                        <p class="text-center text-gray-600">{{ customer.code }} - {{ customer.name }}</p>
                        <p v-if="filters.date_from || filters.date_to" class="text-center text-gray-500 text-sm">
                            Period: {{ filters.date_from || 'Start' }} to {{ filters.date_to || 'Present' }}
                        </p>
                    </div>

                    <!-- Ledger Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Reference</th>
                                    <th class="px-4 py-3">Description</th>
                                    <th class="px-4 py-3 text-right">Debit</th>
                                    <th class="px-4 py-3 text-right">Credit</th>
                                    <th class="px-4 py-3 text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <!-- Opening Balance Row -->
                                <tr class="bg-blue-50 font-medium">
                                    <td class="px-4 py-3">-</td>
                                    <td class="px-4 py-3 text-blue-700">Opening Balance</td>
                                    <td class="px-4 py-3 text-gray-600">Balance brought forward</td>
                                    <td class="px-4 py-3 text-right">-</td>
                                    <td class="px-4 py-3 text-right">-</td>
                                    <td class="px-4 py-3 text-right font-bold text-blue-700">
                                        {{ formatCurrency(customer.opening_balance) }}
                                    </td>
                                </tr>

                                <!-- Transaction Rows -->
                                <tr v-for="(entry, idx) in entries" :key="idx" class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3 text-gray-600">{{ formatDate(entry.date) }}</td>
                                    <td class="px-4 py-3 font-medium"
                                        :class="entry.type === 'invoice' ? 'text-orange-600' : 'text-green-600'">
                                        {{ entry.reference }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ entry.description }}</td>
                                    <td class="px-4 py-3 text-right text-red-600 font-medium">
                                        {{ entry.debit > 0 ? formatCurrency(entry.debit) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-green-600 font-medium">
                                        {{ entry.credit > 0 ? formatCurrency(entry.credit) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900">
                                        {{ formatCurrency(entry.balance) }}
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="entries.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                        No transactions found for this customer.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-100 font-bold text-gray-900">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right">Total:</td>
                                    <td class="px-4 py-3 text-right text-red-600">{{ formatCurrency(totals.debit) }}</td>
                                    <td class="px-4 py-3 text-right text-green-600">{{ formatCurrency(totals.credit) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900">{{ formatCurrency(totals.balance) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Summary Cards -->
                    <div class="p-4 bg-gray-50 border-t no-print">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <p class="text-xs text-gray-500 uppercase font-medium">Opening Balance</p>
                                <p class="text-xl font-bold text-blue-600">{{ formatCurrency(customer.opening_balance) }}</p>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <p class="text-xs text-gray-500 uppercase font-medium">Total Debit (Sales)</p>
                                <p class="text-xl font-bold text-red-600">{{ formatCurrency(totals.debit) }}</p>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <p class="text-xs text-gray-500 uppercase font-medium">Total Credit (Received)</p>
                                <p class="text-xl font-bold text-green-600">{{ formatCurrency(totals.credit) }}</p>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-emerald-200 bg-emerald-50">
                                <p class="text-xs text-emerald-600 uppercase font-medium">Closing Balance</p>
                                <p class="text-xl font-bold text-emerald-700">{{ formatCurrency(totals.balance) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Customer Selected -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                <div class="text-6xl mb-4">📒</div>
                <p>Select a customer or enter a customer code to view their ledger.</p>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
