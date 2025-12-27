<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    stats: Object,
    recentCreditInvoices: Array,
    recentRecoveries: Array,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>

    <Head title="Credit Management" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-amber-500 to-orange-600 text-white p-6 rounded-xl shadow-lg">
                <h1 class="text-2xl font-bold">Credit Management</h1>
                <p class="text-amber-100">Track credit sales, recoveries, and outstanding balances</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Credit Invoices</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.total_credit_invoices }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-2xl">📄</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Credit Amount</p>
                            <p class="text-2xl font-bold text-orange-600">{{ formatCurrency(stats.total_credit_amount) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                            <span class="text-2xl">💰</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Recovered</p>
                            <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.total_recovered) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="text-2xl">✅</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Pending Amount</p>
                            <p class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.pending_amount) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <span class="text-2xl">⏳</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gradient-to-br from-orange-500 to-amber-600 text-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold mb-2">This Month's Credit</h3>
                    <p class="text-3xl font-bold">{{ formatCurrency(stats.monthly_credit) }}</p>
                </div>
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 text-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold mb-2">This Month's Recovery</h3>
                    <p class="text-3xl font-bold">{{ formatCurrency(stats.monthly_recovery) }}</p>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    <Link :href="route('credit-management.entries')"
                        class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                        <span class="text-2xl mb-2">📋</span>
                        <span class="text-sm font-medium text-blue-700">Credit Entries</span>
                    </Link>
                    <Link :href="route('recoveries.index')"
                        class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                        <span class="text-2xl mb-2">💵</span>
                        <span class="text-sm font-medium text-green-700">Recovery</span>
                    </Link>
                    <Link :href="route('credit-management.summary')"
                        class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                        <span class="text-2xl mb-2">📊</span>
                        <span class="text-sm font-medium text-purple-700">Summary</span>
                    </Link>
                    <Link :href="route('credit-management.daily-report')"
                        class="flex flex-col items-center p-4 bg-amber-50 rounded-lg hover:bg-amber-100 transition">
                        <span class="text-2xl mb-2">📅</span>
                        <span class="text-sm font-medium text-amber-700">Daily Report</span>
                    </Link>
                    <Link :href="route('customer-ledgers.index')"
                        class="flex flex-col items-center p-4 bg-teal-50 rounded-lg hover:bg-teal-100 transition">
                        <span class="text-2xl mb-2">📒</span>
                        <span class="text-sm font-medium text-teal-700">Ledger</span>
                    </Link>
                    <Link :href="route('credit-management.search')"
                        class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <span class="text-2xl mb-2">🔍</span>
                        <span class="text-sm font-medium text-gray-700">Search</span>
                    </Link>
                </div>
            </div>

            <!-- Recent Data -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Credit Invoices -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-orange-500 text-white px-4 py-3">
                        <h3 class="font-bold">Recent Credit Invoices</h3>
                    </div>
                    <div class="p-4">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-gray-500 uppercase">
                                <tr>
                                    <th class="text-left py-2">Invoice</th>
                                    <th class="text-left py-2">Customer</th>
                                    <th class="text-right py-2">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="inv in recentCreditInvoices" :key="inv.id">
                                    <td class="py-2 font-medium text-blue-600">{{ inv.invoice_number }}</td>
                                    <td class="py-2 text-gray-600">{{ inv.customer_code }}</td>
                                    <td class="py-2 text-right font-medium">{{ formatCurrency(inv.amount) }}</td>
                                </tr>
                                <tr v-if="recentCreditInvoices.length === 0">
                                    <td colspan="3" class="py-4 text-center text-gray-400">No recent credit invoices</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Recoveries -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-green-500 text-white px-4 py-3">
                        <h3 class="font-bold">Recent Recoveries</h3>
                    </div>
                    <div class="p-4">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-gray-500 uppercase">
                                <tr>
                                    <th class="text-left py-2">Invoice</th>
                                    <th class="text-left py-2">Customer</th>
                                    <th class="text-right py-2">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="rec in recentRecoveries" :key="rec.id">
                                    <td class="py-2 font-medium text-green-600">{{ rec.invoice_number }}</td>
                                    <td class="py-2 text-gray-600">{{ rec.customer_name }}</td>
                                    <td class="py-2 text-right font-medium text-green-600">{{ formatCurrency(rec.amount) }}</td>
                                </tr>
                                <tr v-if="recentRecoveries.length === 0">
                                    <td colspan="3" class="py-4 text-center text-gray-400">No recent recoveries</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
