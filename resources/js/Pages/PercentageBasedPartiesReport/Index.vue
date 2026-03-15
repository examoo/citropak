<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    reportData: Array,
    brands: Array,       // all brands (for filter dropdown)
    filters: Object,
    customers: Array,
});

// ─── Customer filter state ────────────────────────────────────────────
const selectedCustomerIds = ref(props.filters.customer_ids || []);

// ─── Date filter state ────────────────────────────────────────────────
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

// ─── Brand filter state ───────────────────────────────────────────────
const selectedBrandIds = ref((props.filters.brand_ids || []).map(Number));

const addBrand = (brandId) => {
    if (brandId && !selectedBrandIds.value.includes(Number(brandId))) {
        selectedBrandIds.value.push(Number(brandId));
    }
};
const removeBrand = (brandId) => {
    selectedBrandIds.value = selectedBrandIds.value.filter(id => id !== Number(brandId));
};
const selectedBrandNames = computed(() =>
    selectedBrandIds.value
        .map(id => props.brands.find(b => b.id === id))
        .filter(Boolean)
);

// ─── Customer filter state ────────────────────────────────────────────
const addCustomer = (customerId) => {
    if (customerId && !selectedCustomerIds.value.includes(customerId)) {
        selectedCustomerIds.value.push(customerId);
    }

};
const removeCustomer = (customerId) => {
    selectedCustomerIds.value = selectedCustomerIds.value.filter(id => id !== customerId);
};
const selectedCustomersNames = computed(() =>
    selectedCustomerIds.value
        .map(id => props.customers.find(c => c.id === id))
        .filter(Boolean)
);

// ─── Actions ──────────────────────────────────────────────────────────
const search = () => {
    router.get(route('percentage-based-parties-report.index'), {
        customer_ids: selectedCustomerIds.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        brand_ids: selectedBrandIds.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const clearAllFilters = () => {
    selectedCustomerIds.value = [];
    selectedBrandIds.value = [];
    dateFrom.value = '';
    dateTo.value = '';
    search();
};

const print = () => window.print();

// ─── Formatting ───────────────────────────────────────────────────────
const formatCurrency = (value) =>
    new Intl.NumberFormat('en-PK').format(value || 0);
</script>

<template>

    <Head title="Percentage-Based Parties Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">Percentage-Based Parties Report</h1>
                <p class="text-blue-100 text-sm mt-1">
                    Brand-wise discount % and actual sale value for percentage-based parties.
                </p>
            </div>

            <!-- Filters and Actions -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 print:hidden">
                <div class="flex flex-col gap-4">

                    <!-- Row 1: Date Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Date From" />
                            <input type="date" v-model="dateFrom"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
                        </div>
                        <div>
                            <InputLabel value="Date To" />
                            <input type="date" v-model="dateTo"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
                        </div>
                    </div>

                    <!-- Row 2: Brand Filter -->
                    <div>
                        <InputLabel value="Filter by Brands (show only selected brand columns)" />
                        <div class="flex gap-2 mt-1">
                            <SearchableSelect :options="brands" option-value="id" option-label="name"
                                placeholder="Select a brand to add..." class="flex-1" @update:modelValue="addBrand" />
                        </div>
                        <!-- Selected Brand Tags -->
                        <div v-if="selectedBrandNames.length > 0" class="flex flex-wrap gap-2 mt-3">
                            <div v-for="brand in selectedBrandNames" :key="brand.id"
                                class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm">
                                <span>{{ brand.name }}</span>
                                <button @click="removeBrand(brand.id)"
                                    class="hover:text-indigo-900 font-bold">×</button>
                            </div>
                        </div>
                        <p v-else class="text-xs text-gray-400 mt-1">Leave empty to show all brands</p>
                    </div>

                    <!-- Row 3: Customer Filter -->
                    <div>
                        <InputLabel value="Filter by Customers (Multiple Selection)" />
                        <div class="flex gap-2 mt-1">
                            <SearchableSelect :options="customers" option-value="id" option-label="name"
                                placeholder="Select a customer to add..." class="flex-1"
                                @update:modelValue="addCustomer" />
                        </div>
                        <!-- Selected Customer Tags -->
                        <div v-if="selectedCustomersNames.length > 0" class="flex flex-wrap gap-2 mt-3">
                            <div v-for="customer in selectedCustomersNames" :key="customer.id"
                                class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                                <span>{{ customer.name }}</span>
                                <button @click="removeCustomer(customer.id)"
                                    class="hover:text-blue-900 font-bold">×</button>
                            </div>
                            <button @click="clearAllFilters"
                                class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm hover:bg-red-200">
                                Clear All
                            </button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3">
                        <PrimaryButton @click="search" class="bg-blue-600 hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Generate Report
                        </PrimaryButton>

                        <button @click="print"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print / PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Content -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

                <!-- Print Header (hidden on screen) -->
                <div class="hidden print:block p-4 border-b text-center">
                    <h1 class="text-lg font-bold">Percentage-Based Parties Report</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Period: {{ filters.date_from }} to {{ filters.date_to }}
                    </p>
                </div>

                <div class="p-4 border-b border-gray-200 flex justify-between items-center print:hidden">
                    <h3 class="font-bold text-gray-800">Percentage-Based Parties</h3>
                    <div class="text-sm text-gray-500">
                        Total Parties: <span class="font-medium text-gray-700">{{ reportData.length }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-3 py-2 sticky left-0 bg-gray-50 z-10">Code</th>
                                <th class="px-3 py-2 sticky left-20 bg-gray-50 z-10">Shop Name</th>
                                <th class="px-3 py-2">General %</th>
                                <th class="px-3 py-2">Phone</th>
                                <th class="px-3 py-2">Van</th>
                                <th class="px-3 py-2">Route</th>
                                <th class="px-3 py-2 text-right">Total Sale</th>
                                <!-- Brand columns -->
                                <th v-for="brand in brands.filter(b => filters.brand_ids.length === 0 || filters.brand_ids.map(Number).includes(b.id))"
                                    :key="brand.id" class="px-3 py-2 text-center bg-blue-50 min-w-[130px]">
                                    {{ brand.name }}
                                </th>
                                <th class="px-3 py-2 text-center bg-green-50">Brands w/ Disc.</th>
                            </tr>
                            <!-- Sub-header -->
                            <tr class="text-gray-400">
                                <th colspan="7"></th>
                                <th v-for="brand in brands.filter(b => filters.brand_ids.length === 0 || filters.brand_ids.map(Number).includes(b.id))"
                                    :key="brand.id"
                                    class="px-3 py-1 text-center bg-blue-50 font-normal text-xs border-t border-blue-100">
                                    <span class="text-purple-600">Disc%</span> |
                                    <span class="text-green-600">Sale</span> |
                                    <span class="text-blue-600">Sale%</span>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="party in reportData" :key="party.customer_id" class="hover:bg-gray-50/50">
                                <td class="px-3 py-2 font-medium text-gray-700 sticky left-0 bg-white">{{
                                    party.customer_code }}</td>
                                <td class="px-3 py-2 sticky left-20 bg-white whitespace-nowrap">{{ party.shop_name }}
                                </td>
                                <td class="px-3 py-2 text-center font-semibold text-blue-600">{{ party.percentage || 0
                                    }}%</td>
                                <td class="px-3 py-2 text-gray-600">{{ party.phone || '-' }}</td>
                                <td class="px-3 py-2">{{ party.van || '-' }}</td>
                                <td class="px-3 py-2">{{ party.route || '-' }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-gray-800">
                                    {{ formatCurrency(party.total_sale) }}
                                </td>
                                <!-- Brand columns -->
                                <td v-for="brandDiscount in party.brand_discounts" :key="brandDiscount.brand_id"
                                    class="px-3 py-2 text-center bg-blue-50/30">
                                    <div class="flex justify-center gap-2 text-xs whitespace-nowrap">
                                        <!-- Discount % -->
                                        <span
                                            :class="brandDiscount.percentage > 0 ? 'text-purple-600 font-semibold' : 'text-gray-300'">
                                            {{ brandDiscount.percentage }}%
                                        </span>
                                        <span class="text-gray-300">|</span>
                                        <!-- Sale Amount -->
                                        <span
                                            :class="brandDiscount.sale_amount > 0 ? 'text-green-600 font-semibold' : 'text-gray-300'">
                                            {{ formatCurrency(brandDiscount.sale_amount) }}
                                        </span>
                                        <span class="text-gray-300">|</span>
                                        <!-- Sale % -->
                                        <span
                                            :class="brandDiscount.sale_percent > 0 ? 'text-blue-600 font-semibold' : 'text-gray-300'">
                                            {{ brandDiscount.sale_percent }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center font-bold text-green-600">
                                    {{ party.total_brands_with_discount }}
                                </td>
                            </tr>
                            <tr v-if="reportData.length === 0">
                                <td :colspan="7 + brands.length + 2" class="px-4 py-8 text-center text-gray-400">
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

    nav,
    header,
    aside,
    .fixed,
    .sticky,
    button,
    .no-print,
    .print\:hidden {
        display: none !important;
    }

    body,
    #app,
    main,
    .min-h-screen {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow: visible !important;
    }

    .bg-white,
    .shadow-sm,
    .rounded-xl,
    .border,
    .bg-gradient-to-r,
    .shadow-lg {
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

    .overflow-x-auto,
    .overflow-hidden {
        overflow: visible !important;
        height: auto !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 8px !important;
    }

    th,
    td {
        white-space: nowrap !important;
        padding: 2px 4px !important;
        border: 1px solid #ddd !important;
    }

    thead th {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
