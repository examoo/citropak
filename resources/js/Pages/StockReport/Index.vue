<script setup>
import { ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    report: Array,
    filters: Object,
    showDistributionColumn: Boolean,
});

const date = ref(props.filters.date || new Date().toISOString().split('T')[0]);
const search = ref(props.filters.search || '');

const updateReport = debounce(() => {
    router.get(route('stock-reports.index'), { date: date.value, search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch(date, updateReport);
watch(search, updateReport);

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
};
</script>

<template>
    <Head title="Stock Report" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Stock Report</h1>
                    <p class="text-gray-500 mt-1">Daily Opening, In, Out, and Closing/Available Stock</p>
                </div>
                <div class="flex items-center gap-3">
                    <input type="date" v-model="date" class="rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" />
                    
                    <div class="relative">
                        <input v-model="search" type="text" placeholder="Search product..." class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 w-64 shadow-sm">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Product</th>
                            <th v-if="showDistributionColumn" class="px-6 py-4">Distribution</th>
                            <th class="px-6 py-4 text-center">Opening</th>
                            <th class="px-6 py-4 text-center">Stock In</th>
                            <th class="px-6 py-4 text-center">Stock Out</th>
                            <th class="px-6 py-4 text-center">Closing / Avail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in report" :key="item.id" class="hover:bg-gray-50/50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ item.product_name }}</div>
                                <div class="text-xs text-gray-500">{{ item.product_code }}</div>
                            </td>
                            <td v-if="showDistributionColumn" class="px-6 py-4">
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-md text-xs">{{ item.distribution_name }}</span>
                            </td>
                            <td class="px-6 py-4 text-center font-medium text-purple-600">
                                {{ item.opening || '-' }}
                            </td>
                            <td class="px-6 py-4 text-center font-medium text-emerald-600">
                                {{ item.in || '-' }}
                            </td>
                            <td class="px-6 py-4 text-center font-medium text-rose-600">
                                {{ item.out || '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div v-if="item.is_closed" class="font-bold text-gray-900">
                                    {{ item.closing }} <span class="text-[10px] text-gray-400 font-normal uppercase ml-1">Closed</span>
                                </div>
                                <div v-else class="font-bold text-blue-600">
                                    {{ item.available ?? '-' }} <span class="text-[10px] text-blue-300 font-normal uppercase ml-1">Avail</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="report.length === 0">
                            <td :colspan="showDistributionColumn ? 6 : 5" class="px-6 py-12 text-center text-gray-400">
                                No products found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </DashboardLayout>
</template>
