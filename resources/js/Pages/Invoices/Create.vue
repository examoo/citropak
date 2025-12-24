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
    price: 0,
    scheme_id: '',
    scheme_discount: 0
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

// Watch VAN change -> Load Order Bookers
watch(() => form.van_id, async (vanId) => {
    form.order_booker_id = '';
    form.customer_id = '';
    selectedCustomer.value = null;
    filteredCustomers.value = [];
    
    if (vanId) {
        try {
            const response = await axios.get(route('api.bookers-by-van', vanId));
            filteredBookers.value = response.data;
        } catch (e) {
            filteredBookers.value = [];
        }
    } else {
        filteredBookers.value = [];
    }
});

// Watch Order Booker + Day -> Load Customers
watch([() => form.order_booker_id, orderDay], async ([bookerId, day]) => {
    form.customer_id = '';
    selectedCustomer.value = null;
    
    if (bookerId) {
        try {
            const response = await axios.get(route('api.customers-by-booker', bookerId), {
                params: { day: day || undefined }
            });
            filteredCustomers.value = response.data;
        } catch (e) {
            filteredCustomers.value = [];
        }
    } else {
        filteredCustomers.value = [];
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

// Search product by code
const searchProductByCode = async () => {
    if (!productCode.value) return;
    try {
        const response = await axios.get(route('api.product-by-code', productCode.value));
        selectedProduct.value = response.data;
        newItem.value.product_id = response.data.id;
        newItem.value.price = response.data.net_trade_price || response.data.price || 0;
        loadProductSchemes(response.data.id);
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
            newItem.value.price = product.net_trade_price || product.price || 0;
            loadProductSchemes(productId);
            productCode.value = product.dms_code || product.sku || '';
        }
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
    const packing = selectedProduct.value?.packing?.quantity || 12; // Default 12 pieces per carton
    newItem.value.total_pieces = (newItem.value.cartons * packing) + newItem.value.pieces;
});

// Watch scheme selection -> Apply discount
watch(() => newItem.value.scheme_id, (schemeId) => {
    if (schemeId) {
        const scheme = productSchemes.value.find(s => s.id === parseInt(schemeId));
        if (scheme) {
            const baseAmount = newItem.value.total_pieces * newItem.value.price;
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
    
    form.items.push({
        product_id: newItem.value.product_id,
        product_name: product?.name,
        product_code: product?.dms_code || product?.sku,
        brand_name: product?.brand?.name,
        cartons: newItem.value.cartons,
        pieces: newItem.value.pieces,
        total_pieces: newItem.value.total_pieces,
        price: newItem.value.price,
        scheme_id: newItem.value.scheme_id || null,
        scheme_name: scheme?.product?.name || scheme?.brand?.name || null,
        scheme_discount: newItem.value.scheme_discount
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
        price: 0,
        scheme_id: '',
        scheme_discount: 0
    };
    selectedProduct.value = null;
    productCode.value = '';
    productSchemes.value = [];
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Calculate totals
const grandTotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const lineTotal = (item.total_pieces * item.price) - item.scheme_discount;
        return sum + lineTotal;
    }, 0);
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
                    <div class="grid grid-cols-2 md:grid-cols-8 gap-3 items-end">
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

                        <!-- Scheme -->
                        <div>
                            <InputLabel value="Scheme" />
                            <select v-model="newItem.scheme_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                <option value="">No Scheme</option>
                                <option v-for="scheme in productSchemes" :key="scheme.id" :value="scheme.id">
                                    {{ scheme.discount_value }}{{ scheme.discount_type === 'percentage' ? '%' : ' Rs' }}
                                </option>
                            </select>
                        </div>

                        <!-- Add Button -->
                        <div>
                            <button type="button" @click="addItem" :disabled="!newItem.product_id || newItem.total_pieces <= 0" class="w-full px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                + Add
                            </button>
                        </div>
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
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3 text-right">Ctns</th>
                                    <th class="px-4 py-3 text-right">Pcs</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                    <th class="px-4 py-3 text-right">Price</th>
                                    <th class="px-4 py-3">Scheme</th>
                                    <th class="px-4 py-3 text-right">Discount</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, index) in form.items" :key="index">
                                    <td class="px-4 py-3">{{ index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ item.product_name }}</div>
                                        <div class="text-xs text-gray-500">{{ item.product_code }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ item.cartons }}</td>
                                    <td class="px-4 py-3 text-right">{{ item.pieces }}</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ item.total_pieces }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatAmount(item.price) }}</td>
                                    <td class="px-4 py-3">{{ item.scheme_name || '-' }}</td>
                                    <td class="px-4 py-3 text-right text-red-600">-{{ formatAmount(item.scheme_discount) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-600">
                                        {{ formatAmount((item.total_pieces * item.price) - item.scheme_discount) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="form.items.length === 0">
                                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                        No items added. Use the form above to add products.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50 font-bold">
                                <tr>
                                    <td colspan="8" class="px-4 py-3 text-right">Grand Total:</td>
                                    <td class="px-4 py-3 text-right text-lg text-emerald-600">Rs. {{ formatAmount(grandTotal) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
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
