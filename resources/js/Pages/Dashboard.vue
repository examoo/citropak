<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

// Stats data
const allStats = [
    {
        title: 'Total Sales',
        value: '842,003.77',
        prefix: 'Rs.',
        color: 'from-emerald-500 to-teal-500',
        shadowColor: 'shadow-emerald-500/30',
        icon: 'sales',
        permission: 'orders.view'
    },
    {
        title: 'Orders This Month',
        value: '0',
        prefix: '',
        color: 'from-amber-500 to-orange-500',
        shadowColor: 'shadow-amber-500/30',
        icon: 'orders',
        permission: 'orders.view'
    },
    {
        title: 'Total Credit',
        value: '16,260,821.00',
        prefix: 'Rs.',
        color: 'from-blue-500 to-indigo-500',
        shadowColor: 'shadow-blue-500/30',
        icon: 'credit',
        permission: 'invoices.view'
    },
    {
        title: 'Total Products',
        value: '1,245',
        prefix: '',
        color: 'from-purple-500 to-pink-500',
        shadowColor: 'shadow-purple-500/30',
        icon: 'products',
        permission: 'products.view'
    }
];

const stats = computed(() => {
    return allStats.filter(stat => !stat.permission || can(stat.permission));
});

// Quick Actions
const allQuickActions = [
    { name: 'New Order', icon: 'order', color: 'from-emerald-500 to-teal-500', permission: 'orders.create' },
    { name: 'Add Product', icon: 'product', color: 'from-blue-500 to-indigo-500', permission: 'products.create' },
    { name: 'Create Invoice', icon: 'invoice', color: 'from-amber-500 to-orange-500', permission: 'invoices.create' },
    { name: 'View Reports', icon: 'report', color: 'from-purple-500 to-pink-500', permission: 'reports.view' },
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
                <button 
                    v-if="can('orders.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 transition-all duration-200 hover:-translate-y-0.5"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    New Order
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div 
                    v-for="stat in stats" 
                    :key="stat.title"
                    class="group relative bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
                >
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
                        <div :class="['w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-lg', stat.color, stat.shadowColor]">
                            <!-- Sales Icon -->
                            <svg v-if="stat.icon === 'sales'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Orders Icon -->
                            <svg v-else-if="stat.icon === 'orders'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <!-- Credit Icon -->
                            <svg v-else-if="stat.icon === 'credit'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <!-- Products Icon -->
                            <svg v-else-if="stat.icon === 'products'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Trend -->
                    <div class="mt-4 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            12%
                        </span>
                        <span class="text-xs text-gray-500">vs last month</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Audit -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Quick Actions -->
                <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <button 
                            v-for="action in quickActions"
                            :key="action.name"
                            class="group flex flex-col items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-gray-200 hover:shadow-lg hover:-translate-y-1 transition-all duration-200"
                        >
                            <div :class="['w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-md group-hover:shadow-lg transition-shadow', action.color]">
                                <!-- Order Icon -->
                                <svg v-if="action.icon === 'order'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                <!-- Product Icon -->
                                <svg v-else-if="action.icon === 'product'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <!-- Invoice Icon -->
                                <svg v-else-if="action.icon === 'invoice'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <!-- Report Icon -->
                                <svg v-else-if="action.icon === 'report'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">{{ action.name }}</span>
                        </button>
                    </div>
                </div>

                <!-- Audit Trail -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Audit File</h3>
                    <div class="space-y-4">
                        <div class="p-4 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">View Audit Trail</p>
                                    <p class="text-sm text-gray-500">Track all system activities</p>
                                </div>
                            </div>
                        </div>
                        
                        <button class="w-full py-3 px-4 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-200 hover:-translate-y-0.5">
                            View All Logs
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                    <button class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">View All</button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                <th class="pb-3 font-medium">Activity</th>
                                <th class="pb-3 font-medium">User</th>
                                <th class="pb-3 font-medium">Date</th>
                                <th class="pb-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <span class="font-medium text-gray-900">Order #12345 created</span>
                                    </div>
                                </td>
                                <td class="text-gray-600">Admin</td>
                                <td class="text-gray-600">Just now</td>
                                <td><span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-medium">Completed</span></td>
                            </tr>
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </div>
                                        <span class="font-medium text-gray-900">New product added</span>
                                    </div>
                                </td>
                                <td class="text-gray-600">Admin</td>
                                <td class="text-gray-600">2 hours ago</td>
                                <td><span class="px-2.5 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">New</span></td>
                            </tr>
                            <tr class="hover:bg-gray-50/50">
                                <td class="py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <span class="font-medium text-gray-900">Low stock alert</span>
                                    </div>
                                </td>
                                <td class="text-gray-600">System</td>
                                <td class="text-gray-600">5 hours ago</td>
                                <td><span class="px-2.5 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">Warning</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
