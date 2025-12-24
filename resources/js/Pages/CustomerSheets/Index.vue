<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    vans: Array,
    filters: Object,
    booker: Object,
    customers: Object // Grouped by sub_address
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

// Format vans to show distribution name only if All Distributions is selected
const vanOptions = computed(() => {
    return props.vans.map(van => ({
        ...van,
        displayLabel: !currentDistribution.value?.id && van.distribution 
            ? `${van.code} (${van.distribution.name})` 
            : van.code
    }));
});

const selectedVan = ref(props.filters.van_name || '');

watch(selectedVan, (value) => {
    router.get(route('customer-sheets.index'), { van_name: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
});

// Find the selected van object
const selectedVanObject = computed(() => {
    return props.vans.find(v => v.code === selectedVan.value);
});

const printSheet = () => {
    window.print();
};
</script>

<template>
    <Head title="Customer Sheets" />

    <DashboardLayout>
        <div class="space-y-6 print:hidden">
            <!-- Header (Hidden in Print) -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Customer Sheets</h1>
                    <p class="text-gray-500 mt-1">Generate and print customer lists for order bookers.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <button 
                        @click="printSheet"
                        :disabled="!selectedVan"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl font-medium shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 transition-all duration-200 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        Print Sheet
                    </button>
                    
                     <button 
                        @click="printSheet"
                        :disabled="!selectedVan"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-xl font-medium shadow-lg shadow-red-500/30 hover:shadow-xl hover:shadow-red-500/40 transition-all duration-200 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        PDF
                    </button>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="w-full md:w-1/3">
                    <SearchableSelect 
                        v-model="selectedVan"
                        label="Select VAN"
                        :options="vanOptions"
                        option-value="code"
                        option-label="displayLabel"
                        placeholder="Select a VAN to view sheet"
                    />
                </div>
            </div>
        </div>

        <!-- Report Content (Visible in Print) -->
        <div v-if="selectedVan" class="mt-8 bg-white p-8 rounded-none md:rounded-2xl shadow-sm border border-gray-100 print:shadow-none print:border-0 print:p-0 print:mt-0">
            
            <!-- Report Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 uppercase">
                    {{ selectedVanObject?.distribution?.name || 'CPS DISTRIBUTION' }}
                </h1>
                <h2 class="text-xl font-semibold text-gray-700 mt-2">OrderBooker Shops</h2>
                <div class="mt-4 text-lg">
                    <span class="font-bold">Orderbooker Name:</span> {{ booker?.name || 'Not Assigned' }}
                </div>
            </div>

            <!-- Customer Tables Grouped by Sub Address -->
            <div class="space-y-8">
                <div class="bg-teal-600 text-white p-3 font-bold print:bg-teal-600 print:text-white mb-4">
                    Customers for VAN: {{ selectedVan }}
                </div>

                <div v-if="Object.keys(customers).length === 0" class="text-center text-gray-500 py-8">
                    No customers found for this VAN.
                </div>

                <div v-for="(group, subAddress) in customers" :key="subAddress" class="break-inside-avoid">
                    <!-- Sub Address Header (Area) -->
                    <div class="bg-gray-100 p-2 font-bold text-gray-800 border border-gray-200 mb-0 uppercase text-sm">
                        {{ subAddress || 'Unknown Area' }}
                    </div>

                    <table class="w-full text-left text-[10px] text-gray-800 border-collapse border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-200 px-1 py-1">Code</th>
                                <th class="border border-gray-200 px-1 py-1">Shop Name</th>
                                <th class="border border-gray-200 px-1 py-1">Address</th>
                                <th class="border border-gray-200 px-1 py-1">Phone</th>
                                <th class="border border-gray-200 px-1 py-1">Cat</th>
                                <th class="border border-gray-200 px-1 py-1">Channel</th>
                                <th class="border border-gray-200 px-1 py-1">NTN</th>
                                <th class="border border-gray-200 px-1 py-1">CNIC</th>
                                <th class="border border-gray-200 px-1 py-1">STN</th>
                                <th class="border border-gray-200 px-1 py-1">Dist.</th>
                                <th class="border border-gray-200 px-1 py-1">Day</th>
                                <th class="border border-gray-200 px-1 py-1 text-right">Adv.Tax</th>
                                <th class="border border-gray-200 px-1 py-1 text-right">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="customer in group" :key="customer.id" class="border-b border-gray-200">
                                <td class="border border-gray-200 px-1 py-1">{{ customer.customer_code }}</td>
                                <td class="border border-gray-200 px-1 py-1 font-medium">{{ customer.shop_name }}</td>
                                <td class="border border-gray-200 px-1 py-1 truncate max-w-[150px]">{{ customer.address }}</td>
                                <td class="border border-gray-200 px-1 py-1">{{ customer.phone }}</td>
                                <td class="border border-gray-200 px-1 py-1">{{ customer.category }}</td>
                                <td class="border border-gray-200 px-1 py-1">{{ customer.channel }}</td>
                                <td class="border border-gray-200 px-1 py-1">{{ customer.ntn_number }}</td>
                                <td class="border border-gray-200 px-1 py-1">{{ customer.cnic }}</td>
                                <td class="border border-gray-200 px-1 py-1">{{ customer.sales_tax_number }}</td>
                                <td class="border border-gray-200 px-1 py-1">{{ customer.distribution }}</td>
                                <td class="border border-gray-200 px-1 py-1">{{ customer.day }}</td>
                                <td class="border border-gray-200 px-1 py-1 text-right">{{ customer.adv_tax_percent }}</td>
                                <td class="border border-gray-200 px-1 py-1 text-right">{{ customer.percentage }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
             <!-- Footer with Timestamp (Print Only) -->
            <div class="hidden print:block mt-8 text-xs text-right text-gray-400">
                Printed on: {{ new Date().toLocaleString() }}
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print {
    @page {
        size: landscape;
        margin: 5mm;
    }

    /* Hide Sidebar and Navigation */
    :deep(aside), :deep(nav), button {
        display: none !important;
    }
    
    /* Reset margins for main content area to use full width */
    :deep(main) {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    
    /* Ensure backgrounds print */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Ensure table borders are sharp */
    table, th, td {
        border-color: #000 !important;
    }
}
</style>
