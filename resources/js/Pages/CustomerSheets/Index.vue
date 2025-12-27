<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    vans: Array,
    filters: Object,
    booker: Object,
    customers: Object, // Grouped by sub_address
    allCustomers: Array,
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

// Assign Customer Modal
const showAssignModal = ref(false);
const searchCode = ref('');
const assignForm = useForm({
    customer_id: '',
    van_code: '',
});

// Customer options for searchable select
const customerOptions = computed(() => {
    return props.allCustomers.map(c => ({
        id: c.id,
        label: `${c.customer_code} - ${c.shop_name}`,
        code: c.customer_code,
        shop_name: c.shop_name,
        current_van: c.van,
    }));
});

// Filter customers by code search
const filteredCustomers = computed(() => {
    if (!searchCode.value) return customerOptions.value;
    const search = searchCode.value.toLowerCase();
    return customerOptions.value.filter(c => 
        c.code.toLowerCase().includes(search) || 
        c.shop_name.toLowerCase().includes(search)
    );
});

// Selected customer info
const selectedCustomer = computed(() => {
    if (!assignForm.customer_id) return null;
    return customerOptions.value.find(c => c.id === assignForm.customer_id);
});

const openAssignModal = () => {
    assignForm.reset();
    searchCode.value = '';
    showAssignModal.value = true;
};

const closeAssignModal = () => {
    showAssignModal.value = false;
    assignForm.reset();
    searchCode.value = '';
};

const submitAssign = () => {
    assignForm.post(route('customer-sheets.assign-to-van'), {
        preserveScroll: true,
        onSuccess: () => {
            closeAssignModal();
        },
    });
};

// Search by code functionality
const searchByCode = () => {
    if (!searchCode.value) return;
    const found = customerOptions.value.find(c => 
        c.code.toLowerCase() === searchCode.value.toLowerCase()
    );
    if (found) {
        assignForm.customer_id = found.id;
    }
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
                        @click="openAssignModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 text-white rounded-xl font-medium shadow-lg shadow-green-500/30 hover:shadow-xl hover:shadow-green-500/40 transition-all duration-200 hover:-translate-y-0.5"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                        Assign Customer to Van
                    </button>
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

        <!-- Assign Customer to Van Modal -->
        <Teleport to="body">
            <div v-if="showAssignModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeAssignModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-6">
                            Assign Customer to New Van
                        </h3>

                        <form @submit.prevent="submitAssign" class="space-y-5">
                            <!-- Search by Code -->
                            <div>
                                <InputLabel value="Search by Customer Code" />
                                <div class="flex gap-2 mt-1">
                                    <TextInput 
                                        v-model="searchCode" 
                                        type="text" 
                                        placeholder="Enter customer code..." 
                                        class="flex-1"
                                        @keyup.enter.prevent="searchByCode"
                                    />
                                    <button 
                                        type="button"
                                        @click="searchByCode"
                                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                                    >
                                        Search
                                    </button>
                                </div>
                            </div>

                            <!-- Customer Select -->
                            <div>
                                <InputLabel value="Select Customer *" />
                                <SearchableSelect 
                                    v-model="assignForm.customer_id"
                                    :options="filteredCustomers"
                                    option-value="id"
                                    option-label="label"
                                    placeholder="Search and select customer..."
                                    class="mt-1"
                                />
                                <p v-if="assignForm.errors.customer_id" class="mt-1 text-sm text-red-600">{{ assignForm.errors.customer_id }}</p>
                            </div>

                            <!-- Selected Customer Info -->
                            <div v-if="selectedCustomer" class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Selected:</span> {{ selectedCustomer.code }} - {{ selectedCustomer.shop_name }}
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    <span class="font-medium">Current Van:</span> 
                                    <span class="text-orange-600 font-medium">{{ selectedCustomer.current_van || 'Not assigned' }}</span>
                                </p>
                            </div>

                            <!-- New Van Select -->
                            <div>
                                <InputLabel value="Assign to Van *" />
                                <SearchableSelect 
                                    v-model="assignForm.van_code"
                                    :options="vanOptions"
                                    option-value="code"
                                    option-label="displayLabel"
                                    placeholder="Select new van..."
                                    class="mt-1"
                                />
                                <p v-if="assignForm.errors.van_code" class="mt-1 text-sm text-red-600">{{ assignForm.errors.van_code }}</p>
                            </div>

                            <!-- Buttons -->
                            <div class="flex gap-3 pt-4">
                                <button 
                                    type="button" 
                                    @click="closeAssignModal"
                                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
                                >
                                    Cancel
                                </button>
                                <PrimaryButton 
                                    type="submit" 
                                    :disabled="assignForm.processing || !assignForm.customer_id || !assignForm.van_code"
                                    class="flex-1 bg-green-600 hover:bg-green-700"
                                >
                                    {{ assignForm.processing ? 'Saving...' : 'Assign to Van' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
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
