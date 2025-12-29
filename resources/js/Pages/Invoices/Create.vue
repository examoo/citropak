<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    vans: Array,
    orderBookers: Array,
    products: Array,
    schemes: Array,
    distributions: Array,
    nextOrderDate: String,
    availableStocks: { type: Array, default: () => [] }
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

// Form state
const form = useForm({
    van_id: '',
    order_booker_id: '',
    customer_id: '',
    invoice_type: 'sale',
    tax_type: 'food',
    invoice_date: props.nextOrderDate || new Date().toISOString().split('T')[0],
    is_credit: false,
    notes: '',
    distribution_id: currentDistribution.value?.id || '',
    items: []
});

// Cascading dropdown state
const filteredBookers = ref([]);
const filteredCustomers = ref([]);
const selectedCustomer = ref(null);
const customerCode = ref('');
const orderDay = ref('');

// Product entry state
const productCode = ref('');
const selectedProduct = ref(null);
const newItem = ref({
    product_id: '',
    stock_id: '',            // Selected batch/stock
    cartons: 0,
    pieces: 0,
    total_pieces: 0,
    exclusive_price: 0,      // Price before tax (list_price_before_tax)
    fed_percent: 0,          // FED %
    sales_tax_percent: 0,    // Sales Tax %
    extra_tax_percent: 0,    // Extra Tax % (from product type)
    adv_tax_percent: 0,      // Advance Tax % (from customer)
    net_unit_price: 0,       // Price with taxes
    scheme_id: '',
    scheme_discount: 0,
    discount_scheme_id: '',  // DiscountScheme (quantity-based)
    free_product: null,      // Free product from scheme
    free_pieces: 0,          // Free pieces quantity
    manual_discount_percent: 0,  // Manual discount %
    manual_discount_amount: 0,   // Manual discount amount (Rs)
    batch_number: '',        // Selected batch number
    available_qty: 0         // Available quantity in selected batch
});
const productSchemes = ref([]);
const discountSchemes = ref([]);

// Day options
const dayOptions = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Van options with distribution label
const vanOptions = computed(() => {
    return props.vans.map(van => ({
        ...van,
        displayLabel: !currentDistribution.value?.id && van.distribution
            ? `${van.code} (${van.distribution.name})`
            : van.code
    }));
});

// Product options
const productOptions = computed(() => {
    return props.products.map(p => ({
        ...p,
        displayLabel: `${p.dms_code || p.sku || ''} - ${p.name}`
    }));
});

// Get available batches/stocks for the selected product
const availableBatches = computed(() => {
    if (!newItem.value.product_id) return [];

    let stocks = props.availableStocks.filter(s => Number(s.product_id) === Number(newItem.value.product_id));

    // Filter by selected distribution
    const distId = form.distribution_id || currentDistribution.value?.id;
    if (distId) {
        stocks = stocks.filter(s => Number(s.distribution_id) === Number(distId));
    }

    return stocks.map(s => ({
        ...s,
        label: `${s.batch_number ? 'Batch: ' + s.batch_number : 'No Batch'} ${s.location ? '(' + s.location + ')' : ''} - Qty: ${s.quantity}`,
    }));
});

// Watch VAN change -> Auto-select Order Booker + Load Customers
watch(() => form.van_id, async (vanId) => {
    form.customer_id = '';
    selectedCustomer.value = null;
    filteredCustomers.value = [];

    if (vanId) {
        try {
            // Get order bookers for van and auto-select if only one
            const bookersResponse = await axios.get(route('api.bookers-by-van', vanId));
            filteredBookers.value = bookersResponse.data;

            // Auto-select order booker if only one exists
            if (bookersResponse.data.length === 1) {
                form.order_booker_id = bookersResponse.data[0].id;
            } else {
                form.order_booker_id = '';
            }

            // Load customers by van code
            const van = props.vans.find(v => v.id === parseInt(vanId));
            if (van) {
                const customersResponse = await axios.get(route('api.customers-by-van', van.code), {
                    params: { day: orderDay.value || undefined }
                });
                filteredCustomers.value = customersResponse.data;
            }
        } catch (e) {
            filteredBookers.value = [];
            filteredCustomers.value = [];
        }
    } else {
        filteredBookers.value = [];
        filteredCustomers.value = [];
    }
});

// Watch Day change -> Reload Customers for current Van
watch(orderDay, async (day) => {
    form.customer_id = '';
    selectedCustomer.value = null;

    if (form.van_id) {
        try {
            const van = props.vans.find(v => v.id === parseInt(form.van_id));
            if (van) {
                const response = await axios.get(route('api.customers-by-van', van.code), {
                    params: { day: day || undefined }
                });
                filteredCustomers.value = response.data;
            }
        } catch (e) {
            filteredCustomers.value = [];
        }
    }
});

// Watch customer selection
watch(() => form.customer_id, (customerId) => {
    if (customerId) {
        selectedCustomer.value = filteredCustomers.value.find(c => c.id === parseInt(customerId));
    } else {
        selectedCustomer.value = null;
    }
});

// Search customer by code
const searchCustomerByCode = async () => {
    if (!customerCode.value) return;
    try {
        const response = await axios.get(route('api.customer-by-code', customerCode.value));
        selectedCustomer.value = response.data;
        form.customer_id = response.data.id;
    } catch (e) {
        selectedCustomer.value = null;
    }
};

// Helper to calculate net unit price with taxes (matches Product calculation)
// Formula: Base Price * (1 + FED%) * (1 + Sales Tax%) * (1 + Advance Tax%) + Extra Tax
const calculateNetUnitPrice = () => {
    const exclusive = parseFloat(newItem.value.exclusive_price) || 0;
    const fed = parseFloat(newItem.value.fed_percent) / 100 || 0;
    const salesTax = parseFloat(newItem.value.sales_tax_percent) / 100 || 0;
    const extraTax = parseFloat(newItem.value.extra_tax_percent) / 100 || 0;
    const advTax = parseFloat(newItem.value.adv_tax_percent) / 100 || 0;

    // Compound calculation:
    // Step 1: Base Price + FED %
    let netPrice = exclusive * (1 + fed);
    // Step 2: + Sales Tax %
    netPrice = netPrice * (1 + salesTax);
    // Step 3: + Extra Tax % (based on exclusive amount)
    const extraTaxAmount = exclusive * extraTax;
    netPrice = netPrice + extraTaxAmount;
    // Step 4: + Advance Tax (customer specific)
    netPrice = netPrice * (1 + advTax);

    newItem.value.net_unit_price = netPrice;
};

// Search product by code
const searchProductByCode = async () => {
    if (!productCode.value) return;
    try {
        const response = await axios.get(route('api.product-by-code', productCode.value));
        const product = response.data;
        selectedProduct.value = product;
        newItem.value.product_id = product.id;
        // Use list_price_before_tax as exclusive price (before taxes)
        newItem.value.exclusive_price = parseFloat(product.list_price_before_tax) || 0;
        newItem.value.fed_percent = parseFloat(product.fed_percent) || 0;
        newItem.value.sales_tax_percent = parseFloat(product.fed_sales_tax) || 0;
        // Get advance tax from selected customer
        newItem.value.adv_tax_percent = parseFloat(selectedCustomer.value?.adv_tax_percent) || 0;
        // Use product's unit_price if available (already calculated with taxes)
        if (product.unit_price && parseFloat(product.unit_price) > 0) {
            newItem.value.net_unit_price = parseFloat(product.unit_price);
        } else {
            calculateNetUnitPrice();
        }
        loadProductSchemes(product.id);
    } catch (e) {
        selectedProduct.value = null;
    }
};

// Watch product selection
watch(() => newItem.value.product_id, (productId, oldProductId) => {
    // Reset stock selection when product changes
    if (productId !== oldProductId) {
        newItem.value.stock_id = '';
        newItem.value.batch_number = '';
        newItem.value.available_qty = 0;
    }
    
    if (productId) {
        const product = props.products.find(p => p.id === parseInt(productId));
        if (product) {
            selectedProduct.value = product;
            // Use list_price_before_tax as exclusive price (before taxes)
            newItem.value.exclusive_price = parseFloat(product.list_price_before_tax) || parseFloat(product.unit_price) || 0;
            newItem.value.fed_percent = parseFloat(product.fed_percent) || 0;
            newItem.value.sales_tax_percent = parseFloat(product.fed_sales_tax) || 0;
            // Get extra tax from product type
            console.log('Product selected:', product.id, 'product_type:', product.product_type, 'extra_tax:', product.product_type?.extra_tax);
            newItem.value.extra_tax_percent = parseFloat(product.product_type?.extra_tax) || 0;
            // Get advance tax from selected customer
            newItem.value.adv_tax_percent = parseFloat(selectedCustomer.value?.adv_tax_percent) || 0;
            
            // Always calculate net unit price
            calculateNetUnitPrice();
            
            // If product has pre-calculated unit_price and exclusive is 0, use it directly
            if (newItem.value.exclusive_price === 0 && product.unit_price && parseFloat(product.unit_price) > 0) {
                newItem.value.net_unit_price = parseFloat(product.unit_price);
                // Reverse-calculate exclusive price from unit price (approximate)
                newItem.value.exclusive_price = parseFloat(product.unit_price);
            }
            
            loadProductSchemes(productId);
            productCode.value = product.dms_code || product.sku || '';
        }
    }
});

// Watch stock/batch selection to auto-fill batch details
watch(() => newItem.value.stock_id, (stockId) => {
    if (stockId) {
        const stock = props.availableStocks.find(s => Number(s.id) === Number(stockId));
        if (stock) {
            newItem.value.batch_number = stock.batch_number || '';
            newItem.value.available_qty = stock.quantity || 0;
        }
    } else {
        newItem.value.batch_number = '';
        newItem.value.available_qty = 0;
    }
});

// Watch customer selection to update advance tax
watch(() => selectedCustomer.value, (customer) => {
    if (customer && newItem.value.product_id) {
        newItem.value.adv_tax_percent = customer.adv_tax_percent || 0;
        calculateNetUnitPrice();
    }
});

// Load schemes for product
const loadProductSchemes = async (productId) => {
    try {
        const response = await axios.get(route('api.schemes-for-product', productId));
        productSchemes.value = response.data;
    } catch (e) {
        productSchemes.value = [];
    }
};

// Load discount schemes for product based on quantity
const loadDiscountSchemes = async (productId, quantity) => {
    try {
        const response = await axios.get(route('api.discount-schemes', productId), {
            params: { quantity }
        });
        discountSchemes.value = response.data;
        // Auto-apply if only one scheme matches
        if (discountSchemes.value.length === 1) {
            applyDiscountScheme(discountSchemes.value[0]);
        }
    } catch (e) {
        discountSchemes.value = [];
    }
};

// Apply a discount scheme (amount_less or free_product)
const applyDiscountScheme = (scheme) => {
    newItem.value.discount_scheme_id = scheme.id;
    
    if (scheme.discount_type === 'amount_less' && scheme.amount_less > 0) {
        // Amount-based discount
        newItem.value.scheme_discount = parseFloat(scheme.amount_less);
        newItem.value.free_product = null;
        newItem.value.free_pieces = 0;
    } else if (scheme.discount_type === 'free_product' && scheme.free_product) {
        // Free product scheme
        newItem.value.scheme_discount = 0;
        newItem.value.free_product = scheme.free_product;
        newItem.value.free_pieces = scheme.free_pieces || 0;
    }
};

// Watch cartons/pieces -> Calculate total and load discount schemes
watch([() => newItem.value.cartons, () => newItem.value.pieces], () => {
    // Use pieces_per_packing from new simplified structure
    const packing = selectedProduct.value?.pieces_per_packing || selectedProduct.value?.packing?.quantity || 12;
    newItem.value.total_pieces = (newItem.value.cartons * packing) + newItem.value.pieces;
    
    // Load discount schemes based on quantity
    if (newItem.value.product_id && newItem.value.total_pieces > 0) {
        loadDiscountSchemes(newItem.value.product_id, newItem.value.total_pieces);
    }
});

// Watch scheme selection -> Apply discount
watch(() => newItem.value.scheme_id, (schemeId) => {
    if (schemeId) {
        const scheme = productSchemes.value.find(s => s.id === parseInt(schemeId));
        if (scheme) {
            const baseAmount = newItem.value.total_pieces * newItem.value.net_unit_price;
            if (scheme.discount_type === 'percentage') {
                newItem.value.scheme_discount = baseAmount * (scheme.discount_value / 100);
            } else {
                newItem.value.scheme_discount = scheme.discount_value * newItem.value.total_pieces;
            }
        }
    } else {
        newItem.value.scheme_discount = 0;
    }
});

// Add item to grid
const addItem = () => {
    if (!newItem.value.product_id || newItem.value.total_pieces <= 0) return;

    const product = props.products.find(p => p.id === parseInt(newItem.value.product_id));
    const scheme = productSchemes.value.find(s => s.id === parseInt(newItem.value.scheme_id));

    // Calculate total discount (scheme + manual)
    const grossAmount = newItem.value.total_pieces * newItem.value.net_unit_price;
    const manualDiscountFromPercent = grossAmount * (newItem.value.manual_discount_percent / 100);
    const totalManualDiscount = manualDiscountFromPercent + newItem.value.manual_discount_amount;

    // Calculate all amounts for storage
    const exclusiveAmount = newItem.value.exclusive_price * newItem.value.total_pieces;
    const fedAmount = exclusiveAmount * (newItem.value.fed_percent / 100);
    const salesTaxAmount = (exclusiveAmount + fedAmount) * (newItem.value.sales_tax_percent / 100);
    const extraTaxAmount = exclusiveAmount * ((newItem.value.extra_tax_percent || 0) / 100);
    const advTaxAmount = (exclusiveAmount + fedAmount + salesTaxAmount + extraTaxAmount) * (newItem.value.adv_tax_percent / 100);

    form.items.push({
        product_id: newItem.value.product_id,
        product_name: product?.name,
        product_code: product?.dms_code || product?.sku,
        brand_name: product?.brand?.name,
        stock_id: newItem.value.stock_id || null,
        batch_number: newItem.value.batch_number || null,
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
        price: newItem.value.net_unit_price, // For backward compatibility
        scheme_id: newItem.value.scheme_id || null,
        scheme_name: scheme?.product?.name || scheme?.brand?.name || null,
        scheme_discount: newItem.value.scheme_discount,
        discount_scheme_id: newItem.value.discount_scheme_id || null,
        free_product: newItem.value.free_product,
        free_pieces: newItem.value.free_pieces,
        manual_discount_percent: newItem.value.manual_discount_percent,
        manual_discount_amount: newItem.value.manual_discount_amount,
        total_discount: newItem.value.scheme_discount + totalManualDiscount
    });

    // If there's a free product, add it as a separate item with 0 price
    if (newItem.value.free_product && newItem.value.free_pieces > 0) {
        form.items.push({
            product_id: newItem.value.free_product.id,
            product_name: newItem.value.free_product.name + ' (FREE)',
            product_code: newItem.value.free_product.dms_code,
            brand_name: '',
            cartons: 0,
            pieces: newItem.value.free_pieces,
            total_pieces: newItem.value.free_pieces,
            exclusive_price: 0,
            fed_percent: 0,
            sales_tax_percent: 0,
            extra_tax_percent: 0,
            adv_tax_percent: 0,
            net_unit_price: 0,
            price: 0,
            scheme_id: null,
            scheme_name: 'FREE',
            scheme_discount: 0,
            discount_scheme_id: newItem.value.discount_scheme_id,
            free_product: null,
            free_pieces: 0,
            manual_discount_percent: 0,
            manual_discount_amount: 0,
            total_discount: 0,
            is_free: true // Mark as free item
        });
    }

    // Reset
    resetNewItem();
};

const resetNewItem = () => {
    newItem.value = {
        product_id: '',
        stock_id: '',
        cartons: 0,
        pieces: 0,
        total_pieces: 0,
        exclusive_price: 0,
        fed_percent: 0,
        sales_tax_percent: 0,
        extra_tax_percent: 0,
        adv_tax_percent: selectedCustomer.value?.adv_tax_percent || 0,
        net_unit_price: 0,
        scheme_id: '',
        scheme_discount: 0,
        discount_scheme_id: '',
        free_product: null,
        free_pieces: 0,
        manual_discount_percent: 0,
        manual_discount_amount: 0,
        batch_number: '',
        available_qty: 0
    };
    selectedProduct.value = null;
    productCode.value = '';
    productSchemes.value = [];
    discountSchemes.value = [];
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Calculate item discount (scheme + manual)
const getItemDiscount = (item) => {
    const grossAmount = item.total_pieces * item.net_unit_price;
    const manualDiscountFromPercent = grossAmount * ((item.manual_discount_percent || 0) / 100);
    return (item.scheme_discount || 0) + manualDiscountFromPercent + (item.manual_discount_amount || 0);
};

// Calculate item exclusive amount
const getItemExclusive = (item) => {
    return item.exclusive_price * item.total_pieces;
};

// Calculate item FED amount
const getItemFed = (item) => {
    return getItemExclusive(item) * (item.fed_percent / 100);
};

// Calculate item Sales Tax amount
const getItemSalesTax = (item) => {
    return getItemExclusive(item) * (1 + item.fed_percent / 100) * (item.sales_tax_percent / 100);
};

// Calculate item Extra Tax amount (based on exclusive)
const getItemExtraTax = (item) => {
    return getItemExclusive(item) * ((item.extra_tax_percent || 0) / 100);
};

// Calculate item Advance Tax amount
const getItemAdvTax = (item) => {
    return getItemExclusive(item) * (1 + item.fed_percent / 100) * (1 + item.sales_tax_percent / 100) * (item.adv_tax_percent / 100);
};

// Invoice summary totals
const totalExclusive = computed(() => {
    return form.items.reduce((sum, item) => sum + getItemExclusive(item), 0);
});

const totalFed = computed(() => {
    return form.items.reduce((sum, item) => sum + getItemFed(item), 0);
});

const totalSalesTax = computed(() => {
    return form.items.reduce((sum, item) => sum + getItemSalesTax(item), 0);
});

const totalExtraTax = computed(() => {
    return form.items.reduce((sum, item) => sum + getItemExtraTax(item), 0);
});

const totalAdvTax = computed(() => {
    return form.items.reduce((sum, item) => sum + getItemAdvTax(item), 0);
});

const totalGrossAmount = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.total_pieces * item.net_unit_price), 0);
});

const totalDiscount = computed(() => {
    return form.items.reduce((sum, item) => sum + getItemDiscount(item), 0);
});

// Calculate totals
const grandTotal = computed(() => {
    return totalGrossAmount.value - totalDiscount.value;
});

// Format amount
const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0);
};

// F2 Save shortcut
const handleKeydown = (e) => {
    if (e.key === 'F2') {
        e.preventDefault();
        submit();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    // Debug: Log products to check if product_type is included
    console.log('Products loaded:', props.products?.length, 'First product product_type:', props.products?.[0]?.product_type);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});

// Submit form
const submit = () => {
    if (form.items.length === 0) return;
    form.post(route('invoices.store'));
};
</script>

<template>

    <Head title="Create Invoice" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create Invoice</h1>
                    <p class="text-gray-500 mt-1">Create a new sales invoice. Press F2 to save.</p>
                </div>
                <Link :href="route('invoices.index')" class="text-gray-500 hover:text-gray-700">
                    ← Back to list
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Invoice Header -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Invoice Details</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Distribution -->
                        <div v-if="!currentDistribution?.id">
                            <SearchableSelect v-model="form.distribution_id" label="Distribution"
                                :options="distributions" option-value="id" option-label="name"
                                placeholder="Select distribution" required />
                        </div>

                        <!-- VAN -->
                        <div>
                            <SearchableSelect v-model="form.van_id" label="VAN" :options="vanOptions" option-value="id"
                                option-label="displayLabel" placeholder="Select VAN" :error="form.errors.van_id"
                                required />
                        </div>

                        <!-- Order Booker -->
                        <div>
                            <SearchableSelect v-model="form.order_booker_id" label="Order Booker"
                                :options="filteredBookers" option-value="id" option-label="name"
                                placeholder="Select booker" :error="form.errors.order_booker_id" required />
                        </div>

                        <!-- Invoice Date -->
                        <div>
                            <InputLabel value="Invoice Date" />
                            <TextInput v-model="form.invoice_date" type="date" class="mt-1 block w-full" required />
                        </div>

                        <!-- Invoice Type -->
                        <div>
                            <InputLabel value="Invoice Type" />
                            <select v-model="form.invoice_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="sale">Sale</option>
                                <option value="damage">Damage</option>
                                <option value="shelf_rent">Shelf Rent</option>
                            </select>
                        </div>

                        <!-- Credit -->
                        <div class="flex items-center gap-2 pt-6">
                            <input type="checkbox" v-model="form.is_credit" id="is_credit"
                                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="is_credit" class="text-sm text-gray-700">Credit Sale</label>
                        </div>
                    </div>
                </div>

                <!-- Customer Selection -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Customer</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Day Filter -->
                        <div>
                            <InputLabel value="Day" />
                            <select v-model="orderDay"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">All Days</option>
                                <option v-for="day in dayOptions" :key="day" :value="day">{{ day }}</option>
                            </select>
                        </div>

                        <!-- Customer Selection -->
                        <div>
                            <SearchableSelect v-model="form.customer_id" label="Customer" :options="filteredCustomers"
                                option-value="id" option-label="shop_name" placeholder="Select customer"
                                :error="form.errors.customer_id" required />
                        </div>

                        <!-- Customer Code Search -->
                        <div>
                            <InputLabel value="Or Enter Code" />
                            <div class="flex gap-2 mt-1">
                                <TextInput v-model="customerCode" placeholder="Customer code" class="flex-1"
                                    @keyup.enter="searchCustomerByCode" />
                                <button type="button" @click="searchCustomerByCode"
                                    class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Details Card -->
                    <div v-if="selectedCustomer" class="mt-4 p-4 bg-emerald-50 rounded-xl border border-emerald-200">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Code:</span>
                                <span class="font-medium ml-2">{{ selectedCustomer.customer_code }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Shop:</span>
                                <span class="font-medium ml-2">{{ selectedCustomer.shop_name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Address:</span>
                                <span class="font-medium ml-2">{{ selectedCustomer.address }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Phone:</span>
                                <span class="font-medium ml-2">{{ selectedCustomer.phone }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">NTN:</span>
                                <span class="font-medium ml-2">{{ selectedCustomer.ntn_number || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">CNIC:</span>
                                <span class="font-medium ml-2">{{ selectedCustomer.cnic || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Day:</span>
                                <span class="font-medium ml-2">{{ selectedCustomer.day }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">ATL:</span>
                                <span class="font-medium ml-2"
                                    :class="selectedCustomer.atl ? 'text-green-600' : 'text-red-600'">
                                    {{ selectedCustomer.atl ? 'Yes' : 'No' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500">S.Tax Status:</span>
                                <span class="font-medium ml-2"
                                    :class="selectedCustomer.sales_tax_status === 'active' ? 'text-blue-600' : 'text-red-600'">
                                    {{ selectedCustomer.sales_tax_status ? selectedCustomer.sales_tax_status.toUpperCase() : 'N/A' }}
                                </span>
                            </div>
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
                        <div class="col-span-4 lg:col-span-3">
                            <SearchableSelect v-model="newItem.product_id" label="Product" :options="productOptions"
                                option-value="id" option-label="displayLabel" placeholder="Search product..." />
                        </div>

                        <!-- Batch/Stock Select -->
                        <div class="col-span-3 lg:col-span-2">
                            <InputLabel value="Batch / Stock" />
                            <SearchableSelect v-model="newItem.stock_id" :options="availableBatches"
                                option-value="id" option-label="label" placeholder="Select Batch"
                                :disabled="!newItem.product_id" class="mt-1" />
                            <div v-if="newItem.product_id && availableBatches.length === 0"
                                class="text-xs text-amber-600 mt-1">
                                No stock available
                            </div>
                            <div v-if="newItem.stock_id && newItem.available_qty > 0"
                                class="text-xs text-emerald-600 mt-1 font-medium">
                                Available: {{ newItem.available_qty }} pcs
                            </div>
                        </div>

                        <!-- Cartons -->
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Cartons" />
                            <TextInput v-model.number="newItem.cartons" type="number" min="0" class="mt-1 w-full text-center font-medium" />
                        </div>

                        <!-- Pieces -->
                        <div class="col-span-2 lg:col-span-1">
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
                                    class="mt-1 w-full text-sm text-center" />
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

                            <!-- Discount Scheme Dropdown -->
                            <div class="col-span-3 lg:col-span-2">
                                <InputLabel value="Scheme" class="text-xs" />
                                <select v-model="newItem.discount_scheme_id"
                                    @change="discountSchemes.length > 0 && applyDiscountScheme(discountSchemes.find(s => s.id == newItem.discount_scheme_id))"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm h-[42px]"
                                    :class="newItem.free_product ? 'bg-green-50 border-green-300' : (newItem.scheme_discount > 0 ? 'bg-orange-50 border-orange-300' : '')">
                                    <option value="">No Scheme</option>
                                    <option v-for="scheme in discountSchemes" :key="scheme.id" :value="scheme.id">
                                        {{ scheme.name }} - {{ scheme.discount_type === 'amount_less' ? `Rs ${scheme.amount_less}` : `${scheme.free_pieces} FREE` }}
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

                    <!-- Discount Scheme Banner (when applicable) -->
                    <div v-if="newItem.product_id && discountSchemes.length > 0" 
                        class="mb-4 p-3 rounded-lg border-2"
                        :class="newItem.free_product ? 'bg-green-50 border-green-300' : 'bg-orange-50 border-orange-300'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span v-if="newItem.free_product" class="text-2xl">🎁</span>
                                <span v-else class="text-2xl">💰</span>
                                <div>
                                    <p class="font-bold" :class="newItem.free_product ? 'text-green-700' : 'text-orange-700'">
                                        {{ discountSchemes[0]?.name || 'Discount Scheme Applied' }}
                                    </p>
                                    <p class="text-sm" :class="newItem.free_product ? 'text-green-600' : 'text-orange-600'">
                                        <span v-if="newItem.scheme_discount > 0">
                                            Discount: Rs {{ formatAmount(newItem.scheme_discount) }}
                                        </span>
                                        <span v-else-if="newItem.free_product">
                                            FREE: {{ newItem.free_pieces }} x {{ newItem.free_product.name }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <select v-if="discountSchemes.length > 1" 
                                @change="applyDiscountScheme(discountSchemes.find(s => s.id == $event.target.value))"
                                class="text-sm rounded-md border-gray-300">
                                <option v-for="scheme in discountSchemes" :key="scheme.id" :value="scheme.id">
                                    {{ scheme.name }} ({{ scheme.discount_type === 'amount_less' ? `Rs ${scheme.amount_less}` : `${scheme.free_pieces} FREE` }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- No product selected message -->
                    <div v-else class="text-center py-4 text-gray-400 text-sm">
                        Select a product to see pricing details
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
                                    <th class="px-2 py-3 text-right">Discount</th>
                                    <th class="px-2 py-3 text-right">Net</th>
                                    <th class="px-2 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, index) in form.items" :key="index">
                                    <td class="px-2 py-3">{{ index + 1 }}</td>
                                    <td class="px-2 py-3">
                                        <div class="font-medium">{{ item.product_name }}</div>
                                        <div class="text-xs text-gray-500">{{ item.product_code }}</div>
                                    </td>
                                    <td class="px-2 py-3 text-right">{{ item.cartons }}</td>
                                    <td class="px-2 py-3 text-right">{{ item.pieces }}</td>
                                    <td class="px-2 py-3 text-right font-medium">{{ item.total_pieces }}</td>
                                    <td class="px-2 py-3 text-right">{{ formatAmount(item.exclusive_price) }}</td>
                                    <td class="px-2 py-3 text-right text-gray-600">{{
                                        formatAmount(getItemExclusive(item)) }}</td>
                                    <td class="px-2 py-3 text-right text-gray-500">
                                        {{ formatAmount(getItemFed(item)) }}
                                        <div class="text-xs">({{ item.fed_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-gray-500">
                                        {{ formatAmount(getItemSalesTax(item)) }}
                                        <div class="text-xs">({{ item.sales_tax_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-purple-600">
                                        {{ formatAmount(getItemExtraTax(item)) }}
                                        <div class="text-xs">({{ item.extra_tax_percent || 0 }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-gray-500">
                                        {{ formatAmount(getItemAdvTax(item)) }}
                                        <div class="text-xs">({{ item.adv_tax_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right font-medium">{{ formatAmount(item.total_pieces *
                                        item.net_unit_price) }}</td>
                                    <td class="px-2 py-3 text-right text-red-600">-{{
                                        formatAmount(getItemDiscount(item)) }}</td>
                                    <td class="px-2 py-3 text-right font-semibold text-emerald-600">
                                        {{ formatAmount((item.total_pieces * item.net_unit_price) -
                                        getItemDiscount(item)) }}
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
                                        No items added. Use the form above to add products.
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
                                <div class="font-bold text-2xl text-emerald-700">Rs. {{ formatAmount(grandTotal) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('invoices.index')">
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton :disabled="form.processing || form.items.length === 0"
                        class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0">
                        {{ form.processing ? 'Saving...' : 'Save Invoice (F2)' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
