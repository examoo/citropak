<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

// Props from backend
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalSales: '0.00',
            invoicesThisMonth: 0,
            totalCredit: '0.00',
            totalProducts: '0',
        })
    },
    recentActivity: {
        type: Array,
        default: () => []
    }
});

// Stats configuration with real data from props
const allStats = computed(() => [
    {
        title: 'Total Sales',
        value: props.stats.totalSales,
        prefix: 'Rs.',
        color: 'from-emerald-500 to-teal-500',
        shadowColor: 'shadow-emerald-500/30',
        icon: 'sales',
        permission: 'invoices.view'
    },
    {
        title: 'Invoices This Month',
        value: props.stats.invoicesThisMonth,
        prefix: '',
        color: 'from-amber-500 to-orange-500',
        shadowColor: 'shadow-amber-500/30',
        icon: 'invoices',
        permission: 'invoices.view'
    },
    {
        title: 'Total Credit',
        value: props.stats.totalCredit,
        prefix: 'Rs.',
        color: 'from-blue-500 to-indigo-500',
        shadowColor: 'shadow-blue-500/30',
        icon: 'credit',
        permission: 'invoices.view'
    },
    {
        title: 'Total Products',
        value: props.stats.totalProducts,
        prefix: '',
        color: 'from-purple-500 to-pink-500',
        shadowColor: 'shadow-purple-500/30',
        icon: 'products',
        permission: 'products.view'
    }
]);

const stats = computed(() => {
    return allStats.value.filter(stat => !stat.permission || can(stat.permission));
});

// Quick Actions with routes
const allQuickActions = [
    { name: 'New Invoice', icon: 'invoice', color: 'from-emerald-500 to-teal-500', permission: 'invoices.create', route: 'invoices.create' },
    { name: 'Add Product', icon: 'product', color: 'from-blue-500 to-indigo-500', permission: 'products.create', route: 'products.create' },
    { name: 'View Invoices', icon: 'list', color: 'from-amber-500 to-orange-500', permission: 'invoices.view', route: 'invoices.index' },
    { name: 'View Reports', icon: 'report', color: 'from-purple-500 to-pink-500', permission: 'invoices.view', route: 'sales-reports.index' },
];

const quickActions = computed(() => {
    return allQuickActions.filter(action => !action.permission || can(action.permission));
});
</script>

<template>

    <Head title="Dashboard" />

    <DashboardLayout>
        <div class="space-y-6">

            <!-- Welcome Section -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Welcome back! 👋</h1>
                    <p class="text-gray-500 mt-1">Here's what's happening with your business today.</p>
                </div>
                <Link v-if="can('invoices.create')" :href="route('invoices.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 transition-all duration-200 hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    New Invoice
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="stat in stats" :key="stat.title"
                    class="group relative bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                    <!-- Gradient Accent -->
                    <div :class="['absolute inset-x-0 top-0 h-1 rounded-t-2xl bg-gradient-to-r', stat.color]"></div>

                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ stat.title }}</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">
                                <span class="text-sm font-normal text-gray-500">{{ stat.prefix }}</span>
                                {{ stat.value }}
                            </p>
                        </div>
                        <div
                            :class="['w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-lg', stat.color, stat.shadowColor]">
                            <!-- Sales Icon -->
                            <svg v-if="stat.icon === 'sales'" class="w-6 h-6 text-white" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Invoices Icon -->
                            <svg v-else-if="stat.icon === 'invoices'" class="w-6 h-6 text-white" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <!-- Credit Icon -->
                            <svg v-else-if="stat.icon === 'credit'" class="w-6 h-6 text-white" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <!-- Products Icon -->
                            <svg v-else-if="stat.icon === 'products'" class="w-6 h-6 text-white" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Audit -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Quick Actions -->
                <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <Link v-for="action in quickActions" :key="action.name" :href="route(action.route)"
                            class="group flex flex-col items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-gray-200 hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                            <div
                                :class="['w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-md group-hover:shadow-lg transition-shadow', action.color]">
                                <!-- Invoice Icon -->
                                <svg v-if="action.icon === 'invoice'" class="w-6 h-6 text-white" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                <!-- Product Icon -->
                                <svg v-else-if="action.icon === 'product'" class="w-6 h-6 text-white" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <!-- List Icon -->
                                <svg v-else-if="action.icon === 'list'" class="w-6 h-6 text-white" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <!-- Report Icon -->
                                <svg v-else-if="action.icon === 'report'" class="w-6 h-6 text-white" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">{{ action.name
                                }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Audit Trail -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Audit File</h3>
                    <div class="space-y-4">
                        <div
                            class="p-4 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">View Audit Trail</p>
                                    <p class="text-sm text-gray-500">Track all system activities</p>
                                </div>
                            </div>
                        </div>

                        <button
                            class="w-full py-3 px-4 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-200 hover:-translate-y-0.5">
                            View All Logs
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Invoices</h3>
                    <Link :href="route('invoices.index')"
                        class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">View All</Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full" v-if="recentActivity.length > 0">
                        <thead>
                            <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                <th class="pb-3 font-medium">Invoice</th>
                                <th class="pb-3 font-medium">Customer</th>
                                <th class="pb-3 font-medium">Amount</th>
                                <th class="pb-3 font-medium">Created By</th>
                                <th class="pb-3 font-medium">Date</th>
                                <th class="pb-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr v-for="activity in recentActivity" :key="activity.id"
                                class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <span class="font-medium text-gray-900">{{ activity.invoice_number }}</span>
                                    </div>
                                </td>
                                <td class="text-gray-600">{{ activity.customer_name }}</td>
                                <td class="text-gray-900 font-medium">Rs. {{ activity.total_amount }}</td>
                                <td class="text-gray-600">{{ activity.created_by }}</td>
                                <td class="text-gray-600">{{ activity.created_at }}</td>
                                <td>
                                    <span :class="activity.is_credit
                                        ? 'bg-amber-100 text-amber-700'
                                        : 'bg-emerald-100 text-emerald-700'"
                                        class="px-2.5 py-1 rounded-full text-xs font-medium">
                                        {{ activity.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-else class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-500">No recent invoices found</p>
                        <Link v-if="can('invoices.create')" :href="route('invoices.create')"
                            class="inline-flex items-center gap-2 mt-3 text-indigo-600 hover:text-indigo-700 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Create your first invoice
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
