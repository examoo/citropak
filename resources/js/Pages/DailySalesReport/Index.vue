<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    rows: Array,
    products: Array,
    filters: Object,
    productTypes: Array,
});

const form = ref({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    product_type_id: props.filters.product_type_id || '',
});

const search = () => {
    router.get(route('daily-sales-reports.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const print = () => window.print();

const exportExcel = () => {
    const params = new URLSearchParams(form.value);
    window.location.href = route('daily-sales-reports.export') + '?' + params.toString();
};
</script>

<template>

    <Head title="Daily Sales Report" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Back Link -->
            <div class="no-print">
                <Link href="/dashboard" class="text-blue-600 hover:text-blue-800 text-sm">
                    ← Back to Dashboard
                </Link>
            </div>

            <!-- Header -->
            <div class="text-center">
                <h1 class="text-2xl font-bold text-gray-800">Daily Sales Report</h1>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 no-print">
                <div class="flex flex-col md:flex-row gap-4 items-end flex-wrap">
                    <div>
                        <InputLabel value="Start Date:" />
                        <input type="date" v-model="form.date_from"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <InputLabel value="End Date:" />
                        <input type="date" v-model="form.date_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="min-w-[200px]">
                        <InputLabel value="Product Type:" />
                        <SearchableSelect v-model="form.product_type_id" :options="productTypes" option-value="id"
                            option-label="name" placeholder="All Types" class="mt-1 block w-full" />
                    </div>
                    <PrimaryButton @click="search" class="bg-blue-600 hover:bg-blue-700">
                        Generate Report
                    </PrimaryButton>
                    <button @click="exportExcel"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                        Export Excel
                    </button>
                    <button @click="print"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                        Print / PDF
                    </button>
                </div>
            </div>

            <!-- Report Header -->
            <div class="text-blue-600 font-bold text-lg">
                Report for {{ formatDate(filters.date_from) }} to {{ formatDate(filters.date_to) }}
                <span v-if="filters.product_type_id">(Type: {{productTypes.find(t => t.id ==
                    filters.product_type_id)?.name}})</span>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-blue-100 text-gray-700 uppercase font-semibold">
                            <tr>
                                <th class="px-3 py-2 border-r border-blue-200">Van</th>
                                <th class="px-3 py-2 border-r border-blue-200">Order Booker Name</th>
                                <th v-for="product in products" :key="product.id"
                                    class="px-3 py-2 border-r border-blue-200 text-center whitespace-nowrap">
                                    {{ product.name }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(row, idx) in rows" :key="idx" class="hover:bg-gray-50/50"
                                :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                                <td class="px-3 py-2 text-blue-600 font-medium border-r border-gray-100">{{ row.van }}
                                </td>
                                <td class="px-3 py-2 border-r border-gray-100">{{ row.order_booker }}</td>
                                <td v-for="product in products" :key="product.id"
                                    class="px-3 py-2 text-center border-r border-gray-100">
                                    {{ row.products[product.id] || 0 }}
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td :colspan="2 + products.length" class="px-6 py-12 text-center text-gray-400">
                                    No data found for the selected filters.
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
        font-size: 8px !important;
        /* Smaller for daily sales pivot */
    }

    th,
    td {
        white-space: normal !important;
        padding: 2px !important;
        border: 1px solid #ddd !important;
    }

    thead th {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
