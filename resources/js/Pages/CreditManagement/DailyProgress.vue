<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    dailyData: Array,
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
    router.get(route('credit-management.daily-progress'), form.value, { preserveState: true });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
};

const getMonthName = (m) => months.find(x => x.id === m)?.name || '';
</script>

<template>
    <Head title="Daily Credit Progress" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-indigo-600 to-violet-600 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">Daily Credit Progress</h1>
                <p class="text-indigo-100 text-sm">Track daily credit and recovery for {{ getMonthName(filters.month) }} {{ filters.year }}</p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
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
                    <PrimaryButton @click="search" class="bg-indigo-600 hover:bg-indigo-700">Generate</PrimaryButton>
                </div>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-orange-500 text-white rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold">Total Credit</h3>
                    <p class="text-3xl font-bold">{{ formatCurrency(totals.credit) }}</p>
                </div>
                <div class="bg-green-500 text-white rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold">Total Recovery</h3>
                    <p class="text-3xl font-bold">{{ formatCurrency(totals.recovery) }}</p>
                </div>
                <div class="bg-indigo-500 text-white rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold">Net Change</h3>
                    <p class="text-3xl font-bold">{{ formatCurrency(totals.credit - totals.recovery) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-right">Credit</th>
                            <th class="px-4 py-3 text-right">Recovery</th>
                            <th class="px-4 py-3 text-right">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="day in dailyData" :key="day.date" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 font-medium">{{ formatDate(day.date) }}</td>
                            <td class="px-4 py-3 text-right text-orange-600">{{ formatCurrency(day.credit) }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ formatCurrency(day.recovery) }}</td>
                            <td class="px-4 py-3 text-right font-bold" :class="day.net > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatCurrency(day.net) }}
                            </td>
                        </tr>
                        <tr v-if="dailyData.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400">No data for this month.</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr>
                            <td class="px-4 py-3">Total</td>
                            <td class="px-4 py-3 text-right text-orange-600">{{ formatCurrency(totals.credit) }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ formatCurrency(totals.recovery) }}</td>
                            <td class="px-4 py-3 text-right" :class="totals.credit - totals.recovery > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatCurrency(totals.credit - totals.recovery) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </DashboardLayout>
</template>
