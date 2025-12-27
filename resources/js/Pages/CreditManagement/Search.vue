<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    results: Array,
    query: String,
});

const searchQuery = ref(props.query || '');

const search = () => {
    router.get(route('credit-management.search'), { q: searchQuery.value }, { preserveState: true });
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
    <Head title="Credit Search" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-gray-700 to-gray-900 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">🔍 Search Credit Data</h1>
                <p class="text-gray-300 text-sm">Search by invoice number, customer code, or customer name</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex gap-4">
                    <TextInput v-model="searchQuery" type="text" placeholder="Enter invoice number, customer code, or name..."
                        class="flex-1" @keyup.enter="search" />
                    <PrimaryButton @click="search" class="bg-gray-800 hover:bg-gray-900">
                        Search
                    </PrimaryButton>
                </div>
            </div>

            <div v-if="query" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b">
                    <p class="text-sm text-gray-600">
                        Found <strong>{{ results.length }}</strong> results for "<strong>{{ query }}</strong>"
                    </p>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Customer Code</th>
                            <th class="px-4 py-3">Customer Name</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in results" :key="item.id" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                    {{ item.type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium text-blue-600">{{ item.reference }}</td>
                            <td class="px-4 py-3">{{ item.customer_code }}</td>
                            <td class="px-4 py-3">{{ item.customer_name }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(item.amount) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ formatDate(item.date) }}</td>
                        </tr>
                        <tr v-if="results.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">No results found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                <div class="text-6xl mb-4">🔍</div>
                <p>Enter a search term to find credit invoices and recoveries.</p>
            </div>
        </div>
    </DashboardLayout>
</template>
