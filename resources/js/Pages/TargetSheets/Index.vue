<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    orderBookers: Object, // Grouped by distribution
    brands: Array,
    filters: Object,
    stats: Object
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

// Default to current month
const getCurrentMonth = () => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
};

const selectedMonth = ref(props.filters.month || getCurrentMonth());

watch(selectedMonth, (value) => {
    router.get(route('target-sheets.index'), { month: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
});

// Format amount with commas
const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PK', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
};

// Format month for display (2025-01 -> January 2025)
const formatMonth = (month) => {
    if (!month) return '-';
    const [year, m] = month.split('-');
    const date = new Date(year, parseInt(m) - 1);
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

// Get percentage color class
const getPercentageClass = (percentage) => {
    if (percentage >= 100) return 'text-green-600 bg-green-100';
    if (percentage >= 75) return 'text-blue-600 bg-blue-100';
    if (percentage >= 50) return 'text-yellow-600 bg-yellow-100';
    return 'text-red-600 bg-red-100';
};

const printSheet = () => {
    window.print();
};

// Helper to get brand totals for distribution header
const getBrandSummary = (bookers) => {
    const summary = {};
    props.brands.forEach(brand => {
        summary[brand.id] = {
            target: 0,
            achieved: 0
        };
    });

    bookers.forEach(booker => {
        booker.brand_details.forEach(detail => {
            if (summary[detail.id]) {
                summary[detail.id].target += parseFloat(detail.target || 0);
                summary[detail.id].achieved += parseFloat(detail.achieved || 0);
            }
        });
    });

    return summary;
};
</script>

<template>

    <Head title="Target Sheets" />

    <DashboardLayout>
        <div class="space-y-6 print:hidden">
            <!-- Header (Hidden in Print) -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Target Sheets</h1>
                    <p class="text-gray-500 mt-1">View and print monthly sales targets with achievement tracking.</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Month Selector -->
                    <input v-model="selectedMonth" type="month"
                        class="px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-sm">

                    <button @click="printSheet"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl font-medium shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 transition-all duration-200 hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Sheet
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="text-sm text-gray-500">Total Target</div>
                    <div class="text-xl font-bold text-emerald-600">Rs. {{ formatAmount(stats.total_target) }}</div>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="text-sm text-gray-500">Achieved</div>
                    <div class="text-xl font-bold text-blue-600">Rs. {{ formatAmount(stats.total_achieved) }}</div>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="text-sm text-gray-500">Completion</div>
                    <div class="text-xl font-bold"
                        :class="stats.overall_percentage >= 100 ? 'text-green-600' : 'text-orange-600'">
                        {{ stats.overall_percentage }}%
                    </div>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="text-sm text-gray-500">Total Bookers</div>
                    <div class="text-xl font-bold text-gray-900">{{ stats.total_bookers }}</div>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="text-sm text-gray-500">With Target</div>
                    <div class="text-xl font-bold text-green-600">{{ stats.bookers_with_target }}</div>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="text-sm text-gray-500">Without Target</div>
                    <div class="text-xl font-bold text-red-600">{{ stats.bookers_without_target }}</div>
                </div>
            </div>
        </div>

        <!-- Report Content (Visible in Print) -->
        <div
            class="mt-8 bg-white p-8 rounded-none md:rounded-2xl shadow-sm border border-gray-100 print:shadow-none print:border-0 print:p-0 print:mt-0">

            <!-- Report Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 uppercase">
                    Monthly Sales Targets & Achievement
                </h1>
                <h2 class="text-xl font-semibold text-gray-700 mt-2">{{ formatMonth(selectedMonth) }}</h2>
                <div class="mt-4 flex justify-center gap-8 text-lg">
                    <div>
                        <span class="font-bold">Target:</span>
                        <span class="text-emerald-600">Rs. {{ formatAmount(stats.total_target) }}</span>
                    </div>
                    <div>
                        <span class="font-bold">Achieved:</span>
                        <span class="text-blue-600">Rs. {{ formatAmount(stats.total_achieved) }}</span>
                    </div>
                    <div>
                        <span class="font-bold">Completion:</span>
                        <span :class="stats.overall_percentage >= 100 ? 'text-green-600' : 'text-orange-600'">
                            {{ stats.overall_percentage }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Targets Tables Grouped by Distribution -->
            <div class="space-y-8">
                <div v-if="Object.keys(orderBookers).length === 0" class="text-center text-gray-500 py-8">
                    No order bookers found.
                </div>

                <div v-for="(bookers, distribution) in orderBookers" :key="distribution" class="break-inside-avoid">
                    <!-- Distribution Header -->
                    <div class="bg-teal-600 text-white p-3 font-bold print:bg-teal-600 print:text-white mb-0">
                        <div class="flex flex-col gap-2">
                            <div class="flex justify-between border-b border-teal-500 pb-1">
                                <span>{{ distribution || 'Unknown Distribution' }}</span>
                                <span>
                                    TOTAL Target: Rs. {{formatAmount(bookers.reduce((sum, b) => sum +
                                    parseFloat(b.target_amount), 0)) }} |
                                    Achieved: Rs. {{formatAmount(bookers.reduce((sum, b) => sum +
                                    parseFloat(b.achieved_amount), 0)) }}
                                </span>
                            </div>
                            <!-- Brand Totals in Header -->
                            <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs font-normal">
                                <div v-for="brand in brands" :key="brand.id">
                                    <span class="text-teal-100">{{ brand.name }}:</span>
                                    Target Rs. {{ formatAmount(getBrandSummary(bookers)[brand.id].target) }} |
                                    Ach Rs. {{ formatAmount(getBrandSummary(bookers)[brand.id].achieved) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table
                            class="w-full text-left text-[10px] text-gray-800 border-collapse border border-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="border border-gray-200 px-1 py-1 w-6">#</th>
                                    <th class="border border-gray-200 px-1 py-1 w-12">OB</th>
                                    <th class="border border-gray-200 px-1 py-1 min-w-[100px]">Order Booker</th>
                                    <th class="border border-gray-200 px-1 py-1 w-12">VAN</th>
                                    <!-- Dynamic Brand Columns -->
                                    <template v-for="brand in brands" :key="brand.id">
                                        <th class="border border-gray-200 px-1 py-1 text-center bg-indigo-50/50"
                                            colspan="3">
                                            {{ brand.name }}
                                        </th>
                                    </template>
                                    <!-- Totals Column -->
                                    <th class="border border-gray-200 px-1 py-1 text-center bg-gray-100" colspan="3">
                                        GRAND TOTAL</th>
                                </tr>
                                <tr class="bg-gray-50/50 text-[9px]">
                                    <th class="border border-gray-200" colspan="4"></th>
                                    <template v-for="brand in brands" :key="brand.id">
                                        <th class="border border-gray-200 px-1 py-1 text-right">Tgt</th>
                                        <th class="border border-gray-200 px-1 py-1 text-right">Ach</th>
                                        <th class="border border-gray-200 px-1 py-1 text-center">%</th>
                                    </template>
                                    <th class="border border-gray-200 px-1 py-1 text-right bg-gray-100">Tgt</th>
                                    <th class="border border-gray-200 px-1 py-1 text-right bg-gray-100">Ach</th>
                                    <th class="border border-gray-200 px-1 py-1 text-center bg-gray-100">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(booker, index) in bookers" :key="booker.id"
                                    class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="border border-gray-200 px-1 py-1">{{ index + 1 }}</td>
                                    <td class="border border-gray-200 px-1 py-1 font-mono">{{ booker.code }}</td>
                                    <td class="border border-gray-200 px-1 py-1 font-medium">{{ booker.name }}</td>
                                    <td class="border border-gray-200 px-1 py-1">{{ booker.van || '-' }}</td>

                                    <!-- Brand Data -->
                                    <template v-for="brand in booker.brand_details" :key="brand.id">
                                        <td
                                            class="border border-gray-200 px-1 py-1 text-right text-gray-500 font-medium">
                                            {{ formatAmount(brand.target) }}
                                        </td>
                                        <td class="border border-gray-200 px-1 py-1 text-right font-bold text-blue-700">
                                            {{ formatAmount(brand.achieved) }}
                                        </td>
                                        <td class="border border-gray-200 px-1 py-1 text-center font-bold"
                                            :class="getPercentageClass(brand.percentage)">
                                            {{ Math.round(brand.percentage) }}%
                                        </td>
                                    </template>

                                    <!-- Totals -->
                                    <td
                                        class="border border-gray-200 px-1 py-1 text-right font-bold text-emerald-700 bg-gray-50/50">
                                        {{ formatAmount(booker.target_amount) }}
                                    </td>
                                    <td
                                        class="border border-gray-200 px-1 py-1 text-right font-bold text-blue-900 bg-gray-50/50">
                                        {{ formatAmount(booker.achieved_amount) }}
                                    </td>
                                    <td class="border border-gray-200 px-1 py-1 text-center font-bold bg-gray-50/50">
                                        <span class="px-1 rounded" :class="getPercentageClass(booker.percentage)">
                                            {{ Math.round(booker.percentage) }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer with Timestamp (Print Only) -->
            <div class="hidden print:block mt-8 text-xs text-right text-gray-400">
                Printed on: {{ new Date().toLocaleString() }}
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print {
    @page {
        size: landscape;
        margin: 8mm;
    }

    /* Hide Sidebar and Navigation */
    :deep(aside),
    :deep(nav),
    button {
        display: none !important;
    }

    /* Reset margins for main content area to use full width */
    :deep(main) {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    /* Ensure backgrounds print */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Ensure table borders are sharp */
    table,
    th,
    td {
        border-color: #000 !important;
    }
}
</style>
