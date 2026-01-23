<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    visits: Array,
    orderBookers: Array,
    filters: Object,
});

const form = ref({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    order_booker_id: props.filters.order_booker_id || '',
});

const search = () => {
    router.get(route('productivity-report.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Productivity Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center no-print">
                <h1 class="text-2xl font-bold text-gray-800">Productivity Report</h1>
                <Link href="/dashboard" class="text-blue-600 hover:text-blue-800 text-sm">
                    ← Back to Dashboard
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 no-print">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/4">
                        <InputLabel value="Date From" />
                        <input type="date" v-model="form.date_from"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="w-full md:w-1/4">
                        <InputLabel value="Date To" />
                        <input type="date" v-model="form.date_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="w-full md:w-1/4">
                        <InputLabel value="Order Booker" />
                        <select v-model="form.order_booker_id"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Order Bookers</option>
                            <option v-for="ob in orderBookers" :key="ob.id" :value="ob.id">{{ ob.name }}</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <PrimaryButton @click="search" class="bg-blue-600 hover:bg-blue-700">
                            Search
                        </PrimaryButton>
                        <button @click="printReport"
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                            Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                 <!-- Print Header (Visible only in print) -->
                <div class="hidden print:block text-center p-4 border-b border-gray-200">
                    <h1 class="text-xl font-bold">Productivity Report</h1>
                    <p class="text-sm text-gray-600">
                        {{ form.date_from }} to {{ form.date_to }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-indigo-50 text-gray-700 uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Order Booker</th>
                                <th class="px-4 py-3">Shop Name</th>
                                <th class="px-4 py-3">Address</th>
                                <th class="px-4 py-3">Check In</th>
                                <th class="px-4 py-3">Check Out</th>
                                <th class="px-4 py-3">Total Time</th>
                                <th class="px-4 py-3">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="visit in visits" :key="visit.id" class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ visit.order_booker_name }}</td>
                                <td class="px-4 py-3 font-semibold">{{ visit.shop_name }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 truncate max-w-xs">{{ visit.shop_address }}</td>
                                <td class="px-4 py-3 text-green-700">{{ visit.check_in_time }}</td>
                                <td class="px-4 py-3 text-red-700">{{ visit.check_out_time }}</td>
                                <td class="px-4 py-3 font-bold text-gray-800">{{ visit.duration }}</td>
                                <td class="px-4 py-3 text-xs italic">{{ visit.notes }}</td>
                            </tr>
                            <tr v-if="visits.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    No visits found for the selected criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    
    body {
        font-size: 12px;
    }
    
    table {
        width: 100% !important;
    }
}
</style>
