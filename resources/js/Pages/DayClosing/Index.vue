<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    data: Object,
    vans: Array,
    filters: Object,
});

const form = ref({
    date: props.filters.date || new Date().toISOString().split('T')[0],
    van: props.filters.van || '',
});

const search = () => {
    router.get(route('day-closing.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value || 0);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

const print = () => window.print();
</script>

<template>

    <Head title="Day Closing Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-slate-700 to-slate-900 text-white p-4 rounded-xl shadow-lg no-print">
                <h1 class="text-xl font-bold">📊 Day Closing Report</h1>
                <p class="text-slate-300 text-sm">Daily summary of sales, credit, and collections</p>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 no-print">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="min-w-[180px]">
                        <InputLabel value="Date" />
                        <input type="date" v-model="form.date"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-slate-500 focus:border-slate-500">
                    </div>
                    <div class="flex-1">
                        <InputLabel value="VAN (Optional)" />
                        <SearchableSelect v-model="form.van" :options="vans" option-value="code" option-label="name"
                            placeholder="All VANs..." class="mt-1" />
                    </div>
                    <PrimaryButton @click="search" class="bg-slate-700 hover:bg-slate-800">
                        Generate Report
                    </PrimaryButton>
                    <PrimaryButton @click="print" class="bg-gray-600 hover:bg-gray-700">
                        🖨 Print
                    </PrimaryButton>
                </div>
            </div>

            <!-- Report Content -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Print Header -->
                <div class="hidden print:block p-4 border-b text-center">
                    <h1 class="text-2xl font-bold">Day Closing Report</h1>
                    <p class="text-gray-600">{{ formatDate(data.date) }}</p>
                    <p v-if="data.van" class="text-gray-500">VAN: {{ data.van }}</p>
                </div>

                <!-- Summary Cards -->
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Summary for {{ formatDate(data.date) }}</h2>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                            <p class="text-xs text-green-600 uppercase font-medium">Cash Sales</p>
                            <p class="text-2xl font-bold text-green-700">{{ formatCurrency(data.cash_sales?.total) }}</p>
                            <p class="text-sm text-green-500">{{ data.cash_sales?.count || 0 }} invoices</p>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center">
                            <p class="text-xs text-orange-600 uppercase font-medium">Credit Sales</p>
                            <p class="text-2xl font-bold text-orange-700">{{ formatCurrency(data.credit_sales?.total) }}</p>
                            <p class="text-sm text-orange-500">{{ data.credit_sales?.count || 0 }} invoices</p>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                            <p class="text-xs text-blue-600 uppercase font-medium">Total Sales</p>
                            <p class="text-2xl font-bold text-blue-700">{{ formatCurrency(data.total_sales?.total) }}</p>
                            <p class="text-sm text-blue-500">{{ data.total_sales?.count || 0 }} invoices</p>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
                            <p class="text-xs text-purple-600 uppercase font-medium">Recovery</p>
                            <p class="text-2xl font-bold text-purple-700">{{ formatCurrency(data.recovery?.total) }}</p>
                            <p class="text-sm text-purple-500">{{ data.recovery?.count || 0 }} payments</p>
                        </div>
                        <div class="bg-slate-700 text-white rounded-lg p-4 text-center">
                            <p class="text-xs text-slate-300 uppercase font-medium">Net Collection</p>
                            <p class="text-2xl font-bold">{{ formatCurrency(data.net_collection) }}</p>
                            <p class="text-sm text-slate-400">Cash + Recovery</p>
                        </div>
                    </div>
                </div>

                <!-- Invoices Table -->
                <div class="border-t border-gray-100">
                    <div class="bg-gray-50 px-4 py-3">
                        <h3 class="font-bold text-gray-700">Sales Invoices ({{ data.invoices?.length || 0 }})</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-4 py-2 text-left">Invoice #</th>
                                    <th class="px-4 py-2 text-left">Customer</th>
                                    <th class="px-4 py-2 text-left">VAN</th>
                                    <th class="px-4 py-2 text-center">Type</th>
                                    <th class="px-4 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="inv in data.invoices" :key="inv.invoice_number">
                                    <td class="px-4 py-2 font-medium">{{ inv.invoice_number }}</td>
                                    <td class="px-4 py-2">{{ inv.customer_code }} - {{ inv.customer_name }}</td>
                                    <td class="px-4 py-2">{{ inv.van }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <span :class="inv.is_credit ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700'"
                                            class="px-2 py-0.5 rounded text-xs font-medium">
                                            {{ inv.is_credit ? 'Credit' : 'Cash' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right font-medium">{{ formatCurrency(inv.amount) }}</td>
                                </tr>
                                <tr v-if="!data.invoices?.length">
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">No invoices for this date.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recoveries Table -->
                <div class="border-t border-gray-100">
                    <div class="bg-gray-50 px-4 py-3">
                        <h3 class="font-bold text-gray-700">Recoveries ({{ data.recoveries?.length || 0 }})</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-4 py-2 text-left">Invoice #</th>
                                    <th class="px-4 py-2 text-left">Customer</th>
                                    <th class="px-4 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="rec in data.recoveries" :key="rec.invoice_number">
                                    <td class="px-4 py-2 font-medium text-purple-600">{{ rec.invoice_number }}</td>
                                    <td class="px-4 py-2">{{ rec.customer_code }} - {{ rec.customer_name }}</td>
                                    <td class="px-4 py-2 text-right font-bold text-purple-600">{{ formatCurrency(rec.amount) }}</td>
                                </tr>
                                <tr v-if="!data.recoveries?.length">
                                    <td colspan="3" class="px-4 py-6 text-center text-gray-400">No recoveries for this date.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Print Footer -->
                <div class="hidden print:block p-4 border-t text-xs text-right text-gray-400">
                    Printed on: {{ new Date().toLocaleString() }}
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print { .no-print { display: none !important; } }
</style>
