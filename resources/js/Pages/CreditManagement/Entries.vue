<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    invoices: Object,
    filters: Object,
});

const form = ref({
    customer_code: props.filters.customer_code || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const search = () => {
    router.get(route('credit-management.entries'), form.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

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

    <Head title="Credit Entries" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">Credit Entries</h1>
                <p class="text-blue-100 text-sm">View all credit invoices and their recovery status</p>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <InputLabel value="Customer Code" />
                        <TextInput v-model="form.customer_code" type="text" placeholder="Search by code..."
                            class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="From Date" />
                        <input type="date" v-model="form.date_from"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <InputLabel value="To Date" />
                        <input type="date" v-model="form.date_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <PrimaryButton @click="search" class="bg-blue-600 hover:bg-blue-700">
                        Search
                    </PrimaryButton>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Invoice #</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Customer Code</th>
                                <th class="px-4 py-3">Customer Name</th>
                                <th class="px-4 py-3">Van</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-right">Recovered</th>
                                <th class="px-4 py-3 text-right">Pending</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 font-medium text-blue-600">{{ inv.invoice_number }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatDate(inv.date) }}</td>
                                <td class="px-4 py-3 font-medium">{{ inv.customer_code }}</td>
                                <td class="px-4 py-3">{{ inv.customer_name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ inv.van }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(inv.amount) }}</td>
                                <td class="px-4 py-3 text-right text-green-600 font-medium">{{ formatCurrency(inv.recovered) }}</td>
                                <td class="px-4 py-3 text-right font-bold"
                                    :class="inv.pending > 0 ? 'text-red-600' : 'text-green-600'">
                                    {{ formatCurrency(inv.pending) }}
                                </td>
                            </tr>
                            <tr v-if="invoices.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                    No credit entries found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="invoices.links && invoices.links.length > 3"
                    class="px-4 py-3 border-t border-gray-100 flex justify-center gap-1">
                    <template v-for="link in invoices.links" :key="link.label">
                        <button v-if="link.url" @click="router.get(link.url)"
                            :class="link.active ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                            class="px-3 py-1 rounded text-sm" v-html="link.label">
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
