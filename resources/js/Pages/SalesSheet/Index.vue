<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    products: Array,
    creditBills: Array,
    recoveryBills: Array,
    summary: Object,
    filters: Object,
    van: Object,
    vans: Array,
});

const form = ref({
    van_id: props.filters.van_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const search = () => {
    router.get(route('sales-sheets.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Product Totals
const productTotals = computed(() => ({
    qty: props.products.reduce((sum, p) => sum + Number(p.qty), 0),
    issued: props.products.reduce((sum, p) => sum + Number(p.issued), 0),
    damages: props.products.reduce((sum, p) => sum + Number(p.damages), 0),
    pro: props.products.reduce((sum, p) => sum + Number(p.pro), 0),
    free: props.products.reduce((sum, p) => sum + Number(p.free), 0),
    gross_sale: props.products.reduce((sum, p) => sum + Number(p.gross_sale), 0),
    discount: props.products.reduce((sum, p) => sum + Number(p.discount), 0),
    net_sale: props.products.reduce((sum, p) => sum + Number(p.net_sale), 0),
}));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const print = () => window.print();
</script>

<template>
    <Head title="Sales Sheet" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header & Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 no-print">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <InputLabel value="Select Van" />
                        <SearchableSelect
                            v-model="form.van_id"
                            :options="vans"
                            option-value="id"
                            option-label="name"
                            placeholder="Select Van"
                            class="mt-1 block w-full"
                        />
                    </div>
                    <div>
                        <InputLabel value="Date From" />
                        <input type="date" v-model="form.date_from" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <InputLabel value="Date To" />
                        <input type="date" v-model="form.date_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="flex gap-2 pb-0.5">
                        <PrimaryButton @click="search">Search</PrimaryButton>
                        <SecondaryButton @click="print">Print</SecondaryButton>
                    </div>
                </div>
            </div>

            <!-- Report Header -->
            <div v-if="van" class="bg-[#0089BA] text-white p-3 rounded-lg flex justify-between items-center">
                <h2 class="font-bold text-lg">Sales Report for Van: {{ van.name }} on {{ filters.date_from }}</h2>
                <div class="flex gap-2 no-print">
                    <button class="bg-white text-[#0089BA] px-3 py-1 rounded text-sm font-bold hover:bg-gray-100">Download Image</button>
                    <button @click="print" class="bg-white text-[#0089BA] px-3 py-1 rounded text-sm font-bold hover:bg-gray-100">Save PDF</button>
                    <button class="bg-white text-[#0089BA] px-3 py-1 rounded text-sm font-bold hover:bg-gray-100">Print</button>
                </div>
            </div>

            <!-- Products Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-[#0089BA] text-white uppercase font-semibold">
                            <tr>
                                <th class="px-2 py-2">Product Code</th>
                                <th class="px-2 py-2">Product Name</th>
                                <th class="px-2 py-2 text-right">Unit Price</th>
                                <th class="px-2 py-2 text-right">Qty</th>
                                <th class="px-2 py-2 text-right">Issued</th>
                                <th class="px-2 py-2 text-right">Damages</th>
                                <th class="px-2 py-2 text-right">Pro</th>
                                <th class="px-2 py-2 text-right">Free</th>
                                <th class="px-2 py-2 text-right">Gross Sale</th>
                                <th class="px-2 py-2 text-right">Discount</th>
                                <th class="px-2 py-2 text-right">%</th>
                                <th class="px-2 py-2 text-right">Net Sale Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(product, index) in products" :key="index" class="hover:bg-gray-50/50">
                                <td class="px-2 py-2 font-medium">{{ product.product_code }}</td>
                                <td class="px-2 py-2">{{ product.product_name }}</td>
                                <td class="px-2 py-2 text-right">{{ formatCurrency(product.unit_price) }}</td>
                                <td class="px-2 py-2 text-right">{{ product.qty }}</td>
                                <td class="px-2 py-2 text-right">{{ product.issued }}</td>
                                <td class="px-2 py-2 text-right">{{ product.damages }}</td>
                                <td class="px-2 py-2 text-right">{{ product.pro }}</td>
                                <td class="px-2 py-2 text-right">{{ product.free }}</td>
                                <td class="px-2 py-2 text-right">{{ formatCurrency(product.gross_sale) }}</td>
                                <td class="px-2 py-2 text-right">{{ formatCurrency(product.discount) }}</td>
                                <td class="px-2 py-2 text-right">{{ product.percentage }}%</td>
                                <td class="px-2 py-2 text-right font-bold text-green-600">{{ formatCurrency(product.net_sale) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-[#0089BA] text-white font-bold">
                            <tr>
                                <td colspan="3" class="px-2 py-2 text-right">Total</td>
                                <td class="px-2 py-2 text-right">{{ productTotals.qty }}</td>
                                <td class="px-2 py-2 text-right">{{ productTotals.issued }}</td>
                                <td class="px-2 py-2 text-right">{{ productTotals.damages }}</td>
                                <td class="px-2 py-2 text-right">{{ productTotals.pro }}</td>
                                <td class="px-2 py-2 text-right">{{ productTotals.free }}</td>
                                <td class="px-2 py-2 text-right">{{ formatCurrency(productTotals.gross_sale) }}</td>
                                <td class="px-2 py-2 text-right">{{ formatCurrency(productTotals.discount) }}</td>
                                <td class="px-2 py-2 text-right"></td>
                                <td class="px-2 py-2 text-right">{{ formatCurrency(productTotals.net_sale) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Credit Summary Bills -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h3 class="font-bold text-gray-800 mb-3">Credit Summary Bills</h3>
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase text-gray-500">
                            <tr>
                                <th class="text-left py-1">Invoice No</th>
                                <th class="text-left py-1">Shop Name</th>
                                <th class="text-right py-1">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="bill in creditBills" :key="bill.invoice_no">
                                <td class="py-1 text-[#0089BA]">{{ bill.invoice_no }}</td>
                                <td class="py-1">{{ bill.shop_name }}</td>
                                <td class="py-1 text-right">{{ formatCurrency(bill.amount) }}</td>
                            </tr>
                            <tr v-if="creditBills.length === 0">
                                <td colspan="3" class="py-2 text-center text-gray-400 text-xs">No credit entries found.</td>
                            </tr>
                        </tbody>
                        <tfoot class="font-bold border-t">
                            <tr>
                                <td colspan="2" class="py-2">Total Credit:</td>
                                <td class="py-2 text-right">{{ formatCurrency(summary.total_credit) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Recovery Summary Bills -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h3 class="font-bold text-gray-800 mb-3">Recovery Summary Bills</h3>
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase text-gray-500">
                            <tr>
                                <th class="text-left py-1">Bill No</th>
                                <th class="text-left py-1">Customer Name</th>
                                <th class="text-right py-1">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="rec in recoveryBills" :key="rec.bill_no">
                                <td class="py-1">{{ rec.bill_no }}</td>
                                <td class="py-1">{{ rec.customer_name }}</td>
                                <td class="py-1 text-right">{{ formatCurrency(rec.amount) }}</td>
                            </tr>
                            <tr v-if="recoveryBills.length === 0">
                                <td colspan="3" class="py-2 text-center text-gray-400 text-xs">No recovery entries found.</td>
                            </tr>
                        </tbody>
                        <tfoot class="font-bold border-t">
                            <tr>
                                <td colspan="2" class="py-2">Total Recovery:</td>
                                <td class="py-2 text-right">{{ formatCurrency(summary.total_recovery) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Summary Panel -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h3 class="font-bold text-gray-800 mb-3">Summary</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Gross</span>
                            <span class="font-bold">{{ formatCurrency(summary.total_gross) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Discount</span>
                            <span class="font-bold text-red-600">{{ formatCurrency(summary.total_discount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Percentage</span>
                            <span class="font-bold">{{ summary.total_percentage }}%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Advance Tax</span>
                            <span class="font-bold">{{ formatCurrency(summary.total_advance_tax) }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Sale Value</span>
                            <span class="font-bold text-green-600">{{ formatCurrency(summary.total_sale_value) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Credit</span>
                            <span class="font-bold text-amber-600">{{ formatCurrency(summary.total_credit) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Recovery</span>
                            <span class="font-bold text-blue-600">{{ formatCurrency(summary.total_recovery) }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="flex justify-between text-lg">
                            <span class="font-bold text-gray-800">Total Cash Sales</span>
                            <span class="font-bold text-green-700">{{ formatCurrency(summary.total_cash_sales) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
