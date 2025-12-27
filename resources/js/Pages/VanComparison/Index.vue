<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    van1: Object,
    van2: Object,
    customers: Array,
    totals: Object,
    filters: Object,
    vans: Array,
    years: Array,
});

const months = [
    { id: 1, name: 'January' },
    { id: 2, name: 'February' },
    { id: 3, name: 'March' },
    { id: 4, name: 'April' },
    { id: 5, name: 'May' },
    { id: 6, name: 'June' },
    { id: 7, name: 'July' },
    { id: 8, name: 'August' },
    { id: 9, name: 'September' },
    { id: 10, name: 'October' },
    { id: 11, name: 'November' },
    { id: 12, name: 'December' },
];

const form = ref({
    van1_id: props.filters.van1_id || '',
    van2_id: props.filters.van2_id || '',
    month: props.filters.month || new Date().getMonth() + 1,
    year: props.filters.year || new Date().getFullYear(),
});

const compare = () => {
    router.get(route('van-comparison.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const getMonthName = (monthNum) => {
    const month = months.find(m => m.id === monthNum);
    return month ? month.name : '';
};

const print = () => {
    window.print();
};
</script>

<template>

    <Head title="Van Comparison Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-4 rounded-xl shadow-lg no-print">
                <h1 class="text-xl font-bold">Van Comparison Report</h1>
                <p class="text-sm text-purple-100">Compare sales performance between two vans</p>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 no-print">
                <div class="flex flex-col md:flex-row gap-4 items-end flex-wrap">
                    <div class="flex-1 min-w-[150px]">
                        <InputLabel value="Select Van 1" />
                        <SearchableSelect v-model="form.van1_id" :options="vans" option-value="id"
                            option-label="name" placeholder="Select Van 1..." class="mt-1 block w-full" />
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <InputLabel value="Select Van 2" />
                        <SearchableSelect v-model="form.van2_id" :options="vans" option-value="id"
                            option-label="name" placeholder="Select Van 2..." class="mt-1 block w-full" />
                    </div>
                    <div class="min-w-[140px]">
                        <InputLabel value="Month" />
                        <select v-model="form.month"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                            <option v-for="m in months" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>
                    <div class="min-w-[100px]">
                        <InputLabel value="Year" />
                        <select v-model="form.year"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <PrimaryButton @click="compare" class="bg-blue-600 hover:bg-blue-700">
                        <span class="mr-1">📊</span> Compare
                    </PrimaryButton>
                </div>
            </div>

            <!-- Report Content -->
            <div v-if="van1 && van2" class="space-y-4">
                <!-- Report Header -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-4 flex justify-between items-center">
                        <div>
                            <h2 class="font-bold text-lg">Customer-wise Net Sales Comparison for {{ getMonthName(filters.month) }} {{ filters.year }}</h2>
                            <p class="text-purple-100 text-sm">Comparing {{ van1.code }} vs {{ van2.code }}</p>
                        </div>
                        <button @click="print"
                            class="bg-white text-purple-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-100 transition no-print">
                            🖨 Print Report
                        </button>
                    </div>

                    <!-- Print Header (visible only on print) -->
                    <div class="hidden print:block p-4 border-b">
                        <h1 class="text-2xl font-bold text-center">Van Comparison Report</h1>
                        <p class="text-center text-gray-600">{{ getMonthName(filters.month) }} {{ filters.year }}</p>
                        <p class="text-center text-gray-500 text-sm">{{ van1.code }} vs {{ van2.code }}</p>
                    </div>

                    <!-- Comparison Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Customer Code</th>
                                    <th class="px-4 py-3">Shop Name</th>
                                    <th class="px-4 py-3">Address</th>
                                    <th class="px-4 py-3 text-right bg-purple-50 text-purple-700">
                                        {{ van1.code }} (Net Sales)
                                    </th>
                                    <th class="px-4 py-3 text-right bg-indigo-50 text-indigo-700">
                                        {{ van2.code }} (Net Sales)
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(customer, idx) in customers" :key="idx" class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ customer.customer_code }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ customer.shop_name }}</td>
                                    <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ customer.address }}</td>
                                    <td class="px-4 py-3 text-right font-medium"
                                        :class="customer.van1_net_sales > 0 ? 'text-purple-600' : 'text-gray-400'">
                                        {{ customer.van1_net_sales > 0 ? formatCurrency(customer.van1_net_sales) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium"
                                        :class="customer.van2_net_sales > 0 ? 'text-indigo-600' : 'text-gray-400'">
                                        {{ customer.van2_net_sales > 0 ? formatCurrency(customer.van2_net_sales) : '-' }}
                                    </td>
                                </tr>
                                <tr v-if="customers.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                        No data found for the selected period.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-100 font-bold text-gray-900">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right">Total:</td>
                                    <td class="px-4 py-3 text-right text-purple-700">{{ formatCurrency(totals.van1) }}</td>
                                    <td class="px-4 py-3 text-right text-indigo-700">{{ formatCurrency(totals.van2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Summary Cards -->
                    <div class="p-4 bg-gray-50 border-t no-print">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white rounded-lg p-4 border border-purple-200">
                                <p class="text-xs text-purple-500 uppercase font-medium">{{ van1.code }} Total Sales</p>
                                <p class="text-2xl font-bold text-purple-600">{{ formatCurrency(totals.van1) }}</p>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-indigo-200">
                                <p class="text-xs text-indigo-500 uppercase font-medium">{{ van2.code }} Total Sales</p>
                                <p class="text-2xl font-bold text-indigo-600">{{ formatCurrency(totals.van2) }}</p>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-gray-200"
                                :class="totals.van1 > totals.van2 ? 'bg-purple-50' : 'bg-indigo-50'">
                                <p class="text-xs text-gray-500 uppercase font-medium">Difference</p>
                                <p class="text-2xl font-bold"
                                    :class="totals.van1 > totals.van2 ? 'text-purple-600' : 'text-indigo-600'">
                                    {{ formatCurrency(Math.abs(totals.van1 - totals.van2)) }}
                                    <span class="text-sm font-normal text-gray-500">
                                        ({{ totals.van1 > totals.van2 ? van1.code : van2.code }} leads)
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Vans Selected -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                <div class="text-6xl mb-4">🚐</div>
                <p>Select two vans and click "Compare" to view the comparison report.</p>
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
