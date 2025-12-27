<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    invoice: Object
});

// Format amount
const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PK', { 
        minimumFractionDigits: 2,
        maximumFractionDigits: 2 
    }).format(amount || 0);
};

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Type badge classes
const getTypeClass = (type) => {
    switch(type) {
        case 'sale': return 'bg-green-100 text-green-800 border-green-300';
        case 'damage': return 'bg-red-100 text-red-800 border-red-300';
        case 'shelf_rent': return 'bg-blue-100 text-blue-800 border-blue-300';
        default: return 'bg-gray-100 text-gray-800 border-gray-300';
    }
};

const printInvoice = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Invoice: ${invoice.invoice_number}`" />

    <DashboardLayout>
        <div class="space-y-6 max-w-5xl">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ invoice.invoice_number }}</h1>
                    <p class="text-gray-500 mt-1">Invoice Details</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('invoices.index')" class="text-gray-500 hover:text-gray-700">← Back</Link>
                    <Link :href="route('invoices.edit', invoice.id)" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">Edit</Link>
                    <button @click="printInvoice" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Print</button>
                </div>
            </div>

            <!-- Short Bill -->
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 print:shadow-none print:border-0 print:p-4">
                <!-- Header -->
                <div class="text-center border-b pb-6 mb-6">
                    <h1 class="text-2xl font-bold uppercase">SALES INVOICE</h1>
                    <div class="text-xl font-semibold text-emerald-600 mt-2">{{ invoice.invoice_number }}</div>
                    <span class="inline-block mt-2 px-3 py-1 border rounded-full text-sm font-medium uppercase" :class="getTypeClass(invoice.invoice_type)">
                        {{ invoice.invoice_type.replace('_', ' ') }}
                    </span>
                </div>

                <!-- Customer & Invoice Info -->
                <div class="grid grid-cols-2 gap-8 mb-6">
                    <div class="space-y-2">
                        <h3 class="font-bold text-gray-900">Customer</h3>
                        <div class="text-sm space-y-1">
                            <div><span class="text-gray-500">Code:</span> <span class="font-medium">{{ invoice.customer?.customer_code }}</span></div>
                            <div><span class="text-gray-500">Shop:</span> <span class="font-medium">{{ invoice.customer?.shop_name }}</span></div>
                            <div><span class="text-gray-500">Address:</span> {{ invoice.customer?.address }}</div>
                            <div><span class="text-gray-500">Phone:</span> {{ invoice.customer?.phone }}</div>
                            <div v-if="invoice.customer?.ntn_number"><span class="text-gray-500">NTN:</span> {{ invoice.customer?.ntn_number }}</div>
                        </div>
                    </div>
                    <div class="space-y-2 text-right">
                        <h3 class="font-bold text-gray-900">Invoice Info</h3>
                        <div class="text-sm space-y-1">
                            <div><span class="text-gray-500">Date:</span> <span class="font-medium">{{ formatDate(invoice.invoice_date) }}</span></div>
                            <div><span class="text-gray-500">VAN:</span> {{ invoice.van?.code }}</div>
                            <div><span class="text-gray-500">Order Booker:</span> {{ invoice.order_booker?.name }}</div>
                            <div><span class="text-gray-500">Tax Type:</span> <span class="uppercase">{{ invoice.tax_type }}</span></div>
                            <div><span class="text-gray-500">Payment:</span> {{ invoice.is_credit ? 'Credit' : 'Cash' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="w-full text-sm border-collapse border border-gray-300 mb-6">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-2 py-2 text-left">#</th>
                            <th class="border border-gray-300 px-2 py-2 text-left">Product</th>
                            <th class="border border-gray-300 px-2 py-2 text-right">Ctns</th>
                            <th class="border border-gray-300 px-2 py-2 text-right">Pcs</th>
                            <th class="border border-gray-300 px-2 py-2 text-right">Total</th>
                            <th class="border border-gray-300 px-2 py-2 text-right">Price</th>
                            <th class="border border-gray-300 px-2 py-2 text-right">Discount</th>
                            <th class="border border-gray-300 px-2 py-2 text-right">Tax</th>
                            <th class="border border-gray-300 px-2 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in invoice.items" :key="item.id"
                            :class="item.price == 0 ? 'bg-green-50' : ''">
                            <td class="border border-gray-300 px-2 py-2">{{ index + 1 }}</td>
                            <td class="border border-gray-300 px-2 py-2">
                                <div class="font-medium">
                                    {{ item.product?.name }}
                                    <span v-if="item.price == 0" class="ml-2 px-2 py-0.5 text-xs bg-green-500 text-white rounded-full">FREE</span>
                                </div>
                                <div class="text-xs text-gray-500">{{ item.product?.dms_code }}</div>
                            </td>
                            <td class="border border-gray-300 px-2 py-2 text-right">{{ item.cartons }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right">{{ item.pieces }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right font-medium">{{ item.total_pieces }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right">{{ item.price == 0 ? 'FREE' : formatAmount(item.price) }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right text-red-600">-{{ formatAmount(item.scheme_discount) }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right">{{ formatAmount(parseFloat(item.tax || 0) + parseFloat(item.fed_amount || 0)) }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right font-semibold">{{ item.price == 0 ? '-' : formatAmount(item.line_total) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="flex justify-end">
                    <div class="w-64 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal:</span>
                            <span>Rs. {{ formatAmount(invoice.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-red-600">
                            <span>Discount:</span>
                            <span>-Rs. {{ formatAmount(invoice.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Sales Tax:</span>
                            <span>Rs. {{ formatAmount(invoice.tax_amount) }}</span>
                        </div>
                        <div v-if="invoice.fed_amount > 0" class="flex justify-between">
                            <span class="text-gray-500">FED:</span>
                            <span>Rs. {{ formatAmount(invoice.fed_amount) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg border-t pt-2">
                            <span>Total:</span>
                            <span class="text-emerald-600">Rs. {{ formatAmount(invoice.total_amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Signatures -->
                <div class="grid grid-cols-3 gap-8 mt-12 pt-8 border-t">
                    <div class="text-center">
                        <div class="border-t border-gray-400 pt-2 mt-12">Prepared By</div>
                    </div>
                    <div class="text-center">
                        <div class="border-t border-gray-400 pt-2 mt-12">Checked By</div>
                    </div>
                    <div class="text-center">
                        <div class="border-t border-gray-400 pt-2 mt-12">Customer Signature</div>
                    </div>
                </div>

                <div class="mt-6 text-xs text-gray-400 text-right print:mt-4">
                    Printed on: {{ new Date().toLocaleString() }}
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print {
    @page { size: A4 portrait; margin: 10mm; }
    :deep(aside), :deep(nav), :deep(header) { display: none !important; }
    :deep(main) { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    table, th, td { border-color: #000 !important; }
}
</style>
