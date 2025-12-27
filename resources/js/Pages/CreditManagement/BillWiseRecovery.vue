<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    recoveries: Array,
    totalRecovered: Number,
    filters: Object,
});

const form = ref({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const search = () => {
    router.get(route('credit-management.bill-wise-recovery'), form.value, { preserveState: true });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Bill-Wise Recovery" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">Bill-Wise Recovery</h1>
                <p class="text-green-100 text-sm">View recovery payments by invoice</p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex gap-4 items-end">
                    <div>
                        <InputLabel value="From Date" />
                        <input type="date" v-model="form.date_from" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <InputLabel value="To Date" />
                        <input type="date" v-model="form.date_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <PrimaryButton @click="search" class="bg-green-600 hover:bg-green-700">Search</PrimaryButton>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Invoice #</th>
                            <th class="px-4 py-3">Customer Code</th>
                            <th class="px-4 py-3">Customer Name</th>
                            <th class="px-4 py-3">Van</th>
                            <th class="px-4 py-3 text-right">Invoice Amount</th>
                            <th class="px-4 py-3 text-right">Recovery Amount</th>
                            <th class="px-4 py-3">Recovery Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="rec in recoveries" :key="rec.id" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 font-medium text-blue-600">{{ rec.invoice_number }}</td>
                            <td class="px-4 py-3">{{ rec.customer_code }}</td>
                            <td class="px-4 py-3">{{ rec.customer_name }}</td>
                            <td class="px-4 py-3">{{ rec.van }}</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(rec.invoice_amount) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-green-600">{{ formatCurrency(rec.recovery_amount) }}</td>
                            <td class="px-4 py-3">{{ formatDate(rec.recovery_date) }}</td>
                        </tr>
                        <tr v-if="recoveries.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">No recoveries found.</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right">Total Recovered:</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ formatCurrency(totalRecovered) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </DashboardLayout>
</template>
