<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    invoice: Object,
    products: Array,
    schemes: Array,
    discountSchemes: Array
});

// Product entry state
const productCode = ref('');
const selectedProduct = ref(null);
const newItem = ref({
    product_id: '',
    cartons: 0,
    pieces: 0,
    total_pieces: 0,
    exclusive_price: 0,
    fed_percent: 0,
    sales_tax_percent: 0,
    extra_tax_percent: 0,
    adv_tax_percent: 0,
    net_unit_price: 0,
    scheme_id: '',
    scheme_discount: 0,
    discount_scheme_id: '',
    free_product: null,
    free_pieces: 0,
    manual_discount_percent: 0,
    manual_discount_amount: 0
});
const productSchemes = ref([]);

// Form with existing data - map all fields including tax amounts
const form = useForm({
    invoice_type: props.invoice.invoice_type,
    is_credit: props.invoice.is_credit,
    notes: props.invoice.notes || '',
    items: props.invoice.items.map(item => ({
        id: item.id,
        product_id: item.product_id,
        product_name: item.product?.name,
        product_code: item.product?.dms_code || item.product?.sku,
        brand_name: item.product?.brand?.name,
        cartons: item.cartons,
        pieces: item.pieces,
        total_pieces: item.total_pieces,
        exclusive_price: parseFloat(item.list_price_before_tax) || parseFloat(item.price) || 0,
        fed_percent: parseFloat(item.fed_percent) || 0,
        fed_amount: parseFloat(item.fed_amount) || 0,
        sales_tax_percent: parseFloat(item.tax_percent) || 0,
        sales_tax_amount: parseFloat(item.tax) || 0,
        extra_tax_percent: parseFloat(item.extra_tax_percent) || 0,
        extra_tax_amount: parseFloat(item.extra_tax_amount) || 0,
        adv_tax_percent: parseFloat(item.adv_tax_percent) || 0,
        adv_tax_amount: parseFloat(item.adv_tax_amount) || 0,
        gross_amount: parseFloat(item.gross_amount) || parseFloat(item.line_total) || 0,
        net_unit_price: parseFloat(item.price) || 0,
        scheme_id: item.scheme_id,
        scheme_name: item.scheme?.product?.name || item.scheme?.brand?.name || null,
        scheme_discount: parseFloat(item.scheme_discount) || 0,
        discount: parseFloat(item.discount) || 0,
        total_discount: parseFloat(item.discount) || 0,
        is_free: item.is_free || false
    }))
});

// Product options
const productOptions = computed(() => {
    return props.products.map(p => ({
        ...p,
        displayLabel: `${p.dms_code || p.sku || ''} - ${p.name}`
    }));
});

// Watch product selection
watch(() => newItem.value.product_id, (productId) => {
    if (productId) {
        const product = props.products.find(p => p.id === parseInt(productId));
        if (product) {
            selectedProduct.value = product;
            newItem.value.exclusive_price = parseFloat(product.list_price_before_tax) || 0;
            newItem.value.fed_percent = parseFloat(product.fed_percent) || 0;
            newItem.value.sales_tax_percent = parseFloat(product.sales_tax_percent) || 18;
            newItem.value.extra_tax_percent = parseFloat(product.product_type?.extra_tax) || 0;
            newItem.value.adv_tax_percent = parseFloat(props.invoice.customer?.adv_tax_percent) || 0;
            
            productSchemes.value = props.schemes.filter(s =>
                s.product_id === product.id || s.brand_id === product.brand_id
            );
            
            calculateNetUnitPrice();
        }
    }
});

// Watch cartons/pieces
watch([() => newItem.value.cartons, () => newItem.value.pieces], () => {
    const packing = selectedProduct.value?.pieces_per_packing || selectedProduct.value?.packing?.quantity || 12;
    newItem.value.total_pieces = (newItem.value.cartons * packing) + newItem.value.pieces;
    calculateSchemeDiscount();
});

// Watch scheme selection
watch(() => newItem.value.scheme_id, () => calculateSchemeDiscount());

// Calculate net unit price
const calculateNetUnitPrice = () => {
    const exclusive = parseFloat(newItem.value.exclusive_price) || 0;
    const fed = parseFloat(newItem.value.fed_percent) / 100 || 0;
    const salesTax = parseFloat(newItem.value.sales_tax_percent) / 100 || 0;
    const extraTax = parseFloat(newItem.value.extra_tax_percent) / 100 || 0;
    const advTax = parseFloat(newItem.value.adv_tax_percent) / 100 || 0;

    let netPrice = exclusive * (1 + fed);
    netPrice = netPrice * (1 + salesTax);
    const extraTaxAmount = exclusive * extraTax;
    netPrice = netPrice + extraTaxAmount;
    netPrice = netPrice * (1 + advTax);

    newItem.value.net_unit_price = Math.round(netPrice * 100) / 100;
};

// Calculate scheme discount
const calculateSchemeDiscount = () => {
    if (newItem.value.scheme_id) {
        const scheme = productSchemes.value.find(s => s.id === parseInt(newItem.value.scheme_id));
        if (scheme) {
            const baseAmount = newItem.value.total_pieces * newItem.value.net_unit_price;
            newItem.value.scheme_discount = scheme.discount_type === 'percentage'
                ? baseAmount * (scheme.discount_value / 100)
                : scheme.discount_value * newItem.value.total_pieces;
        }
    } else {
        newItem.value.scheme_discount = 0;
    }
};

// Search product by code
const searchProductByCode = () => {
    if (!productCode.value) return;
    const found = props.products.find(p => 
        (p.dms_code && p.dms_code.toLowerCase() === productCode.value.toLowerCase()) ||
        (p.sku && p.sku.toLowerCase() === productCode.value.toLowerCase())
    );
    if (found) {
        newItem.value.product_id = found.id;
        productCode.value = '';
    }
};

// Reset new item
const resetNewItem = () => {
    newItem.value = {
        product_id: '',
        cartons: 0,
        pieces: 0,
        total_pieces: 0,
        exclusive_price: 0,
        fed_percent: 0,
        sales_tax_percent: 0,
        extra_tax_percent: 0,
        adv_tax_percent: 0,
        net_unit_price: 0,
        scheme_id: '',
        scheme_discount: 0,
        discount_scheme_id: '',
        free_product: null,
        free_pieces: 0,
        manual_discount_percent: 0,
        manual_discount_amount: 0
    };
    selectedProduct.value = null;
    productSchemes.value = [];
};

// Add item
const addItem = () => {
    if (!newItem.value.product_id || newItem.value.total_pieces <= 0) return;

    const product = props.products.find(p => p.id === parseInt(newItem.value.product_id));
    const scheme = productSchemes.value.find(s => s.id === parseInt(newItem.value.scheme_id));

    // Calculate all amounts
    const exclusiveAmount = newItem.value.exclusive_price * newItem.value.total_pieces;
    const fedAmount = exclusiveAmount * (newItem.value.fed_percent / 100);
    const salesTaxAmount = (exclusiveAmount + fedAmount) * (newItem.value.sales_tax_percent / 100);
    const extraTaxAmount = exclusiveAmount * ((newItem.value.extra_tax_percent || 0) / 100);
    const advTaxAmount = (exclusiveAmount + fedAmount + salesTaxAmount + extraTaxAmount) * (newItem.value.adv_tax_percent / 100);
    const grossAmount = newItem.value.total_pieces * newItem.value.net_unit_price;

    const totalManualDiscount = (grossAmount * (newItem.value.manual_discount_percent / 100)) + newItem.value.manual_discount_amount;

    form.items.push({
        id: null,
        product_id: newItem.value.product_id,
        product_name: product?.name,
        product_code: product?.dms_code || product?.sku,
        brand_name: product?.brand?.name,
        cartons: newItem.value.cartons,
        pieces: newItem.value.pieces,
        total_pieces: newItem.value.total_pieces,
        exclusive_price: newItem.value.exclusive_price,
        fed_percent: newItem.value.fed_percent,
        fed_amount: fedAmount,
        sales_tax_percent: newItem.value.sales_tax_percent,
        sales_tax_amount: salesTaxAmount,
        extra_tax_percent: newItem.value.extra_tax_percent,
        extra_tax_amount: extraTaxAmount,
        adv_tax_percent: newItem.value.adv_tax_percent,
        adv_tax_amount: advTaxAmount,
        gross_amount: grossAmount,
        net_unit_price: newItem.value.net_unit_price,
        scheme_id: newItem.value.scheme_id || null,
        scheme_name: scheme?.product?.name || scheme?.brand?.name || null,
        scheme_discount: newItem.value.scheme_discount,
        total_discount: newItem.value.scheme_discount + totalManualDiscount
    });

    resetNewItem();
};

// Remove item
const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Format amount
const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount || 0);
};

// Calculate item values for display
const getItemExclusive = (item) => item.exclusive_price * item.total_pieces;
const getItemFed = (item) => item.fed_amount || (getItemExclusive(item) * (item.fed_percent / 100));
const getItemSalesTax = (item) => item.sales_tax_amount || (getItemExclusive(item) * (1 + item.fed_percent / 100) * (item.sales_tax_percent / 100));
const getItemExtraTax = (item) => item.extra_tax_amount || (getItemExclusive(item) * ((item.extra_tax_percent || 0) / 100));
const getItemAdvTax = (item) => item.adv_tax_amount || 0;
const getItemDiscount = (item) => (item.scheme_discount || 0) + (item.total_discount || 0) - (item.scheme_discount || 0);

// Totals
const totalExclusive = computed(() => form.items.reduce((sum, item) => sum + getItemExclusive(item), 0));
const totalFed = computed(() => form.items.reduce((sum, item) => sum + getItemFed(item), 0));
const totalSalesTax = computed(() => form.items.reduce((sum, item) => sum + getItemSalesTax(item), 0));
const totalExtraTax = computed(() => form.items.reduce((sum, item) => sum + getItemExtraTax(item), 0));
const totalAdvTax = computed(() => form.items.reduce((sum, item) => sum + getItemAdvTax(item), 0));
const totalGrossAmount = computed(() => form.items.reduce((sum, item) => sum + (item.gross_amount || (item.total_pieces * item.net_unit_price)), 0));
const totalDiscount = computed(() => form.items.reduce((sum, item) => sum + (item.total_discount || item.scheme_discount || 0), 0));
const grandTotal = computed(() => totalGrossAmount.value - totalDiscount.value);

// F2 save
const handleKeydown = (e) => {
    if (e.key === 'F2') { e.preventDefault(); submit(); }
};
onMounted(() => document.addEventListener('keydown', handleKeydown));
onUnmounted(() => document.removeEventListener('keydown', handleKeydown));

const submit = () => {
    form.put(route('invoices.update', props.invoice.id));
};
</script>

<template>
    <Head :title="`Edit Invoice: ${invoice.invoice_number}`" />

    <DashboardLayout>
        <div class="space-y-6 max-w-7xl">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Invoice: {{ invoice.invoice_number }}</h1>
                    <p class="text-gray-500 mt-1">Modify invoice details. Press F2 to save.</p>
                </div>
                <Link :href="route('invoices.show', invoice.id)" class="text-gray-500 hover:text-gray-700">← Back</Link>
            </div>

            <!-- Customer Info Card (Read-only) -->
            <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                    <div><span class="text-gray-500">Customer:</span> <span class="font-medium">{{ invoice.customer?.shop_name }}</span></div>
                    <div><span class="text-gray-500">Code:</span> <span class="font-medium">{{ invoice.customer?.customer_code }}</span></div>
                    <div><span class="text-gray-500">VAN:</span> <span class="font-medium">{{ invoice.van?.code }}</span></div>
                    <div><span class="text-gray-500">Order Booker:</span> <span class="font-medium">{{ invoice.order_booker?.name }}</span></div>
                    <div><span class="text-gray-500">Date:</span> <span class="font-medium">{{ invoice.invoice_date }}</span></div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mt-2">
                    <div><span class="text-gray-500">S.Tax Status:</span> 
                        <span :class="invoice.customer?.sales_tax_status === 'active' ? 'text-green-600 font-bold' : 'text-red-600 font-bold'">
                            {{ invoice.customer?.sales_tax_status?.toUpperCase() || 'INACTIVE' }}
                        </span>
                    </div>
                    <div><span class="text-gray-500">Adv Tax %:</span> <span class="font-medium">{{ invoice.customer?.adv_tax_percent || 0 }}%</span></div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Invoice Options -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <InputLabel value="Invoice Type" />
                            <select v-model="form.invoice_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="sale">Sale</option>
                                <option value="damage">Damage (Zero Value)</option>
                                <option value="shelf_rent">Shelf Rent</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input type="checkbox" v-model="form.is_credit" id="is_credit"
                                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="is_credit" class="text-sm text-gray-700">Credit Sale</label>
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel value="Notes" />
                            <textarea v-model="form.notes" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Product Entry -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Add Products</h2>

                    <!-- Row 1: Product selection -->
                    <div class="grid grid-cols-6 lg:grid-cols-12 gap-3 items-end mb-4">
                        <!-- Product Code -->
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Product Code" />
                            <TextInput v-model="productCode" placeholder="Enter code" class="mt-1 w-full"
                                @keyup.enter="searchProductByCode" />
                        </div>

                        <!-- Product Select -->
                        <div class="col-span-4 lg:col-span-4">
                            <SearchableSelect v-model="newItem.product_id" label="Product" :options="productOptions"
                                option-value="id" option-label="displayLabel" placeholder="Search product..." />
                        </div>

                        <!-- Cartons -->
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Cartons" />
                            <TextInput v-model.number="newItem.cartons" type="number" min="0" class="mt-1 w-full text-center font-medium" />
                        </div>

                        <!-- Pieces -->
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Pieces" />
                            <TextInput v-model.number="newItem.pieces" type="number" min="0" class="mt-1 w-full text-center font-medium" />
                        </div>

                        <!-- Total Pieces (readonly) -->
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Total Pcs" />
                            <TextInput :value="newItem.total_pieces" type="number" class="mt-1 w-full bg-emerald-50 text-emerald-700 text-center font-bold" readonly />
                        </div>
                    </div>

                    <!-- Row 2: Pricing breakdown (visible when product selected) -->
                    <div v-if="newItem.product_id" class="bg-gray-50 p-4 rounded-lg mb-4">
                        <div class="grid grid-cols-6 lg:grid-cols-10 gap-3 items-end">
                            <!-- Exclusive Price -->
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Excl. Price" class="text-xs" />
                                <TextInput v-model.number="newItem.exclusive_price" type="number" step="0.01"
                                    class="mt-1 w-full text-sm text-center" @input="calculateNetUnitPrice" />
                            </div>

                            <!-- FED % -->
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="FED %" class="text-xs" />
                                <TextInput v-model.number="newItem.fed_percent" type="number" step="0.01"
                                    class="mt-1 w-full text-sm text-center" @input="calculateNetUnitPrice" />
                            </div>

                            <!-- Sales Tax % -->
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="S.Tax %" class="text-xs" />
                                <TextInput v-model.number="newItem.sales_tax_percent" type="number" step="0.01"
                                    class="mt-1 w-full text-sm text-center" @input="calculateNetUnitPrice" />
                            </div>

                            <!-- Extra Tax % (from Product Type) -->
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Extra Tax %" class="text-xs" />
                                <TextInput v-model.number="newItem.extra_tax_percent" type="number" step="0.01"
                                    class="mt-1 w-full text-sm text-center" @input="calculateNetUnitPrice" />
                            </div>

                            <!-- Advance Tax % -->
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Adv.Tax %" class="text-xs" />
                                <TextInput v-model.number="newItem.adv_tax_percent" type="number" step="0.01"
                                    class="mt-1 w-full text-sm text-center" @input="calculateNetUnitPrice" />
                            </div>

                            <!-- Net Unit Price -->
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Net Price" class="text-xs font-bold text-indigo-600" />
                                <TextInput :value="newItem.net_unit_price.toFixed(2)" type="text"
                                    class="mt-1 w-full bg-indigo-50 font-bold text-indigo-700 text-sm text-center" readonly />
                            </div>

                            <!-- Scheme Dropdown -->
                            <div class="col-span-3 lg:col-span-2">
                                <InputLabel value="Scheme" class="text-xs" />
                                <select v-model="newItem.scheme_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm h-[42px]"
                                    :class="newItem.scheme_discount > 0 ? 'bg-orange-50 border-orange-300' : ''">
                                    <option value="">No Scheme</option>
                                    <option v-for="s in productSchemes" :key="s.id" :value="s.id">
                                        {{ s.discount_value }}{{ s.discount_type === 'percentage' ? '%' : ' Rs' }}
                                    </option>
                                </select>
                            </div>

                            <!-- Manual Discount % -->
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Disc. %" class="text-xs" />
                                <TextInput v-model.number="newItem.manual_discount_percent" type="number" step="0.01"
                                    min="0" max="100" class="mt-1 w-full text-sm text-center" placeholder="0" />
                            </div>

                            <!-- Manual Discount Amount -->
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Disc. Rs" class="text-xs" />
                                <TextInput v-model.number="newItem.manual_discount_amount" type="number" step="0.01" min="0"
                                    class="mt-1 w-full text-sm text-center" placeholder="0" />
                            </div>

                            <!-- Add Button -->
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value=" " class="text-xs invisible" />
                                <button type="button" @click="addItem"
                                    :disabled="!newItem.product_id || newItem.total_pieces <= 0"
                                    class="w-full mt-1 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">
                                    + Add
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Scheme Discount Banner -->
                    <div v-if="newItem.product_id && newItem.scheme_discount > 0" 
                        class="mb-4 p-3 rounded-lg border-2 bg-orange-50 border-orange-300">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">💰</span>
                            <div>
                                <p class="font-bold text-orange-700">Scheme Discount Applied</p>
                                <p class="text-sm text-orange-600">Discount: Rs {{ formatAmount(newItem.scheme_discount) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Grid -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b bg-gray-50">
                        <h2 class="text-lg font-medium">Items ({{ form.items.length }})</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 text-[10px] uppercase font-semibold text-gray-500">
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
                                    <th class="px-2 py-3 text-right">Discount</th>
                                    <th class="px-2 py-3 text-right">Net</th>
                                    <th class="px-2 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, index) in form.items" :key="index" :class="item.is_free ? 'bg-green-50' : ''">
                                    <td class="px-2 py-3">{{ index + 1 }}</td>
                                    <td class="px-2 py-3">
                                        <div class="font-medium">{{ item.product_name }}</div>
                                        <div class="text-xs text-gray-500">{{ item.product_code }}</div>
                                    </td>
                                    <td class="px-2 py-3 text-right">{{ item.cartons }}</td>
                                    <td class="px-2 py-3 text-right">{{ item.pieces }}</td>
                                    <td class="px-2 py-3 text-right font-medium">{{ item.total_pieces }}</td>
                                    <td class="px-2 py-3 text-right">{{ formatAmount(item.exclusive_price) }}</td>
                                    <td class="px-2 py-3 text-right text-gray-600">{{ formatAmount(getItemExclusive(item)) }}</td>
                                    <td class="px-2 py-3 text-right text-gray-500">
                                        {{ formatAmount(getItemFed(item)) }}
                                        <div class="text-[9px]">({{ item.fed_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-gray-500">
                                        {{ formatAmount(getItemSalesTax(item)) }}
                                        <div class="text-[9px]">({{ item.sales_tax_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-purple-600">
                                        {{ formatAmount(getItemExtraTax(item)) }}
                                        <div class="text-[9px]">({{ item.extra_tax_percent || 0 }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-gray-500">
                                        {{ formatAmount(getItemAdvTax(item)) }}
                                        <div class="text-[9px]">({{ item.adv_tax_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right font-medium">{{ formatAmount(item.gross_amount || (item.total_pieces * item.net_unit_price)) }}</td>
                                    <td class="px-2 py-3 text-right text-red-600">-{{ formatAmount(item.total_discount || item.scheme_discount) }}</td>
                                    <td class="px-2 py-3 text-right font-semibold text-emerald-600">
                                        {{ formatAmount((item.gross_amount || (item.total_pieces * item.net_unit_price)) - (item.total_discount || item.scheme_discount || 0)) }}
                                    </td>
                                    <td class="px-2 py-3">
                                        <button type="button" @click="removeItem(index)"
                                            class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="form.items.length === 0">
                                    <td colspan="15" class="px-4 py-8 text-center text-gray-500">
                                        No items. Use the form above to add products.
                                    </td>
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
                        <div class="grid grid-cols-3 gap-4 mt-4 text-sm">
                            <div class="bg-white p-3 rounded-lg border">
                                <div class="text-gray-500 text-xs uppercase">Gross Amount</div>
                                <div class="font-bold text-lg">{{ formatAmount(totalGrossAmount) }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-red-200">
                                <div class="text-red-500 text-xs uppercase">Total Discount</div>
                                <div class="font-bold text-lg text-red-600">-{{ formatAmount(totalDiscount) }}</div>
                            </div>
                            <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-300">
                                <div class="text-emerald-600 text-xs uppercase font-bold">Grand Total</div>
                                <div class="font-bold text-2xl text-emerald-700">Rs. {{ formatAmount(grandTotal) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('invoices.show', invoice.id)">
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton :disabled="form.processing || form.items.length === 0"
                        class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0">
                        {{ form.processing ? 'Saving...' : 'Update Invoice (F2)' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
