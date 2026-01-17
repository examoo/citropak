<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    report: Array,
    filters: Object,
    showDistributionColumn: Boolean,
});

const search = ref(props.filters.search || '');

const updateReport = debounce(() => {
    router.get(route('low-stock-reports.index'), { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch(search, updateReport);

const print = () => window.print();

const exportExcel = () => {
    const params = new URLSearchParams({
        search: search.value
    });
    window.location.href = route('low-stock-report.export') + '?' + params.toString();
};
</script>

<template>

    <Head title="Low Stock Report" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Low Stock Report</h1>
                    <p class="text-gray-500 mt-1">Products with available stock below minimum quantity</p>
                </div>
                <div class="flex items-center gap-3 no-print">
                    <div class="relative">
                        <input v-model="search" type="text" placeholder="Search product..."
                            class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 w-64 shadow-sm">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <div class="flex gap-2">
                        <button @click="exportExcel"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                            Excel
                        </button>
                        <button @click="print"
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                            Print
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Product</th>
                            <th v-if="showDistributionColumn" class="px-6 py-4">Distribution</th>
                            <th class="px-6 py-4 text-center">Min Qty</th>
                            <th class="px-6 py-4 text-center">Avail Qty</th>
                            <th class="px-6 py-4 text-center">Deficit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in report" :key="item.id" class="hover:bg-gray-50/50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ item.name }}</div>
                                <div class="text-xs text-gray-500">{{ item.code }}</div>
                            </td>
                            <td v-if="showDistributionColumn" class="px-6 py-4">
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-md text-xs">{{
                                    item.distribution_name }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 bg-gray-100 rounded text-xs font-semibold">{{ item.min_qty
                                }}</span>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-rose-600">
                                {{ item.available }}
                            </td>
                            <td class="px-6 py-4 text-center text-rose-500">
                                {{ (item.min_qty - item.available).toFixed(0) }}
                            </td>
                        </tr>
                        <tr v-if="report.length === 0">
                            <td :colspan="showDistributionColumn ? 5 : 4" class="px-6 py-12 text-center text-gray-400">
                                No low stock products found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </DashboardLayout>
</template>
<style scoped>
@media print {
    .no-print {
        display: none !important;
    }

    body,
    #app,
    main {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        overflow: visible !important;
    }

    .bg-white,
    .shadow-sm,
    .rounded-2xl,
    .border {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
    }

    table {
        width: 100% !important;
        font-size: 10px !important;
    }

    th,
    td {
        padding: 4px !important;
    }
}
</style>
