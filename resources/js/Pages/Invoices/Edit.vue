<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    invoice: Object,
    products: Array,
    schemes: Array,
    availableStocks: { type: Array, default: () => [] }
});

const selectedCustomer = ref(props.invoice.customer || null);
const productCode = ref('');
const selectedProduct = ref(null);
const productCodeRef = ref(null);
const cartonsRef = ref(null);
const piecesRef = ref(null);
const schemeRef = ref(null);
const discountSchemes = ref([]);
const productSchemes = ref([]);

const newItem = ref({
    product_id: '', stock_id: '', cartons: 0, pieces: 0, total_pieces: 0,
    exclusive_price: 0, fed_percent: 0, sales_tax_percent: 0, extra_tax_percent: 0,
    adv_tax_percent: selectedCustomer.value?.adv_tax_percent || 0, net_unit_price: 0,
    scheme_id: '', scheme_discount: 0, discount_scheme_id: '',
    free_product: null, free_pieces: 0,
    manual_discount_percentage: 0, manual_discount_amount: 0,
    batch_number: '', available_qty: 0, trade_discount_percent: 0, is_net_fixed: false
});

const form = useForm({
    invoice_type: props.invoice.invoice_type,
    is_credit: Boolean(props.invoice.is_credit),
    notes: props.invoice.notes || '',
    distribution_id: props.invoice.distribution_id,
    van_id: props.invoice.van_id,
    order_booker_id: props.invoice.order_booker_id,
    customer_id: props.invoice.customer_id,
    invoice_date: props.invoice.invoice_date,
    items: props.invoice.items.map(item => ({
        id: item.id, product_id: item.product_id,
        product_name: item.product?.name, product_code: item.product?.dms_code || item.product?.sku,
        brand_id: item.product?.brand_id, brand_name: item.product?.brand?.name,
        cartons: item.cartons, pieces: item.pieces, total_pieces: item.total_pieces,
        exclusive_price: parseFloat(item.list_price_before_tax) || parseFloat(item.price),
        fed_percent: parseFloat(item.fed_percent), fed_amount: parseFloat(item.fed_amount),
        sales_tax_percent: parseFloat(item.tax_percent), sales_tax_amount: parseFloat(item.tax),
        extra_tax_percent: parseFloat(item.extra_tax_percent), extra_tax_amount: parseFloat(item.extra_tax_amount),
        adv_tax_percent: parseFloat(item.adv_tax_percent), adv_tax_amount: parseFloat(item.adv_tax_amount),
        gross_amount: parseFloat(item.gross_amount), net_unit_price: parseFloat(item.price),
        scheme_id: item.scheme_id, scheme_name: item.scheme?.name || (item.is_free ? 'FREE' : null),
        scheme_discount: parseFloat(item.scheme_discount), discount_scheme_id: item.scheme_id,
        manual_discount_percentage: 0, manual_discount_amount: parseFloat(item.discount) - parseFloat(item.scheme_discount),
        total_discount: parseFloat(item.discount), trade_discount_amount: parseFloat(item.retail_margin),
        trade_discount_percent: 0, is_free: Boolean(item.is_free), brand_scheme_brand_id: null
    }))
});

const brandQuantities = computed(() => {
    const q = {};
    form.items.forEach(i => { if (!i.is_free && i.brand_id) q[i.brand_id] = (q[i.brand_id] || 0) + i.total_pieces; });
    return q;
});

const productOptions = computed(() => props.products.map(p => ({ ...p, displayLabel: `${p.dms_code || p.sku || ''} - ${p.name}` })));

const availableBatches = computed(() => {
    if (!newItem.value.product_id) return [];
    let stocks = props.availableStocks.filter(s => Number(s.product_id) === Number(newItem.value.product_id));
    if (form.distribution_id) stocks = stocks.filter(s => Number(s.distribution_id) === Number(form.distribution_id));
    return stocks.map(s => ({ ...s, label: `${s.batch_number ? 'Batch: ' + s.batch_number : 'No Batch'} ${s.location ? '(' + s.location + ')' : ''} - Qty: ${s.quantity}` }));
});

const isStockExceeded = computed(() => newItem.value.stock_id && newItem.value.available_qty > 0 && newItem.value.total_pieces > newItem.value.available_qty);

const calculateNetUnitPrice = () => {
    const e = parseFloat(newItem.value.exclusive_price) || 0, f = parseFloat(newItem.value.fed_percent) / 100 || 0;
    const s = parseFloat(newItem.value.sales_tax_percent) / 100 || 0, x = parseFloat(newItem.value.extra_tax_percent) / 100 || 0;
    const isFood = (selectedProduct.value?.product_type?.name || '').toLowerCase() === 'food';
    newItem.value.net_unit_price = isFood ? e * (1 + s + x) : e * (1 + f) * (1 + s);
};

const calculateReversePrice = () => {
    const n = parseFloat(newItem.value.net_unit_price) || 0, f = parseFloat(newItem.value.fed_percent) / 100 || 0;
    const s = parseFloat(newItem.value.sales_tax_percent) / 100 || 0, x = parseFloat(newItem.value.extra_tax_percent) / 100 || 0;
    const isFood = (selectedProduct.value?.product_type?.name || '').toLowerCase() === 'food';
    const d = isFood ? (1 + s + x) : (1 + f) * (1 + s);
    newItem.value.exclusive_price = d <= 1 ? n : n / d;
};

const handleTaxChange = () => newItem.value.is_net_fixed ? calculateReversePrice() : calculateNetUnitPrice();
const onExclusivePriceInput = () => { newItem.value.is_net_fixed = false; calculateNetUnitPrice(); };

const loadDiscountSchemes = async (productId, quantity, brandId = null) => {
    try {
        let bq = quantity; if (brandId) bq = (brandQuantities.value[brandId] || 0) + quantity;
        const r = await axios.get(route('api.discount-schemes', productId), { params: { quantity, brand_quantity: bq, sub_distribution_id: selectedCustomer.value?.sub_distribution_id } });
        discountSchemes.value = r.data;
        newItem.value.discount_scheme_id = ''; newItem.value.scheme_discount = 0; newItem.value.free_product = null; newItem.value.free_pieces = 0;
    } catch { discountSchemes.value = []; }
};

const applyDiscountScheme = (scheme) => {
    if (!scheme) { newItem.value.discount_scheme_id = ''; newItem.value.scheme_discount = 0; newItem.value.free_product = null; newItem.value.free_pieces = 0; return; }
    newItem.value.discount_scheme_id = scheme.id;
    if (scheme.discount_type === 'amount_less') { newItem.value.scheme_discount = parseFloat(scheme.amount_less) || 0; newItem.value.free_product = null; }
    else if (scheme.discount_type === 'free_product') { newItem.value.scheme_discount = 0; newItem.value.free_product = scheme.free_product; newItem.value.free_pieces = scheme.free_pieces || 0; }
};

watch(() => newItem.value.product_id, (v) => {
    if (v) {
        const p = props.products.find(x => x.id === parseInt(v));
        if (p) {
            selectedProduct.value = p; newItem.value.stock_id = ''; newItem.value.batch_number = ''; newItem.value.available_qty = 0;
            newItem.value.exclusive_price = parseFloat(p.list_price_before_tax) || 0;
            newItem.value.fed_percent = parseFloat(p.fed_percent) || 0;
            newItem.value.sales_tax_percent = parseFloat(p.fed_sales_tax) || 0;
            newItem.value.extra_tax_percent = parseFloat(p.product_type?.extra_tax) || 0;
            newItem.value.trade_discount_percent = parseFloat(p.retail_margin) || 0;
            newItem.value.adv_tax_percent = parseFloat(selectedCustomer.value?.adv_tax_percent) || 0;
            const isFood = (p.product_type?.name || '').toLowerCase() === 'food';
            if (isFood) { newItem.value.is_net_fixed = false; calculateNetUnitPrice(); }
            else { newItem.value.net_unit_price = parseFloat(p.unit_price) || 0; newItem.value.is_net_fixed = true; calculateReversePrice(); }
            productCode.value = p.dms_code || p.sku || '';
            const stocks = availableBatches.value;
            if (stocks.length > 0) newItem.value.stock_id = stocks[0].id;
            else Swal.fire({ icon: 'warning', title: 'Stock Not Available', text: `No stock for "${p.name}".`, confirmButtonColor: '#059669' });
            setTimeout(() => cartonsRef.value?.focus(), 100);
        }
    } else { selectedProduct.value = null; discountSchemes.value = []; }
});

watch(() => newItem.value.stock_id, (v) => {
    const s = availableBatches.value.find(x => x.id === v);
    newItem.value.batch_number = s?.batch_number || ''; newItem.value.available_qty = s?.quantity || 0;
});

watch([() => newItem.value.cartons, () => newItem.value.pieces], () => {
    const pk = selectedProduct.value?.pieces_per_packing || selectedProduct.value?.packing?.quantity || 12;
    newItem.value.total_pieces = (newItem.value.cartons * pk) + newItem.value.pieces;
    if (newItem.value.product_id && newItem.value.total_pieces > 0) loadDiscountSchemes(newItem.value.product_id, newItem.value.total_pieces, selectedProduct.value?.brand_id);
});

const addItem = () => {
    if (!newItem.value.product_id || newItem.value.total_pieces <= 0) return;
    const p = selectedProduct.value;
    if (form.items.some(i => i.product_id === parseInt(newItem.value.product_id) && !i.is_free)) {
        Swal.fire({ icon: 'error', title: 'Product Already Added', text: `"${p?.name}" is already in the invoice.`, confirmButtonColor: '#059669' }); return;
    }
    if (newItem.value.stock_id && newItem.value.available_qty > 0 && newItem.value.total_pieces > newItem.value.available_qty) {
        Swal.fire({ icon: 'warning', title: 'Insufficient Stock', text: `Requested: ${newItem.value.total_pieces}, Available: ${newItem.value.available_qty}`, confirmButtonColor: '#059669' }); return;
    }
    const excl = newItem.value.exclusive_price * newItem.value.total_pieces;
    const fed = excl * (newItem.value.fed_percent / 100);
    const sTax = (excl + fed) * (newItem.value.sales_tax_percent / 100);
    const xTax = excl * ((newItem.value.extra_tax_percent || 0) / 100);
    const gross = excl + fed + sTax + xTax;
    const tradeDis = (gross / (1 + newItem.value.trade_discount_percent / 100)) * (newItem.value.trade_discount_percent / 100);
    const manDis = gross * (newItem.value.manual_discount_percentage / 100) + newItem.value.manual_discount_amount;
    const totalDis = (newItem.value.scheme_discount || 0) + manDis;
    const advTax = (gross - tradeDis - totalDis) * (newItem.value.adv_tax_percent / 100);
    form.items.push({
        id: null, product_id: p.id, product_name: p.name, product_code: p.dms_code || p.sku,
        brand_id: p.brand_id, brand_name: p.brand?.name, stock_id: newItem.value.stock_id, batch_number: newItem.value.batch_number,
        cartons: newItem.value.cartons, pieces: newItem.value.pieces, total_pieces: newItem.value.total_pieces,
        exclusive_price: newItem.value.exclusive_price, fed_percent: newItem.value.fed_percent, fed_amount: fed,
        sales_tax_percent: newItem.value.sales_tax_percent, sales_tax_amount: sTax,
        extra_tax_percent: newItem.value.extra_tax_percent, extra_tax_amount: xTax,
        adv_tax_percent: newItem.value.adv_tax_percent, adv_tax_amount: advTax, gross_amount: gross,
        net_unit_price: newItem.value.net_unit_price, scheme_id: newItem.value.scheme_id,
        scheme_name: discountSchemes.value.find(s => s.id == newItem.value.scheme_id)?.name,
        scheme_discount: newItem.value.scheme_discount, discount_scheme_id: newItem.value.discount_scheme_id,
        free_product: newItem.value.free_product, free_pieces: newItem.value.free_pieces,
        manual_discount_percentage: newItem.value.manual_discount_percentage, manual_discount_amount: newItem.value.manual_discount_amount,
        total_discount: totalDis, trade_discount_percent: newItem.value.trade_discount_percent, trade_discount_amount: tradeDis, is_free: false
    });
    if (newItem.value.free_product && newItem.value.free_pieces > 0) addFreeItem(newItem.value.free_product, newItem.value.free_pieces);
    form.items.sort((a, b) => (a.is_free === b.is_free) ? 0 : (a.is_free ? 1 : -1));
    resetNewItem();
};

const addFreeItem = (fp, qty) => {
    const exP = parseFloat(fp.list_price_before_tax) || 0;
    const fedP = parseFloat(fp.fed_percent) || 0, sP = parseFloat(fp.fed_sales_tax) || 0, xP = parseFloat(fp.product_type?.extra_tax) || 0;
    const ex = exP * qty, fd = ex * fedP / 100, st = (ex + fd) * sP / 100, xt = ex * xP / 100, gr = ex + fd + st + xt;
    form.items.push({
        id: null, product_id: fp.id, product_name: fp.name + ' (FREE)', product_code: fp.dms_code,
        cartons: 0, pieces: qty, total_pieces: qty, exclusive_price: exP,
        fed_percent: fedP, fed_amount: fd, sales_tax_percent: sP, sales_tax_amount: st,
        extra_tax_percent: xP, extra_tax_amount: xt, adv_tax_percent: 0, adv_tax_amount: 0,
        gross_amount: gr, net_unit_price: exP * (1 + fedP / 100) * (1 + sP / 100),
        scheme_name: 'FREE', total_discount: 0, trade_discount_amount: gr, is_free: true
    });
};

const resetNewItem = () => {
    newItem.value = { product_id: '', stock_id: '', cartons: 0, pieces: 0, total_pieces: 0, exclusive_price: 0, fed_percent: 0, sales_tax_percent: 0, extra_tax_percent: 0, adv_tax_percent: selectedCustomer.value?.adv_tax_percent || 0, net_unit_price: 0, scheme_id: '', scheme_discount: 0, discount_scheme_id: '', free_product: null, free_pieces: 0, manual_discount_percentage: 0, manual_discount_amount: 0, batch_number: '', available_qty: 0, trade_discount_percent: 0, is_net_fixed: false };
    selectedProduct.value = null; productCode.value = ''; discountSchemes.value = [];
    setTimeout(() => productCodeRef.value?.focus(), 100);
};

const removeItem = (idx) => form.items.splice(idx, 1);
const searchProductByCode = () => { const f = props.products.find(p => (p.dms_code?.toLowerCase() === productCode.value.toLowerCase()) || (p.sku?.toLowerCase() === productCode.value.toLowerCase())); if (f) newItem.value.product_id = f.id; };

const getItemExclusive = (i) => i.exclusive_price * i.total_pieces;
const getItemFed = (i) => getItemExclusive(i) * (i.fed_percent / 100);
const getItemSalesTax = (i) => getItemExclusive(i) * (1 + i.fed_percent / 100) * (i.sales_tax_percent / 100);
const getItemExtraTax = (i) => getItemExclusive(i) * ((i.extra_tax_percent || 0) / 100);
const getItemAdvTax = (i) => { const g = getItemExclusive(i) + getItemFed(i) + getItemSalesTax(i) + getItemExtraTax(i); return (g - getItemDiscount(i) - (i.trade_discount_amount || 0)) * (i.adv_tax_percent / 100); };
const getItemDiscount = (i) => { const g = i.total_pieces * i.net_unit_price; return (i.scheme_discount || 0) + g * ((i.manual_discount_percent || 0) / 100) + (i.manual_discount_amount || 0); };

const totalExclusive = computed(() => form.items.reduce((s, i) => s + getItemExclusive(i), 0));
const totalFed = computed(() => form.items.reduce((s, i) => s + getItemFed(i), 0));
const totalSalesTax = computed(() => form.items.reduce((s, i) => s + getItemSalesTax(i), 0));
const totalExtraTax = computed(() => form.items.reduce((s, i) => s + getItemExtraTax(i), 0));
const totalAdvTax = computed(() => form.items.reduce((s, i) => s + getItemAdvTax(i), 0));
const totalGrossAmount = computed(() => form.items.reduce((s, i) => s + getItemExclusive(i) + getItemFed(i) + getItemSalesTax(i) + getItemExtraTax(i), 0));
const totalDiscount = computed(() => form.items.reduce((s, i) => s + getItemDiscount(i), 0));
const totalTradeDiscount = computed(() => form.items.reduce((s, i) => s + (i.trade_discount_amount || 0), 0));
const grandTotal = computed(() => totalGrossAmount.value - totalDiscount.value - totalTradeDiscount.value + totalAdvTax.value);
const formatAmount = (a) => new Intl.NumberFormat('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 5 }).format(a || 0);

const handleKeydown = (e) => { if (e.key === 'F2') { e.preventDefault(); submit(); } };
onMounted(() => document.addEventListener('keydown', handleKeydown));
onUnmounted(() => document.removeEventListener('keydown', handleKeydown));

const submit = () => {
    if (form.items.length === 0) return;
    form.put(route('invoices.update', props.invoice.id));
};
</script>

<template>

    <Head title="Edit Invoice" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Invoice #{{ invoice.invoice_number }}</h1>
                    <p class="text-gray-500 mt-1">Modify invoice. Press F2 to save.</p>
                </div>
                <Link :href="route('invoices.show', invoice.id)" class="text-gray-500 hover:text-gray-700">← Back</Link>
            </div>
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Invoice Details (Read-Only Context) -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Invoice Details</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <InputLabel value="VAN" />
                            <div class="mt-1 px-3 py-2 bg-gray-100 rounded-md font-medium">{{ invoice.van?.code }}</div>
                        </div>
                        <div>
                            <InputLabel value="Order Booker" />
                            <div class="mt-1 px-3 py-2 bg-gray-100 rounded-md font-medium">{{ invoice.order_booker?.name
                                }}</div>
                        </div>
                        <div>
                            <InputLabel value="Invoice Date" />
                            <TextInput v-model="form.invoice_date" type="date" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="Invoice Type" /><select v-model="form.invoice_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="sale">Sale</option>
                                <option value="damage">Damage</option>
                                <option value="shelf_rent">Shelf Rent</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2 pt-6"><input type="checkbox" v-model="form.is_credit"
                                id="is_credit"
                                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"><label
                                for="is_credit" class="text-sm text-gray-700">Credit Sale</label></div>
                    </div>
                </div>
                <!-- Customer Info -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Customer</h2>
                    <div v-if="selectedCustomer" class="p-4 bg-emerald-50 rounded-xl border border-emerald-200">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div><span class="text-gray-500">Code:</span><span class="font-medium ml-2">{{
                                selectedCustomer.customer_code }}</span></div>
                            <div><span class="text-gray-500">Shop:</span><span class="font-medium ml-2">{{
                                selectedCustomer.shop_name }}</span></div>
                            <div><span class="text-gray-500">Address:</span><span class="font-medium ml-2">{{
                                selectedCustomer.address }}</span></div>
                            <div><span class="text-gray-500">Phone:</span><span class="font-medium ml-2">{{
                                selectedCustomer.phone }}</span></div>
                            <div><span class="text-gray-500">NTN:</span><span class="font-medium ml-2">{{
                                selectedCustomer.ntn_number || 'N/A' }}</span></div>
                            <div><span class="text-gray-500">CNIC:</span><span class="font-medium ml-2">{{
                                selectedCustomer.cnic || 'N/A' }}</span></div>
                            <div><span class="text-gray-500">Day:</span><span class="font-medium ml-2">{{
                                selectedCustomer.day }}</span></div>
                            <div><span class="text-gray-500">ATL:</span><span class="font-medium ml-2"
                                    :class="selectedCustomer.atl ? 'text-green-600' : 'text-red-600'">{{
                                        selectedCustomer.atl ? 'Yes' : 'No' }}</span></div>
                        </div>
                    </div>
                </div>
                <!-- Product Entry -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Add Products</h2>
                    <div class="grid grid-cols-6 lg:grid-cols-12 gap-3 items-end mb-4">
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Product Code" />
                            <div class="flex items-stretch mt-1">
                                <TextInput ref="productCodeRef" v-model="productCode" placeholder="Enter code"
                                    class="flex-1 !rounded-r-none !border-r-0 min-w-0"
                                    @keyup.enter="searchProductByCode" /><button type="button"
                                    @click="searchProductByCode"
                                    class="px-3 flex items-center justify-center bg-emerald-600 text-white rounded-r-md hover:bg-emerald-700 border border-emerald-600 shrink-0"><svg
                                        class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg></button>
                            </div>
                        </div>
                        <div class="col-span-4 lg:col-span-3">
                            <SearchableSelect v-model="newItem.product_id" label="Product" :options="productOptions"
                                option-value="id" option-label="displayLabel" placeholder="Search product..." />
                        </div>
                        <div class="col-span-3 lg:col-span-2">
                            <InputLabel value="Batch / Stock" /><template
                                v-if="newItem.product_id && availableBatches.length === 0">
                                <div class="mt-1 px-3 py-2 bg-gray-100 rounded-md text-gray-500 text-sm font-medium">
                                    Batch N/A</div>
                            </template><template v-else>
                                <SearchableSelect v-model="newItem.stock_id" :options="availableBatches"
                                    option-value="id" option-label="label" placeholder="Select Batch"
                                    :disabled="!newItem.product_id" class="mt-1" />
                            </template>
                            <div v-if="newItem.stock_id && newItem.available_qty > 0"
                                class="text-xs text-emerald-600 mt-1 font-medium">Available:
                                {{ newItem.available_qty }} pcs</div>
                        </div>
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Cartons" />
                            <TextInput ref="cartonsRef" v-model.number="newItem.cartons" type="number" min="0"
                                class="mt-1 w-full text-center font-medium"
                                @keydown.enter.prevent="piecesRef?.focus()" />
                        </div>
                        <div class="col-span-2 lg:col-span-1">
                            <InputLabel value="Pieces" />
                            <TextInput ref="piecesRef" v-model.number="newItem.pieces" type="number" min="0"
                                class="mt-1 w-full text-center font-medium"
                                @keydown.enter.prevent="schemeRef?.focus()" />
                        </div>
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Total Pcs" />
                            <TextInput :value="newItem.total_pieces" type="number"
                                :class="['mt-1 w-full text-center font-bold', isStockExceeded ? 'bg-red-50 text-red-700 border-red-300' : 'bg-emerald-50 text-emerald-700']"
                                readonly />
                            <div v-if="isStockExceeded" class="text-xs text-red-600 mt-1 font-medium">Exceeds available
                                ({{
                                    newItem.available_qty }})</div>
                        </div>
                    </div>
                    <div v-if="newItem.product_id" class="bg-gray-50 p-4 rounded-lg mb-4">
                        <div class="grid grid-cols-6 lg:grid-cols-10 gap-3 items-end">
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Excl. Price" class="text-xs" />
                                <TextInput v-model.number="newItem.exclusive_price" type="number" step="0.00001"
                                    class="mt-1 w-full text-sm text-center" @input="onExclusivePriceInput" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="FED %" class="text-xs" />
                                <TextInput v-model.number="newItem.fed_percent" type="number" step="0.00001"
                                    class="mt-1 w-full text-sm text-center" @input="handleTaxChange" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="S.Tax %" class="text-xs" />
                                <TextInput v-model.number="newItem.sales_tax_percent" type="number" step="0.00001"
                                    class="mt-1 w-full text-sm text-center" @input="handleTaxChange" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Extra Tax %" class="text-xs" />
                                <TextInput v-model.number="newItem.extra_tax_percent" type="number" step="0.00001"
                                    class="mt-1 w-full text-sm text-center" @input="handleTaxChange" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Adv.Tax %" class="text-xs" />
                                <TextInput v-model.number="newItem.adv_tax_percent" type="number" step="0.00001"
                                    class="mt-1 w-full text-sm text-center" @input="handleTaxChange" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Net Price" class="text-xs font-bold text-indigo-600" />
                                <TextInput :model-value="formatAmount(newItem.net_unit_price)" type="text"
                                    class="mt-1 w-full bg-indigo-50 font-bold text-indigo-700 text-sm text-center"
                                    readonly />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Trade Disc. %" class="text-xs" />
                                <TextInput v-model.number="newItem.trade_discount_percent" type="number" step="0.00001"
                                    class="mt-1 w-full text-sm text-center bg-amber-50 text-amber-700" readonly />
                            </div>
                            <div class="col-span-3 lg:col-span-2">
                                <InputLabel value="Scheme" class="text-xs" /><select ref="schemeRef"
                                    v-model="newItem.discount_scheme_id"
                                    @change="discountSchemes.length > 0 && applyDiscountScheme(discountSchemes.find(s => s.id == newItem.discount_scheme_id))"
                                    @keydown.enter.prevent="addItem"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm h-[42px]"
                                    :class="newItem.free_product ? 'bg-green-50 border-green-300' : (newItem.scheme_discount > 0 ? 'bg-orange-50 border-orange-300' : '')">
                                    <option value="">No Scheme</option>
                                    <option v-for="s in discountSchemes" :key="s.id" :value="s.id">{{ s.name }} - {{
                                        s.discount_type ===
                                            'amount_less' ? `Rs ${s.amount_less}` : `${s.free_pieces} FREE` }}</option>
                                </select>
                            </div>
                            <div class="col-span-2 lg:col-span-1"><button type="button" @click="addItem"
                                    :disabled="!newItem.product_id || newItem.total_pieces <= 0"
                                    class="w-full mt-5 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">+
                                    Add</button></div>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 text-gray-400 text-sm">Select a product to see pricing details
                    </div>
                </div>
                <!-- Product Grid -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50">
                        <h2 class="text-lg font-medium text-gray-900">Items ({{ form.items.length }})</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                                <tr>
                                    <th class="px-2 py-3">#</th>
                                    <th class="px-2 py-3">Product</th>
                                    <th class="px-2 py-3 text-right">Ctns</th>
                                    <th class="px-2 py-3 text-right">Pcs</th>
                                    <th class="px-2 py-3 text-right">Total</th>
                                    <th class="px-2 py-3 text-right">Rate</th>
                                    <th class="px-2 py-3 text-right">Excl. Amt</th>
                                    <th class="px-2 py-3 text-right">FED</th>
                                    <th class="px-2 py-3 text-right">S.Tax</th>
                                    <th class="px-2 py-3 text-right">Extra Tax</th>
                                    <th class="px-2 py-3 text-right">Adv.Tax</th>
                                    <th class="px-2 py-3 text-right">Gross</th>
                                    <th class="px-2 py-3 text-right">Trade Disc.</th>
                                    <th class="px-2 py-3 text-right">Discount</th>
                                    <th class="px-2 py-3 text-right">Net</th>
                                    <th class="px-2 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, index) in form.items" :key="index"
                                    :class="{ 'bg-emerald-50/60': item.is_free }">
                                    <td class="px-2 py-3">{{ index + 1 }}</td>
                                    <td class="px-2 py-3">
                                        <div class="font-medium" :class="{ 'text-emerald-700': item.is_free }">{{
                                            item.product_name
                                            }}<span v-if="item.is_free"
                                                class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 uppercase tracking-wide border border-emerald-200">FREE</span>
                                        </div>
                                        <div class="text-xs text-gray-500">{{ item.product_code }}</div>
                                    </td>
                                    <td class="px-2 py-3 text-right">{{ item.cartons }}</td>
                                    <td class="px-2 py-3 text-right">{{ item.pieces }}</td>
                                    <td class="px-2 py-3 text-right font-medium">{{ item.total_pieces }}</td>
                                    <td class="px-2 py-3 text-right">{{ formatAmount(item.exclusive_price) }}<span
                                            v-if="item.is_free"
                                            class="ml-1 text-[10px] font-bold text-emerald-600">(FREE)</span></td>
                                    <td class="px-2 py-3 text-right text-gray-600">{{
                                        formatAmount(getItemExclusive(item)) }}</td>
                                    <td class="px-2 py-3 text-right text-gray-500">{{ formatAmount(getItemFed(item)) }}
                                        <div class="text-xs">({{ item.fed_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-gray-500">{{
                                        formatAmount(getItemSalesTax(item)) }}<div class="text-xs">({{
                                            item.sales_tax_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-purple-600">{{
                                        formatAmount(getItemExtraTax(item)) }}<div class="text-xs">({{
                                            item.extra_tax_percent || 0 }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-gray-500">{{ formatAmount(getItemAdvTax(item))
                                        }}<div class="text-xs">({{ item.adv_tax_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right font-medium">{{ formatAmount(item.gross_amount) }}
                                    </td>
                                    <td class="px-2 py-3 text-right text-amber-600">{{
                                        formatAmount(item.trade_discount_amount || 0) }}
                                    </td>
                                    <td class="px-2 py-3 text-right text-red-600">-{{
                                        formatAmount(getItemDiscount(item)) }}</td>
                                    <td class="px-2 py-3 text-right font-semibold text-emerald-600">{{
                                        formatAmount(item.gross_amount -
                                            getItemDiscount(item) - (item.trade_discount_amount || 0)) }}</td>
                                    <td class="px-2 py-3"><button type="button" @click="removeItem(index)"
                                            class="text-red-600 hover:text-red-800"><svg class="w-5 h-5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg></button></td>
                                </tr>
                                <tr v-if="form.items.length === 0">
                                    <td colspan="16" class="px-4 py-8 text-center text-gray-500">No items added. Use the
                                        form above to
                                        add products.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Invoice Summary -->
                    <div v-if="form.items.length > 0" class="border-t border-gray-200 bg-gray-50 p-4">
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                            <div class="bg-white p-3 rounded-lg border">
                                <div class="text-gray-500 text-xs uppercase">Total Exclusive</div>
                                <div class="font-bold text-lg">{{ formatAmount(totalExclusive) }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border">
                                <div class="text-gray-500 text-xs uppercase">Total FED</div>
                                <div class="font-bold text-lg">{{ formatAmount(totalFed) }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border">
                                <div class="text-gray-500 text-xs uppercase">Total Sales Tax</div>
                                <div class="font-bold text-lg">{{ formatAmount(totalSalesTax) }}</div>
                            </div>
                            <div class="bg-purple-50 p-3 rounded-lg border border-purple-200">
                                <div class="text-purple-500 text-xs uppercase">Total Extra Tax</div>
                                <div class="font-bold text-lg text-purple-700">{{ formatAmount(totalExtraTax) }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border">
                                <div class="text-gray-500 text-xs uppercase">Total Adv. Tax</div>
                                <div class="font-bold text-lg">{{ formatAmount(totalAdvTax) }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                            <div class="bg-white p-3 rounded-lg border">
                                <div class="text-gray-500 text-xs uppercase">Gross Amount</div>
                                <div class="font-bold text-lg">{{ formatAmount(totalGrossAmount) }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-red-200">
                                <div class="text-red-500 text-xs uppercase">Total Discount</div>
                                <div class="font-bold text-lg text-red-600">-{{ formatAmount(totalDiscount) }}</div>
                            </div>
                            <div class="bg-amber-50 p-3 rounded-lg border border-amber-200">
                                <div class="text-amber-600 text-xs uppercase">Total Trade Discount</div>
                                <div class="font-bold text-lg text-amber-700">-{{ formatAmount(totalTradeDiscount) }}
                                </div>
                            </div>
                            <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-300">
                                <div class="text-emerald-600 text-xs uppercase font-bold">Grand Total</div>
                                <div class="font-bold text-2xl text-emerald-700">Rs. {{ formatAmount(grandTotal) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('invoices.show', invoice.id)">
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton :disabled="form.processing || form.items.length === 0">{{ form.processing ?
                        'Saving...' : 'Update Invoice' }}</PrimaryButton>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
