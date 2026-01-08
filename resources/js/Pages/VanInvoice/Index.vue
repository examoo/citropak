<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    invoices: Array,
    vans: Array,
    filters: Object,
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

// Filters
const selectedVanId = ref(props.filters?.van_id || '');
const selectedDate = ref(props.filters?.date || new Date().toISOString().split('T')[0]);

// Format vans for dropdown
const vanOptions = computed(() => {
    return props.vans.map(van => ({
        ...van,
        displayLabel: !currentDistribution.value?.id && van.distribution 
            ? `${van.code} (${van.distribution.name})` 
            : van.code
    }));
});

// Load invoices when filters change
const loadInvoices = () => {
    if (!selectedVanId.value) return;
    
    router.get(route('van-invoices.index'), {
        van_id: selectedVanId.value,
        date: selectedDate.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

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
    return new Date(date).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).replace(/\//g, '-');
};

// Get item values directly from database
const getItemRate = (item) => parseFloat(item.list_price_before_tax) || 0;
const getItemExclusive = (item) => parseFloat(item.exclusive_amount) || (item.total_pieces * getItemRate(item));
const getItemFed = (item) => parseFloat(item.fed_amount) || 0;
const getItemSalesTax = (item) => parseFloat(item.tax) || 0;
const getItemExtraTax = (item) => parseFloat(item.extra_tax_amount) || 0;
const getItemGross = (item) => {
    if (item.gross_amount) return parseFloat(item.gross_amount);
    return getItemExclusive(item) + getItemFed(item) + getItemSalesTax(item);
};
const getItemNet = (item) => parseFloat(item.line_total) || 0;

// Get totals for an invoice
const getInvoiceTotals = (invoice) => {
    const items = invoice.items || [];
    const regularItems = items.filter(item => !item.is_free);
    const freeItems = items.filter(item => item.is_free);
    
    const sumItems = (items, valueFn) => items.reduce((sum, item) => sum + valueFn(item), 0);
    
    return {
        regularItems,
        freeItems,
        regularTotalQty: sumItems(regularItems, i => i.total_pieces || 0),
        regularTotalExclusive: sumItems(regularItems, getItemExclusive),
        regularTotalFed: sumItems(regularItems, getItemFed),
        regularTotalSalesTax: sumItems(regularItems, getItemSalesTax),
        regularTotalExtraTax: sumItems(regularItems, getItemExtraTax),
        regularTotalGross: sumItems(regularItems, getItemGross),
        regularTotalTradeDiscount: sumItems(regularItems, i => parseFloat(i.retail_margin || 0)),
        regularTotalSchemeDiscount: sumItems(regularItems, i => parseFloat(i.discount || 0)),
        regularTotalNet: sumItems(regularItems, getItemNet),
        totalQty: sumItems(items, i => i.total_pieces || 0),
        totalExclusive: sumItems(items, getItemExclusive),
        totalFed: sumItems(items, getItemFed),
        totalSalesTax: sumItems(items, getItemSalesTax),
        totalExtraTax: sumItems(items, getItemExtraTax),
        totalGross: sumItems(items, getItemGross),
        totalRetailMargin: sumItems(items, i => parseFloat(i.retail_margin || 0)),
        totalSchemeDiscount: sumItems(items, i => parseFloat(i.discount || 0)),
        totalAdvTax: sumItems(items, i => parseFloat(i.adv_tax_amount || 0)),
        totalNet: sumItems(items, getItemNet),
    };
};

// Generate QR Data string
const getQrData = (invoice) => {
    const totals = getInvoiceTotals(invoice);
    const totalAmount = totals.totalNet + totals.totalAdvTax;
    const customerName = invoice.customer?.shop_name || 'Unknown';
    const invoiceNum = invoice.invoice_number;
    
    return `Customer: ${customerName} | Inv: ${invoiceNum} | Amt: ${totalAmount.toFixed(2)}`;
};

// Print all invoices
const printAllInvoices = () => {
    window.print();
};

// Calculate grand totals for all invoices
const grandTotals = computed(() => {
    return {
        count: props.invoices?.length || 0,
        totalNet: (props.invoices || []).reduce((sum, inv) => sum + getInvoiceTotals(inv).totalNet, 0),
    };
});
</script>

<template>
    <Head title="Van Invoices" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header (hidden in print) -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Van Invoices</h1>
                    <p class="text-gray-500 mt-1">View and print all invoices for a van</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <!-- Van Selector -->
                    <div class="w-48">
                        <SearchableSelect 
                            v-model="selectedVanId"
                            :options="vanOptions"
                            option-value="id"
                            option-label="displayLabel"
                            placeholder="Select Van"
                        />
                    </div>
                    
                    <!-- Date Picker -->
                    <input 
                        v-model="selectedDate"
                        type="date" 
                        class="px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-sm"
                    >
                    
                    <!-- Load Button -->
                    <button 
                        @click="loadInvoices"
                        :disabled="!selectedVanId"
                        class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Load Invoices
                    </button>
                    
                    <!-- Print Button -->
                    <button 
                        v-if="invoices?.length > 0"
                        @click="printAllInvoices"
                        class="px-5 py-2.5 bg-blue-600 text-white rounded-xl font-medium shadow-lg hover:bg-blue-700 transition-all duration-200"
                    >
                        Print All ({{ invoices.length }})
                    </button>
                </div>
            </div>

            <!-- Summary Card (hidden in print) -->
            <div v-if="invoices?.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 print:hidden">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-3xl font-bold text-emerald-600">{{ grandTotals.count }}</div>
                        <div class="text-gray-500 text-sm">Total Invoices</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-blue-600">{{ vans.find(v => v.id == selectedVanId)?.code || '-' }}</div>
                        <div class="text-gray-500 text-sm">Van</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-amber-600">{{ formatAmount(grandTotals.totalNet) }}</div>
                        <div class="text-gray-500 text-sm">Total Sales</div>
                    </div>
                </div>
            </div>

            <!-- No Invoices Message -->
            <div v-if="selectedVanId && invoices?.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center print:hidden">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <p class="text-gray-500">No invoices found for this van on {{ formatDate(selectedDate) }}</p>
            </div>

            <!-- Select Van Message -->
            <div v-if="!selectedVanId" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center print:hidden">
                <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <p class="text-gray-500">Select a van and date to view invoices</p>
            </div>

            <!-- Printable Invoices -->
            <div v-for="(invoice, idx) in invoices" :key="invoice.id" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 print:shadow-none print:border-0 print:p-2 print:rounded-none invoice-page">
                
                <!-- Title -->
                <div class="text-center border-b border-gray-400 pb-4 mb-4">
                    <h1 class="text-xl font-bold uppercase tracking-wide">FED & SALE TAX INVOICE</h1>
                </div>

                <!-- Company & Customer Info -->
                <div class="grid grid-cols-2 gap-6 mb-4 text-xs">
                    <!-- Left: Customer Info -->
                    <div>
                        <!-- Customer Info QR -->
                        <div class="mb-2">
                            <div class="flex flex-col items-center w-fit">
                                <div class="w-16 h-16 bg-white p-0.5 border border-gray-200">
                                    <QrcodeVue :value="getQrData(invoice)" :size="60" level="L" render-as="svg" />
                                </div>
                                <div class="text-[8px] font-bold mt-0.5">Invoice QR</div>
                            </div>
                        </div>

                        <div class="font-bold text-sm mb-1">Customer Detail:</div>

                        <div class="space-y-1">
                            <div><span class="text-gray-600">CustomerCode:</span> <span class="font-medium">{{ invoice.customer?.customer_code }}</span></div>
                            <div><span class="text-gray-600">CustomerName:</span> <span class="font-medium">{{ invoice.customer?.shop_name }}</span></div>
                            <div><span class="text-gray-600">CustomerAddress:</span> {{ invoice.customer?.address }}</div>
                            <div><span class="text-gray-600">CNIC/NTN:</span> {{ invoice.customer?.ntn_number || invoice.customer?.cnic || '' }}</div>
                            <div><span class="text-gray-600">Phone#:</span> {{ invoice.customer?.phone || '0' }}</div>
                            <div><span class="text-gray-600">S.Tax ATL:</span> <span :class="invoice.customer?.sales_tax_status === 'active' ? 'text-green-600' : 'text-red-600'">{{ invoice.customer?.sales_tax_status?.toUpperCase() || 'INACTIVE' }}</span></div>
                            <div><span class="text-gray-600">Business Category:</span> {{ invoice.customer?.category || 'Retail' }}</div>
                        </div>
                    </div>

                    <!-- Right: Distribution/Company Info & FBR QR -->
                    <div class="text-right space-y-1">
                        <div class="flex justify-end gap-4">
                            <div v-if="invoice.fbr_qr_code" class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-white p-1 border border-gray-200">
                                    <img :src="`data:image/png;base64,${invoice.fbr_qr_code}`" alt="FBR QR" class="w-full h-full object-contain" v-if="invoice.fbr_qr_code.length > 200" />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-gray-100 text-[8px] text-center overflow-hidden">
                                        FBR QR
                                    </div>
                                </div>
                                <div class="text-[8px] font-bold mt-0.5">FBR Invoice</div>
                                <div class="text-[8px] font-mono">{{ invoice.fbr_invoice_number }}</div>
                            </div>

                            <!-- Removed Customer QR from here -->

                            <div class="text-right space-y-1">
                                <div class="font-bold text-sm">{{ invoice.distribution?.name || 'CITROPAK LTD' }}</div>
                                <div>{{ invoice.distribution?.address || invoice.distribution?.business_address || '' }}</div>
                                <div v-if="invoice.distribution?.ntn_number">NTN No: {{ invoice.distribution.ntn_number }}</div>
                                <div v-if="invoice.distribution?.stn_number">STN No: {{ invoice.distribution.stn_number }}</div>
                                <div v-if="invoice.distribution?.phone_number">Contact#: {{ invoice.distribution.phone_number }}</div>
                                <div><span class="font-semibold">Invoice#:</span> {{ invoice.invoice_number }}</div>
                                <div><span class="font-semibold">Date:</span> {{ formatDate(invoice.invoice_date) }}</div>
                                <div><span class="font-semibold">Van#:</span> {{ invoice.van?.code }}</div>
                                <div v-if="invoice.fbr_invoice_number"><span class="font-semibold">FBR Inv#:</span> {{ invoice.fbr_invoice_number }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="w-full text-[10px] border-collapse border border-black mb-4">
                    <thead>
                        <tr class="bg-gray-100 print:bg-white">
                            <th class="border border-black px-1 py-1 text-center">Sr.No</th>
                            <th class="border border-black px-1 py-1 text-center">Product<br/>Code</th>
                            <th class="border border-black px-1 py-1 text-left">Item Name</th>
                            <th class="border border-black px-1 py-1 text-center">Issued<br/>Qty</th>
                            <th class="border border-black px-1 py-1 text-right">Rate</th>
                            <th class="border border-black px-1 py-1 text-right">Total<br/>Excl.Value</th>
                            <th class="border border-black px-1 py-1 text-right">FED</th>
                            <th class="border border-black px-1 py-1 text-right">Sale Tax<br/>{{ invoice.items?.[0]?.tax_percent || 18 }}%</th>
                            <th class="border border-black px-1 py-1 text-right">Extra<br/>Tax</th>
                            <th class="border border-black px-1 py-1 text-right">Gross<br/>Value</th>
                            <th class="border border-black px-1 py-1 text-right">Trade<br/>Discount</th>
                            <th class="border border-black px-1 py-1 text-right">Scheme<br/>Discount</th>
                            <th class="border border-black px-1 py-1 text-right">Net Sale<br/>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Regular Items -->
                        <tr v-for="(item, index) in getInvoiceTotals(invoice).regularItems" :key="'reg-'+item.id">
                            <td class="border border-black px-1 py-1 text-center">{{ index + 1 }}</td>
                            <td class="border border-black px-1 py-1 text-center">{{ item.product?.dms_code || item.product?.sku }}</td>
                            <td class="border border-black px-1 py-1 text-left">{{ item.product?.name }}</td>
                            <td class="border border-black px-1 py-1 text-center">{{ item.total_pieces }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(item.list_price_before_tax || item.price) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getItemExclusive(item)) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getItemFed(item)) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getItemSalesTax(item)) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getItemExtraTax(item)) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getItemGross(item)) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(item.retail_margin) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(parseFloat(item.discount) || 0) }}</td>
                            <td class="border border-black px-1 py-1 text-right font-medium">{{ formatAmount(getItemNet(item)) }}</td>
                        </tr>

                        <!-- Regular Total Row -->
                        <tr class="bg-amber-500 font-bold print:bg-white print:text-black">
                             <td class="border border-black px-1 py-1 text-center" colspan="3">TOTAL</td>
                             <td class="border border-black px-1 py-1 text-center">{{ getInvoiceTotals(invoice).regularTotalQty }}</td>
                             <td class="border border-black px-1 py-1"></td>
                             <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).regularTotalExclusive) }}</td>
                             <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).regularTotalFed) }}</td>
                             <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).regularTotalSalesTax) }}</td>
                             <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).regularTotalExtraTax) }}</td>
                             <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).regularTotalGross) }}</td>
                             <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).regularTotalTradeDiscount) }}</td>
                             <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).regularTotalSchemeDiscount) }}</td>
                             <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).regularTotalNet) }}</td>
                        </tr>

                        <!-- Free Scheme Section -->
                        <template v-if="getInvoiceTotals(invoice).freeItems.length > 0">
                            <tr>
                                <td colspan="13" class="border border-black px-1 py-1 text-center font-bold bg-white">
                                    Free Piece Scheme:_
                                </td>
                            </tr>
                            <tr v-for="(item, index) in getInvoiceTotals(invoice).freeItems" :key="'free-'+item.id">
                                <td class="border border-black px-1 py-1 text-center">{{ getInvoiceTotals(invoice).regularItems.length + index + 1 }}</td>
                                <td class="border border-black px-1 py-1 text-center">{{ item.product?.dms_code || item.product?.sku }}</td>
                                <td class="border border-black px-1 py-1 text-left">{{ item.product?.name }}</td>
                                <td class="border border-black px-1 py-1 text-center">{{ item.total_pieces }}</td>
                                <td class="border border-black px-1 py-1 text-right">{{ formatAmount(item.list_price_before_tax || item.price) }}</td>
                                <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getItemExclusive(item)) }}</td>
                                <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getItemFed(item)) }}</td>
                                <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getItemSalesTax(item)) }}</td>
                                <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getItemExtraTax(item)) }}</td>
                                <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getItemGross(item)) }}</td>
                                <td class="border border-black px-1 py-1 text-right">{{ formatAmount(item.retail_margin) }}</td>
                                <td class="border border-black px-1 py-1 text-right">{{ formatAmount(parseFloat(item.discount) || 0) }}</td>
                                <td class="border border-black px-1 py-1 text-right font-medium">{{ formatAmount(getItemNet(item)) }}</td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="bg-amber-500 font-bold print:bg-white print:text-black">
                            <td class="border border-black px-1 py-1 text-right" colspan="3">TOTAL WITH FREE SCHEME</td>
                            <td class="border border-black px-1 py-1 text-center">{{ getInvoiceTotals(invoice).totalQty }}</td>
                            <td class="border border-black px-1 py-1"></td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).totalExclusive) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).totalFed) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).totalSalesTax) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).totalExtraTax) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).totalGross) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).totalRetailMargin) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).totalSchemeDiscount) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).totalNet) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Footer Section -->
                <div class="grid grid-cols-2 gap-6 text-xs">
                    <!-- Left: Checked By -->
                    <div>
                        <div class="font-semibold underline">Checked By O.B.: {{ invoice.order_booker?.name }}</div>
                    </div>

                    <!-- Right: Summary Box -->
                    <div class="border border-black">
                        <table class="w-full text-xs">
                            <tr class="border-b border-black">
                                <td class="px-2 py-1">Net Invoice Value Inclusive Sales Tax:</td>
                                <td class="px-2 py-1 text-right font-medium">{{ formatAmount(getInvoiceTotals(invoice).totalNet) }}</td>
                            </tr>
                            <tr class="border-b border-black">
                                <td class="px-2 py-1">Total Discount:</td>
                                <td class="px-2 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).totalRetailMargin + getInvoiceTotals(invoice).totalSchemeDiscount) }}</td>
                            </tr>
                            <tr class="border-b border-black">
                                <td class="px-2 py-1">Advance Tax:</td>
                                <td class="px-2 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).totalAdvTax) }}</td>
                            </tr>
                            <tr class="font-bold">
                                <td class="px-2 py-1">Total Invoice Value:</td>
                                <td class="px-2 py-1 text-right">{{ formatAmount(getInvoiceTotals(invoice).totalNet + getInvoiceTotals(invoice).totalAdvTax) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Urdu Note -->
                <div class="mt-4 text-[9px] text-gray-600 font-urdu text-right" dir="rtl">
                    نوٹ: اپنا مال موقع پر چیک کر کے پورا کر لیں ۔ رقم کی ادائیگی نقد یا ادھار بل وصول کر کے کریں ۔ کمپنی نمائندے سے بل کے بغیر ادائیگی یا ذاتی لین دین کی کمپنی ذمہ دار نہ ہوگی
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print {
    @page { size: A4; margin: 5mm; }
    :deep(aside), :deep(nav), :deep(header) { display: none !important; }
    :deep(main) { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    /* Removed color adjust to avoid forced backgrounds */
    table, th, td { border-color: #000 !important; }
    
    .invoice-page {
        page-break-after: always;
    }
    .invoice-page:last-child {
        page-break-after: auto;
    }
}

.font-urdu {
    font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif;
}
</style>
