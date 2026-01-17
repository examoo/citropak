<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    customer: Object,
    invoices: Array,
    productsByDate: Array,
    brandWiseSales: Array,
    filters: Object,
    customers: Array,
});

const form = ref({
    customer_code: props.filters.customer_code || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const activeTab = ref('invoices');
const selectedCustomerId = ref('');

// When customer dropdown changes, update the code
watch(selectedCustomerId, (newId) => {
    if (newId) {
        const cust = props.customers.find(c => c.id === newId);
        if (cust) {
            form.value.customer_code = cust.code;
        }
    }
});

const search = () => {
    router.get(route('customer-sales-reports.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
};

const print = () => window.print();

const exportExcel = () => {
    const params = new URLSearchParams(form.value);
    window.location.href = route('customer-sales-reports.export') + '?' + params.toString();
};
</script>

<template>

    <Head title="Customer Sales Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">Customer Sales Report</h1>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex flex-col md:flex-row gap-4 items-end flex-wrap">
                    <div class="min-w-[200px]">
                        <InputLabel value="Select Customer" />
                        <SearchableSelect v-model="selectedCustomerId" :options="customers" option-value="id"
                            option-label="name" placeholder="Search customer..." class="mt-1 block w-full" />
                    </div>
                    <div class="text-gray-400 self-center">OR</div>
                    <div class="flex-1">
                        <InputLabel value="Enter Customer Code" />
                        <TextInput type="text" v-model="form.customer_code" placeholder="CPSSGD01475"
                            class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Start Date" />
                        <input type="date" v-model="form.date_from"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <InputLabel value="End Date" />
                        <input type="date" v-model="form.date_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <PrimaryButton @click="search" class="bg-green-600 hover:bg-green-700">
                        <span class="mr-1">✓</span> Generate Report
                    </PrimaryButton>
                </div>
            </div>

            <!-- Report Content -->
            <div v-if="customer" class="space-y-4">
                <!-- Customer Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-3 rounded-lg">
                    <h2 class="font-bold">Reports for Customer: {{ customer.code }}</h2>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button @click="activeTab = 'invoices'"
                                :class="activeTab === 'invoices' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-6 py-3 border-b-2 font-medium text-sm">
                                All Invoices
                            </button>
                            <button @click="activeTab = 'products'"
                                :class="activeTab === 'products' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-6 py-3 border-b-2 font-medium text-sm">
                                Products by Date
                            </button>
                            <button @click="activeTab = 'brands'"
                                :class="activeTab === 'brands' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-6 py-3 border-b-2 font-medium text-sm">
                                Brand-wise Sale
                            </button>
                        </nav>
                    </div>

                    <!-- Tab: All Invoices -->
                    <div v-if="activeTab === 'invoices'" class="p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-gray-800">All Invoices</h3>
                            <div class="flex gap-2 no-print">
                                <button @click="print"
                                    class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700">
                                    🖨 Print / PDF
                                </button>
                                <button @click="exportExcel"
                                    class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                    📊 Export Excel
                                </button>
                            </div>
                        </div>
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Invoice No</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Van</th>
                                    <th class="px-4 py-3 text-right">Gross Sale</th>
                                    <th class="px-4 py-3 text-right">Discount</th>
                                    <th class="px-4 py-3 text-right">After Discount Amount</th>
                                    <th class="px-4 py-3 text-right">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="inv in invoices" :key="inv.invoice_number" class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3 text-indigo-600 font-medium">{{ inv.invoice_number }}</td>
                                    <td class="px-4 py-3">{{ formatDate(inv.date) }}</td>
                                    <td class="px-4 py-3 text-indigo-600">{{ inv.van }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatCurrency(inv.gross_sale) }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatCurrency(inv.discount) }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatCurrency(inv.after_discount) }}</td>
                                    <td class="px-4 py-3 text-right">{{ inv.percentage }}</td>
                                </tr>
                                <tr v-if="invoices.length === 0">
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">No invoices found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab: Products by Date -->
                    <div v-if="activeTab === 'products'" class="p-4">
                        <h3 class="font-bold text-gray-800 mb-4">Products by Date</h3>
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Product Code</th>
                                    <th class="px-4 py-3">Product Name</th>
                                    <th class="px-4 py-3 text-right">Qty</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(prod, idx) in productsByDate" :key="idx" class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3">{{ formatDate(prod.date) }}</td>
                                    <td class="px-4 py-3 font-medium">{{ prod.product_code }}</td>
                                    <td class="px-4 py-3">{{ prod.product_name }}</td>
                                    <td class="px-4 py-3 text-right">{{ prod.qty }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatCurrency(prod.amount) }}</td>
                                </tr>
                                <tr v-if="productsByDate.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">No products found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab: Brand-wise Sale -->
                    <div v-if="activeTab === 'brands'" class="p-4">
                        <h3 class="font-bold text-gray-800 mb-4">Brand-wise Sale</h3>
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Brand</th>
                                    <th class="px-4 py-3 text-right">Qty</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="brand in brandWiseSales" :key="brand.brand" class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3 font-medium">{{ brand.brand }}</td>
                                    <td class="px-4 py-3 text-right">{{ brand.qty }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatCurrency(brand.amount) }}</td>
                                </tr>
                                <tr v-if="brandWiseSales.length === 0">
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-400">No brand data found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- No Customer Selected -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                Enter a Customer Code and click "Generate Report" to view sales data.
            </div>
        </div>
    </DashboardLayout>
</template>
<style scoped>
@media print {

    /* Hide non-printable elements */
    .no-print,
    nav,
    header,
    aside,
    .fixed,
    .sticky {
        display: none !important;
    }

    /* Reset layout for print */
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

    /* Overwrite container styles */
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

    /* Ensure table fits */
    .overflow-x-auto,
    .overflow-hidden {
        overflow: visible !important;
        height: auto !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 10px !important;
    }

    th,
    td {
        white-space: normal !important;
        padding: 4px !important;
        border: 1px solid #ddd !important;
    }

    thead th {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
