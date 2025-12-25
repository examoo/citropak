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
    nextOrderDate: String
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
    cartons: 0,
    pieces: 0,
    total_pieces: 0,
    exclusive_price: 0,      // Price before tax (list_price_before_tax)
    fed_percent: 0,          // FED %
    sales_tax_percent: 0,    // Sales Tax %
    adv_tax_percent: 0,      // Advance Tax % (from customer)
    net_unit_price: 0,       // Price with taxes
    scheme_id: '',
    scheme_discount: 0,
    manual_discount_percent: 0,  // Manual discount %
    manual_discount_amount: 0    // Manual discount amount (Rs)
});
const productSchemes = ref([]);

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
// Formula: Base Price * (1 + FED%) * (1 + Sales Tax%) * (1 + Advance Tax%)
const calculateNetUnitPrice = () => {
    const exclusive = parseFloat(newItem.value.exclusive_price) || 0;
    const fed = parseFloat(newItem.value.fed_percent) / 100 || 0;
    const salesTax = parseFloat(newItem.value.sales_tax_percent) / 100 || 0;
    const advTax = parseFloat(newItem.value.adv_tax_percent) / 100 || 0;
    
    // Compound calculation (same as Product):
    // Step 1: Base Price + FED %
    let netPrice = exclusive * (1 + fed);
    // Step 2: (Base Price + FED) + Sales Tax %
    netPrice = netPrice * (1 + salesTax);
    // Step 3: Add Advance Tax (customer specific)
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
watch(() => newItem.value.product_id, (productId) => {
    if (productId) {
        const product = props.products.find(p => p.id === parseInt(productId));
        if (product) {
            selectedProduct.value = product;
            // Use list_price_before_tax as exclusive price (before taxes)
            newItem.value.exclusive_price = parseFloat(product.list_price_before_tax) || 0;
            newItem.value.fed_percent = parseFloat(product.fed_percent) || 0;
            newItem.value.sales_tax_percent = parseFloat(product.fed_sales_tax) || 0;
            // Get advance tax from selected customer
            newItem.value.adv_tax_percent = parseFloat(selectedCustomer.value?.adv_tax_percent) || 0;
            // Use product's unit_price if available (already calculated with taxes)
            // Otherwise calculate from exclusive price + taxes
            if (product.unit_price && parseFloat(product.unit_price) > 0) {
                newItem.value.net_unit_price = parseFloat(product.unit_price);
            } else {
                calculateNetUnitPrice();
            }
            loadProductSchemes(productId);
            productCode.value = product.dms_code || product.sku || '';
        }
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

// Watch cartons/pieces -> Calculate total
watch([() => newItem.value.cartons, () => newItem.value.pieces], () => {
    // Use pieces_per_packing from new simplified structure
    const packing = selectedProduct.value?.pieces_per_packing || selectedProduct.value?.packing?.quantity || 12;
    newItem.value.total_pieces = (newItem.value.cartons * packing) + newItem.value.pieces;
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
    
    form.items.push({
        product_id: newItem.value.product_id,
        product_name: product?.name,
        product_code: product?.dms_code || product?.sku,
        brand_name: product?.brand?.name,
        cartons: newItem.value.cartons,
        pieces: newItem.value.pieces,
        total_pieces: newItem.value.total_pieces,
        exclusive_price: newItem.value.exclusive_price,
        fed_percent: newItem.value.fed_percent,
        sales_tax_percent: newItem.value.sales_tax_percent,
        adv_tax_percent: newItem.value.adv_tax_percent,
        net_unit_price: newItem.value.net_unit_price,
        price: newItem.value.net_unit_price, // For backward compatibility
        scheme_id: newItem.value.scheme_id || null,
        scheme_name: scheme?.product?.name || scheme?.brand?.name || null,
        scheme_discount: newItem.value.scheme_discount,
        manual_discount_percent: newItem.value.manual_discount_percent,
        manual_discount_amount: newItem.value.manual_discount_amount,
        total_discount: newItem.value.scheme_discount + totalManualDiscount
    });
    
    // Reset
    resetNewItem();
};

const resetNewItem = () => {
    newItem.value = {
        product_id: '',
        cartons: 0,
        pieces: 0,
        total_pieces: 0,
        exclusive_price: 0,
        fed_percent: 0,
        sales_tax_percent: 0,
        adv_tax_percent: selectedCustomer.value?.adv_tax_percent || 0,
        net_unit_price: 0,
        scheme_id: '',
        scheme_discount: 0,
        manual_discount_percent: 0,
        manual_discount_amount: 0
    };
    selectedProduct.value = null;
    productCode.value = '';
    productSchemes.value = [];
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
                            <SearchableSelect 
                                v-model="form.distribution_id"
                                label="Distribution"
                                :options="distributions"
                                option-value="id"
                                option-label="name"
                                placeholder="Select distribution"
                                required
                            />
                        </div>

                        <!-- VAN -->
                        <div>
                            <SearchableSelect 
                                v-model="form.van_id"
                                label="VAN"
                                :options="vanOptions"
                                option-value="id"
                                option-label="displayLabel"
                                placeholder="Select VAN"
                                :error="form.errors.van_id"
                                required
                            />
                        </div>

                        <!-- Order Booker -->
                        <div>
                            <SearchableSelect 
                                v-model="form.order_booker_id"
                                label="Order Booker"
                                :options="filteredBookers"
                                option-value="id"
                                option-label="name"
                                placeholder="Select booker"
                                :error="form.errors.order_booker_id"
                                required
                            />
                        </div>

                        <!-- Invoice Date -->
                        <div>
                            <InputLabel value="Invoice Date" />
                            <TextInput v-model="form.invoice_date" type="date" class="mt-1 block w-full" required />
                        </div>

                        <!-- Invoice Type -->
                        <div>
                            <InputLabel value="Invoice Type" />
                            <select v-model="form.invoice_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="sale">Sale</option>
                                <option value="damage">Damage</option>
                                <option value="shelf_rent">Shelf Rent</option>
                            </select>
                        </div>

                        <!-- Tax Type -->
                        <div>
                            <InputLabel value="Tax Type" />
                            <select v-model="form.tax_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="food">Food (18% ST + 4%)</option>
                                <option value="juice">Juice (18% ST + 20% FED)</option>
                            </select>
                        </div>

                        <!-- Credit -->
                        <div class="flex items-center gap-2 pt-6">
                            <input type="checkbox" v-model="form.is_credit" id="is_credit" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
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
                            <select v-model="orderDay" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">All Days</option>
                                <option v-for="day in dayOptions" :key="day" :value="day">{{ day }}</option>
                            </select>
                        </div>

                        <!-- Customer Selection -->
                        <div>
                            <SearchableSelect 
                                v-model="form.customer_id"
                                label="Customer"
                                :options="filteredCustomers"
                                option-value="id"
                                option-label="shop_name"
                                placeholder="Select customer"
                                :error="form.errors.customer_id"
                                required
                            />
                        </div>

                        <!-- Customer Code Search -->
                        <div>
                            <InputLabel value="Or Enter Code" />
                            <div class="flex gap-2 mt-1">
                                <TextInput v-model="customerCode" placeholder="Customer code" class="flex-1" @keyup.enter="searchCustomerByCode" />
                                <button type="button" @click="searchCustomerByCode" class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
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
                                <span class="font-medium ml-2" :class="selectedCustomer.atl ? 'text-green-600' : 'text-red-600'">
                                    {{ selectedCustomer.atl ? 'Yes' : 'No' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Entry -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Add Products</h2>
                    
                    <!-- Row 1: Product selection -->
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 items-end mb-4">
                        <!-- Product Code -->
                        <div>
                            <InputLabel value="Product Code" />
                            <TextInput v-model="productCode" placeholder="Enter code" class="mt-1" @keyup.enter="searchProductByCode" />
                        </div>

                        <!-- Product Select -->
                        <div class="md:col-span-2">
                            <SearchableSelect 
                                v-model="newItem.product_id"
                                label="Product"
                                :options="productOptions"
                                option-value="id"
                                option-label="displayLabel"
                                placeholder="Search..."
                            />
                        </div>

                        <!-- Cartons -->
                        <div>
                            <InputLabel value="Cartons" />
                            <TextInput v-model.number="newItem.cartons" type="number" min="0" class="mt-1" />
                        </div>

                        <!-- Pieces -->
                        <div>
                            <InputLabel value="Pieces" />
                            <TextInput v-model.number="newItem.pieces" type="number" min="0" class="mt-1" />
                        </div>

                        <!-- Total Pieces (readonly) -->
                        <div>
                            <InputLabel value="Total Pcs" />
                            <TextInput :value="newItem.total_pieces" type="number" class="mt-1 bg-gray-100" readonly />
                        </div>
                    </div>

                    <!-- Row 2: Pricing breakdown (visible when product selected) -->
                    <div v-if="newItem.product_id" class="grid grid-cols-2 md:grid-cols-7 gap-3 items-end mb-4 bg-gray-50 p-3 rounded-lg">
                        <!-- Exclusive Price -->
                        <div>
                            <InputLabel value="Excl. Price" class="text-xs" />
                            <TextInput v-model.number="newItem.exclusive_price" type="number" step="0.01" class="mt-1 text-sm" />
                        </div>

                        <!-- FED % -->
                        <div>
                            <InputLabel value="FED %" class="text-xs" />
                            <TextInput v-model.number="newItem.fed_percent" type="number" step="0.01" class="mt-1 text-sm" @input="calculateNetUnitPrice" />
                        </div>

                        <!-- Sales Tax % -->
                        <div>
                            <InputLabel value="S.Tax %" class="text-xs" />
                            <TextInput v-model.number="newItem.sales_tax_percent" type="number" step="0.01" class="mt-1 text-sm" @input="calculateNetUnitPrice" />
                        </div>

                        <!-- Advance Tax % -->
                        <div>
                            <InputLabel value="Adv.Tax %" class="text-xs" />
                            <TextInput v-model.number="newItem.adv_tax_percent" type="number" step="0.01" class="mt-1 text-sm" @input="calculateNetUnitPrice" />
                        </div>

                        <!-- Net Unit Price -->
                        <div>
                            <InputLabel value="Net Price" class="text-xs font-bold text-indigo-600" />
                            <TextInput :value="newItem.net_unit_price.toFixed(2)" type="text" class="mt-1 bg-indigo-50 font-bold text-indigo-700 text-sm" readonly />
                        </div>

                        <!-- Scheme -->
                        <div>
                            <InputLabel value="Scheme" class="text-xs" />
                            <select v-model="newItem.scheme_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                <option value="">No Scheme</option>
                                <option v-for="scheme in productSchemes" :key="scheme.id" :value="scheme.id">
                                    {{ scheme.discount_value }}{{ scheme.discount_type === 'percentage' ? '%' : ' Rs' }}
                                </option>
                            </select>
                        </div>

                        <!-- Manual Discount % -->
                        <div>
                            <InputLabel value="Disc. %" class="text-xs" />
                            <TextInput v-model.number="newItem.manual_discount_percent" type="number" step="0.01" min="0" max="100" class="mt-1 text-sm" placeholder="0" />
                        </div>

                        <!-- Manual Discount Amount -->
                        <div>
                            <InputLabel value="Disc. Rs" class="text-xs" />
                            <TextInput v-model.number="newItem.manual_discount_amount" type="number" step="0.01" min="0" class="mt-1 text-sm" placeholder="0" />
                        </div>

                        <!-- Add Button -->
                        <div>
                            <button type="button" @click="addItem" :disabled="!newItem.product_id || newItem.total_pieces <= 0" class="w-full px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                + Add
                            </button>
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
                                    <td class="px-2 py-3 text-right text-gray-600">{{ formatAmount(getItemExclusive(item)) }}</td>
                                    <td class="px-2 py-3 text-right text-gray-500">
                                        {{ formatAmount(getItemFed(item)) }}
                                        <div class="text-xs">({{ item.fed_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-gray-500">
                                        {{ formatAmount(getItemSalesTax(item)) }}
                                        <div class="text-xs">({{ item.sales_tax_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-gray-500">
                                        {{ formatAmount(getItemAdvTax(item)) }}
                                        <div class="text-xs">({{ item.adv_tax_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right font-medium">{{ formatAmount(item.total_pieces * item.net_unit_price) }}</td>
                                    <td class="px-2 py-3 text-right text-red-600">-{{ formatAmount(getItemDiscount(item)) }}</td>
                                    <td class="px-2 py-3 text-right font-semibold text-emerald-600">
                                        {{ formatAmount((item.total_pieces * item.net_unit_price) - getItemDiscount(item)) }}
                                    </td>
                                    <td class="px-2 py-3">
                                        <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="form.items.length === 0">
                                    <td colspan="14" class="px-4 py-8 text-center text-gray-500">
                                        No items added. Use the form above to add products.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Invoice Summary -->
                    <div v-if="form.items.length > 0" class="border-t border-gray-200 bg-gray-50 p-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
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
                    <Link :href="route('invoices.index')">
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton 
                        :disabled="form.processing || form.items.length === 0"
                        class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Invoice (F2)' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
