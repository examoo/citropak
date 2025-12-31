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
    return new Date(date).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).replace(/\//g, '-');
};

// Get item values directly from database - NO calculations, show as stored
const getItemRate = (item) => parseFloat(item.list_price_before_tax) || 0;
const getItemExclusive = (item) => parseFloat(item.exclusive_amount) || (item.total_pieces * getItemRate(item));
const getItemFed = (item) => parseFloat(item.fed_amount) || 0;
const getItemSalesTax = (item) => parseFloat(item.tax) || 0;
const getItemExtraTax = (item) => parseFloat(item.extra_tax_amount) || 0;
const getItemAdvTax = (item) => parseFloat(item.adv_tax_amount) || 0;
const getItemGross = (item) => parseFloat(item.gross_amount) || parseFloat(item.line_total) || 0;
const getItemNet = (item) => parseFloat(item.line_total) || 0;

// Totals
const totalQty = computed(() => props.invoice.items?.reduce((sum, item) => sum + (item.total_pieces || 0), 0) || 0);
const totalExclusive = computed(() => props.invoice.items?.reduce((sum, item) => sum + getItemExclusive(item), 0) || 0);
const totalFed = computed(() => props.invoice.items?.reduce((sum, item) => sum + getItemFed(item), 0) || 0);
const totalSalesTax = computed(() => props.invoice.items?.reduce((sum, item) => sum + getItemSalesTax(item), 0) || 0);
const totalExtraTax = computed(() => props.invoice.items?.reduce((sum, item) => sum + getItemExtraTax(item), 0) || 0);
const totalAdvTax = computed(() => props.invoice.items?.reduce((sum, item) => sum + parseFloat(item.adv_tax_amount || 0), 0) || 0);
const totalGross = computed(() => props.invoice.items?.reduce((sum, item) => sum + getItemGross(item), 0) || 0);
const totalTradeDiscount = computed(() => props.invoice.items?.reduce((sum, item) => sum + parseFloat(item.discount || 0), 0) || 0);
const totalSchemeDiscount = computed(() => props.invoice.items?.reduce((sum, item) => sum + parseFloat(item.scheme_discount || 0), 0) || 0);
const totalRetailMargin = computed(() => props.invoice.items?.reduce((sum, item) => {
    const rate = parseFloat(item.list_price_before_tax) || 0;
    const qty = item.total_pieces || 0;
    const marginPercent = parseFloat(item.product?.retail_margin) || 0;
    return sum + (rate * qty * marginPercent / 100);
}, 0) || 0);
const totalNet = computed(() => props.invoice.items?.reduce((sum, item) => sum + getItemNet(item), 0) || 0);

const printInvoice = () => {
    window.print();
};

const resyncFbr = () => {
    router.post(route('invoices.resync-fbr', props.invoice.id), {}, {
        preserveScroll: true,
        onError: (errors) => {
            alert(errors.error || 'Failed to sync with FBR');
        }
    });
};
</script>

<template>
    <Head :title="`Invoice: ${invoice.invoice_number}`" />

    <DashboardLayout>
        <div class="space-y-6 max-w-6xl">
            <!-- Header (hidden in print) -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ invoice.invoice_number }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-gray-500">Invoice Details</p>
                        <!-- FBR Status Badge -->
                        <span v-if="invoice.fbr_status === 'synced'" class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            FBR Synced
                        </span>
                        <span v-else-if="invoice.fbr_status === 'pending'" class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                            FBR Pending
                        </span>
                        <span v-else-if="invoice.fbr_status === 'failed'" class="px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                            FBR Failed
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button v-if="invoice.fbr_status === 'failed' || (invoice.fbr_status === 'pending' && !invoice.fbr_invoice_number)" 
                        @click="resyncFbr" 
                        class="px-3 py-2 bg-indigo-50 text-indigo-700 rounded-lg border border-indigo-200 hover:bg-indigo-100 text-sm font-medium transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Resync FBR
                    </button>
                    <Link :href="route('invoices.index')" class="text-gray-500 hover:text-gray-700">← Back</Link>
                    <Link :href="route('invoices.edit', invoice.id)" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">Edit</Link>
                    <button @click="printInvoice" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Print</button>
                </div>
            </div>

            <!-- Invoice Print Format -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 print:shadow-none print:border-0 print:p-2 print:rounded-none">
                
                <!-- Title -->
                <div class="text-center border-b border-gray-400 pb-4 mb-4">
                    <h1 class="text-xl font-bold uppercase tracking-wide">FED & SALE TAX INVOICE</h1>
                </div>

                <!-- Company & Customer Info -->
                <div class="grid grid-cols-2 gap-6 mb-4 text-xs">
                    <!-- Left: Customer Info -->
                    <div class="space-y-1">
                        <div class="font-bold text-sm">Customer Detail:</div>
                        <div><span class="text-gray-600">CustomerCode:</span> <span class="font-medium">{{ invoice.customer?.customer_code }}</span></div>
                        <div><span class="text-gray-600">CustomerName:</span> <span class="font-medium">{{ invoice.customer?.shop_name }}</span></div>
                        <div><span class="text-gray-600">CustomerAddress:</span> {{ invoice.customer?.address }}</div>
                        <div><span class="text-gray-600">CNIC/NTN:</span> {{ invoice.customer?.ntn_number || invoice.customer?.cnic || '' }}</div>
                        <div><span class="text-gray-600">Phone#:</span> {{ invoice.customer?.phone || '0' }}</div>
                        <div><span class="text-gray-600">S.Tax ATL:</span> <span :class="invoice.customer?.sales_tax_status === 'active' ? 'text-green-600' : 'text-red-600'">{{ invoice.customer?.sales_tax_status?.toUpperCase() || 'INACTIVE' }}</span></div>
                        <div><span class="text-gray-600">Business Category:</span> {{ invoice.customer?.category || 'Retail' }}</div>
                    </div>

                    <!-- Right: Distribution/Company Info & FBR QR -->
                    <div class="text-right space-y-1">
                        <div class="flex justify-end gap-4">
                            <!-- FBR QR Code Display -->
                            <div v-if="invoice.fbr_qr_code" class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-white p-1 border border-gray-200">
                                    <img :src="`data:image/png;base64,${invoice.fbr_qr_code}`" alt="FBR QR" class="w-full h-full object-contain" v-if="invoice.fbr_qr_code.length > 200" />
                                    <!-- Fallback for text-based QR code content -->
                                    <div v-else class="w-full h-full flex items-center justify-center bg-gray-100 text-[8px] text-center overflow-hidden">
                                        FBR QR
                                    </div>
                                </div>
                                <div class="text-[8px] font-bold mt-0.5">FBR Invoice</div>
                                <div class="text-[8px] font-mono">{{ invoice.fbr_invoice_number }}</div>
                            </div>

                            <div class="text-right space-y-1">
                                <div class="font-bold text-sm">CITROPAK LTD</div>
                                <div>15-6 New Civil Line Sargodha</div>
                                <div>NTN No: 0683798-7</div>
                                <div>Contact#: 0301-8441306</div>
                                <div><span class="font-semibold">Invoice#:</span> {{ invoice.invoice_number }}</div>
                                <div><span class="font-semibold">Date:</span> {{ formatDate(invoice.invoice_date) }}</div>
                                <div><span class="font-semibold">Van#:</span> {{ invoice.van?.code }}</div>
                                <!-- FBR Info -->
                                <div v-if="invoice.fbr_invoice_number"><span class="font-semibold">FBR Inv#:</span> {{ invoice.fbr_invoice_number }}</div>
                                <div v-if="invoice.fbr_pos_id"><span class="font-semibold">POS ID:</span> {{ invoice.fbr_pos_id }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="w-full text-[10px] border-collapse border border-black mb-4">
                    <thead>
                        <tr class="bg-gray-100">
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
                        <tr v-for="(item, index) in invoice.items" :key="item.id">
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
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount((parseFloat(item.list_price_before_tax) || 0) * (item.total_pieces || 0) * (parseFloat(item.product?.retail_margin) || 0) / 100) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount((item.discount || 0) + (item.scheme_discount || 0)) }}</td>
                            <td class="border border-black px-1 py-1 text-right font-medium">{{ formatAmount(getItemNet(item)) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-bold">
                            <td class="border border-black px-1 py-1 text-right" colspan="3">Total</td>
                            <td class="border border-black px-1 py-1 text-center">{{ totalQty }}</td>
                            <td class="border border-black px-1 py-1"></td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(totalExclusive) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(totalFed) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(totalSalesTax) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(totalExtraTax) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(totalGross) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(totalRetailMargin) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(totalTradeDiscount + totalSchemeDiscount) }}</td>
                            <td class="border border-black px-1 py-1 text-right">{{ formatAmount(totalNet) }}</td>
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
                                <td class="px-2 py-1 text-right font-medium">{{ formatAmount(totalNet) }}</td>
                            </tr>
                            <tr class="border-b border-black">
                                <td class="px-2 py-1">Total Discount:</td>
                                <td class="px-2 py-1 text-right">{{ formatAmount(totalTradeDiscount + totalSchemeDiscount) }}</td>
                            </tr>
                            <tr class="border-b border-black">
                                <td class="px-2 py-1">Advance Tax:</td>
                                <td class="px-2 py-1 text-right">{{ formatAmount(totalAdvTax) }}</td>
                            </tr>
                            <tr class="font-bold">
                                <td class="px-2 py-1">Total Invoice Value:</td>
                                <td class="px-2 py-1 text-right">{{ formatAmount(totalNet) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Urdu Note (optional) -->
                <div class="mt-4 text-[9px] text-gray-600 font-urdu text-right" dir="rtl">
                    نوٹ: اپنا مال موقع پر چیک کر کے پورا کر لیں ۔ رقم کی ادائیگی نقد یا ادھار بل وصول کر کے کریں ۔ کمپنی نمائندے سے بل کے بغیر ادائیگی یا ذاتی لین دین کی کمپنی ذمہ دار نہ ہوگی
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print {
    @page { size: A4 landscape; margin: 5mm; }
    :deep(aside), :deep(nav), :deep(header) { display: none !important; }
    :deep(main) { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    table, th, td { border-color: #000 !important; }
}

.font-urdu {
    font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif;
}
</style>
