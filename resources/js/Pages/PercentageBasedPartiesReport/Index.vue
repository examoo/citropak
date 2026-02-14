<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    reportData: Array,
    brands: Array,
    filters: Object,
    customers: Array,
});

const selectedCustomerIds = ref(props.filters.customer_ids || []);

const search = () => {
    router.get(route('percentage-based-parties-report.index'), {
        customer_ids: selectedCustomerIds.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const print = () => window.print();

const addCustomer = (customerId) => {
    if (customerId && !selectedCustomerIds.value.includes(customerId)) {
        selectedCustomerIds.value.push(customerId);
    }
};

const removeCustomer = (customerId) => {
    selectedCustomerIds.value = selectedCustomerIds.value.filter(id => id !== customerId);
};

const selectedCustomersNames = computed(() => {
    return selectedCustomerIds.value
        .map(id => props.customers.find(c => c.id === id))
        .filter(Boolean);
});

const clearAllFilters = () => {
    selectedCustomerIds.value = [];
    search();
};
</script>

<template>
    <Head title="Percentage-Based Parties Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">Percentage-Based Parties Report</h1>
                <p class="text-blue-100 text-sm mt-1">View all parties with percentage-based discounts and their brand-wise discount percentages.</p>
            </div>

            <!-- Filters and Actions -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex flex-col gap-4">
                    <!-- Multi-Select Customer Filter -->
                    <div>
                        <InputLabel value="Filter by Customers (Multiple Selection)" />
                        <div class="flex gap-2 mt-1">
                            <SearchableSelect
                                :options="customers"
                                option-value="id"
                                option-label="name"
                                placeholder="Select a customer to add..."
                                class="flex-1"
                                @update:modelValue="addCustomer"
                            />
                        </div>

                        <!-- Selected Customers Tags -->
                        <div v-if="selectedCustomersNames.length > 0" class="flex flex-wrap gap-2 mt-3">
                            <div
                                v-for="customer in selectedCustomersNames"
                                :key="customer.id"
                                class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm"
                            >
                                <span>{{ customer.name }}</span>
                                <button
                                    @click="removeCustomer(customer.id)"
                                    class="hover:text-blue-900 font-bold"
                                >
                                    ×
                                </button>
                            </div>
                            <button
                                @click="clearAllFilters"
                                class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm hover:bg-red-200"
                            >
                                Clear All
                            </button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3">
                        <PrimaryButton @click="search" class="bg-blue-600 hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Generate Report
                        </PrimaryButton>

                        <button
                            @click="print"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest transition"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print / PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Content -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Percentage-Based Parties</h3>
                    <div class="text-sm text-gray-500">
                        Total Parties: <span class="font-medium text-gray-700">{{ reportData.length }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-4 py-3 sticky left-0 bg-gray-50 z-10">Code</th>
                                <th class="px-4 py-3 sticky left-20 bg-gray-50 z-10">Shop Name</th>
                                <th class="px-4 py-3">General %</th>
                                <th class="px-4 py-3">Phone</th>
                                <th class="px-4 py-3">Van</th>
                                <th class="px-4 py-3">Route</th>
                                <th
                                    v-for="brand in brands"
                                    :key="brand.id"
                                    class="px-4 py-3 text-center bg-blue-50"
                                >
                                    {{ brand.name }} %
                                </th>
                                <th class="px-4 py-3 text-center bg-green-50">Brands w/ Discount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="party in reportData" :key="party.customer_id" class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 font-medium text-gray-700 sticky left-0 bg-white">{{ party.customer_code }}</td>
                                <td class="px-4 py-3 sticky left-20 bg-white">{{ party.shop_name }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-blue-600">{{ party.percentage || 0 }}%</td>
                                <td class="px-4 py-3 text-gray-600">{{ party.phone || '-' }}</td>
                                <td class="px-4 py-3">{{ party.van || '-' }}</td>
                                <td class="px-4 py-3">{{ party.route || '-' }}</td>
                                <td
                                    v-for="brandDiscount in party.brand_discounts"
                                    :key="brandDiscount.brand_id"
                                    class="px-4 py-3 text-center"
                                    :class="brandDiscount.percentage > 0 ? 'text-green-600 font-semibold bg-green-50' : 'text-gray-400'"
                                >
                                    {{ brandDiscount.percentage }}%
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-green-600">
                                    {{ party.total_brands_with_discount }}
                                </td>
                            </tr>
                            <tr v-if="reportData.length === 0">
                                <td :colspan="7 + brands.length" class="px-4 py-8 text-center text-gray-400">
                                    No percentage-based parties found.
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
    nav, header, aside, .fixed, .sticky, button, .no-print {
        display: none !important;
    }

    body, #app, main, .min-h-screen {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow: visible !important;
    }

    .bg-white, .shadow-sm, .rounded-xl, .border, .bg-gradient-to-r, .shadow-lg {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        color: black !important;
    }

    .text-white {
        color: black !important;
    }

    .overflow-x-auto, .overflow-hidden {
        overflow: visible !important;
        height: auto !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 9px !important;
    }

    th, td {
        white-space: normal !important;
        padding: 3px !important;
        border: 1px solid #ddd !important;
    }

    thead th {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
