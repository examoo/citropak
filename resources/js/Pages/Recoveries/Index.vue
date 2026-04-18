<script setup>
import { ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import { debounce } from 'lodash';

const props = defineProps({
    recoveries: Object,
    totalRecovered: Number,
    filters: Object,
});

const form = ref({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    search: props.filters.search || '',
});

const searchRecoveries = () => {
    router.get(route('recoveries.index'), {
        ...form.value,
        page: 1
    }, { 
        preserveState: true,
        replace: true
    });
};

// Debounced search for the text input
watch(() => form.value.search, debounce(() => {
    searchRecoveries();
}, 500));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { 
        style: 'decimal', 
        minimumFractionDigits: 2,
        maximumFractionDigits: 2 
    }).format(value);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', { 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric' 
    });
};

const clearFilters = () => {
    form.value = {
        date_from: '',
        date_to: '',
        search: '',
    };
    searchRecoveries();
};
</script>

<template>
    <Head title="Recoveries" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Recoveries</h1>
                    <p class="text-gray-500 text-sm mt-1">Manage and view all credit recovery records</p>
                </div>
                
                <div class="flex items-center gap-4 bg-white px-6 py-4 rounded-2xl shadow-sm border border-gray-100">
                    <div class="text-right">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Recovered</p>
                        <p class="text-2xl font-bold text-emerald-600">Rs. {{ formatCurrency(totalRecovered) }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                    <div class="md:col-span-1">
                        <InputLabel value="Search Invoice / Customer" />
                        <div class="mt-1 relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <TextInput 
                                v-model="form.search" 
                                placeholder="Type to search..." 
                                class="pl-10 w-full"
                            />
                        </div>
                    </div>
                    
                    <div>
                        <InputLabel value="From Date" />
                        <TextInput type="date" v-model="form.date_from" class="mt-1 w-full" @change="searchRecoveries" />
                    </div>
                    
                    <div>
                        <InputLabel value="To Date" />
                        <TextInput type="date" v-model="form.date_to" class="mt-1 w-full" @change="searchRecoveries" />
                    </div>
                    
                    <div class="flex gap-2">
                        <PrimaryButton @click="searchRecoveries" class="flex-1 bg-indigo-600 hover:bg-indigo-700 shadow-indigo-200 shadow-lg">
                            Apply Filters
                        </PrimaryButton>
                        <button @click="clearFilters" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50/50 text-xs uppercase font-bold text-gray-500 tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Recovery Date</th>
                                <th class="px-6 py-4">Invoice #</th>
                                <th class="px-6 py-4">Customer Details</th>
                                <th class="px-6 py-4">Van</th>
                                <th class="px-6 py-4 text-right">Amount</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 italic-rows">
                            <tr v-for="rec in recoveries.data" :key="rec.id" class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                    {{ formatDate(rec.recovery_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-indigo-600">
                                    {{ rec.invoice_number }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-gray-900">{{ rec.customer_name }}</span>
                                        <span class="text-xs text-gray-400 font-mono">{{ rec.customer_code }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-medium text-xs">
                                        {{ rec.van }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="font-bold text-emerald-600">Rs. {{ formatCurrency(rec.amount) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <!-- View Invoice Link -->
                                    <Link 
                                        :href="route('invoices.show', rec.id)" 
                                        class="p-2 text-gray-400 hover:text-indigo-600 transition-colors inline-block"
                                        title="View Invoice"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="recoveries.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2 text-gray-400">
                                        <svg class="w-12 h-12 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <span class="text-sm">No recognition records found. Try adjusting your filters.</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <Pagination :links="recoveries.links" />
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.italic-rows tr td {
    /* Subtle touch for a premium feel */
}
</style>
