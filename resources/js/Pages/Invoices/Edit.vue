<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    invoice: Object,
    products: Array,
    schemes: Array
});

// Form with existing data
const form = useForm({
    invoice_type: props.invoice.invoice_type,
    tax_type: props.invoice.tax_type,
    is_credit: props.invoice.is_credit,
    notes: props.invoice.notes || '',
    items: props.invoice.items.map(item => ({
        id: item.id,
        product_id: item.product_id,
        product_name: item.product?.name,
        product_code: item.product?.dms_code || item.product?.sku,
        cartons: item.cartons,
        pieces: item.pieces,
        total_pieces: item.total_pieces,
        price: item.price,
        scheme_id: item.scheme_id,
        scheme_name: item.scheme?.product?.name || item.scheme?.brand?.name || null,
        scheme_discount: item.scheme_discount
    }))
});

// Product options
const productOptions = computed(() => {
    return props.products.map(p => ({
        ...p,
        displayLabel: `${p.dms_code || p.sku || ''} - ${p.name}`
    }));
});

// New item entry
const newItem = ref({
    product_id: '',
    cartons: 0,
    pieces: 0,
    total_pieces: 0,
    price: 0,
    scheme_id: '',
    scheme_discount: 0
});
const selectedProduct = ref(null);
const productSchemes = ref([]);

// Watch product selection
watch(() => newItem.value.product_id, (productId) => {
    if (productId) {
        const product = props.products.find(p => p.id === parseInt(productId));
        if (product) {
            selectedProduct.value = product;
            newItem.value.price = product.net_trade_price || product.price || 0;
            // Load schemes for this product
            productSchemes.value = props.schemes.filter(s =>
                s.product_id === product.id || s.brand_id === product.brand_id
            );
        }
    }
});

// Watch cartons/pieces
watch([() => newItem.value.cartons, () => newItem.value.pieces], () => {
    const packing = selectedProduct.value?.packing?.quantity || 12;
    newItem.value.total_pieces = (newItem.value.cartons * packing) + newItem.value.pieces;
});

// Watch scheme
watch(() => newItem.value.scheme_id, (schemeId) => {
    if (schemeId) {
        const scheme = productSchemes.value.find(s => s.id === parseInt(schemeId));
        if (scheme) {
            const baseAmount = newItem.value.total_pieces * newItem.value.price;
            newItem.value.scheme_discount = scheme.discount_type === 'percentage'
                ? baseAmount * (scheme.discount_value / 100)
                : scheme.discount_value * newItem.value.total_pieces;
        }
    } else {
        newItem.value.scheme_discount = 0;
    }
});

// If type changes to damage, recalculate all items to zero
watch(() => form.invoice_type, (type) => {
    if (type === 'damage') {
        form.items.forEach(item => {
            item.price = 0;
            item.scheme_discount = 0;
        });
    }
});

const addItem = () => {
    if (!newItem.value.product_id || newItem.value.total_pieces <= 0) return;

    const product = props.products.find(p => p.id === parseInt(newItem.value.product_id));
    const scheme = productSchemes.value.find(s => s.id === parseInt(newItem.value.scheme_id));

    form.items.push({
        id: null,
        product_id: newItem.value.product_id,
        product_name: product?.name,
        product_code: product?.dms_code || product?.sku,
        cartons: newItem.value.cartons,
        pieces: newItem.value.pieces,
        total_pieces: newItem.value.total_pieces,
        price: newItem.value.price,
        scheme_id: newItem.value.scheme_id || null,
        scheme_name: scheme?.product?.name || scheme?.brand?.name || null,
        scheme_discount: newItem.value.scheme_discount
    });

    // Reset
    newItem.value = { product_id: '', cartons: 0, pieces: 0, total_pieces: 0, price: 0, scheme_id: '', scheme_discount: 0 };
    selectedProduct.value = null;
    productSchemes.value = [];
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Format amount
const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount || 0);
};

// Grand total
const grandTotal = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + ((item.total_pieces * item.price) - item.scheme_discount);
    }, 0);
});

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
        <div class="space-y-6 max-w-6xl">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Invoice: {{ invoice.invoice_number }}</h1>
                    <p class="text-gray-500 mt-1">Modify invoice details. Press F2 to save.</p>
                </div>
                <Link :href="route('invoices.show', invoice.id)" class="text-gray-500 hover:text-gray-700">← Back</Link>
            </div>

            <!-- Customer Info Card -->
            <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div><span class="text-gray-500">Customer:</span> <span class="font-medium">{{
                            invoice.customer?.shop_name }}</span></div>
                    <div><span class="text-gray-500">Code:</span> <span class="font-medium">{{
                        invoice.customer?.customer_code }}</span></div>
                    <div><span class="text-gray-500">VAN:</span> <span class="font-medium">{{ invoice.van?.code
                            }}</span></div>
                    <div><span class="text-gray-500">Order Booker:</span> <span class="font-medium">{{
                            invoice.order_booker?.name }}</span></div>
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
                            <p v-if="form.invoice_type === 'damage'" class="text-xs text-red-600 mt-1">All prices will
                                be set to zero</p>
                        </div>

                        <div class="flex items-center gap-2 pt-6">
                            <input type="checkbox" v-model="form.is_credit" id="is_credit"
                                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="is_credit" class="text-sm text-gray-700">Credit Sale</label>
                        </div>

                        <div>
                            <InputLabel value="Notes" />
                            <textarea v-model="form.notes" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Add New Product -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Add More Products</h2>
                    <div class="grid grid-cols-2 md:grid-cols-7 gap-3 items-end">
                        <div class="md:col-span-2">
                            <SearchableSelect v-model="newItem.product_id" label="Product" :options="productOptions"
                                option-value="id" option-label="displayLabel" placeholder="Search..." />
                        </div>
                        <div>
                            <InputLabel value="Ctns" />
                            <TextInput v-model.number="newItem.cartons" type="number" min="0" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Pcs" />
                            <TextInput v-model.number="newItem.pieces" type="number" min="0" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Total" />
                            <TextInput :value="newItem.total_pieces" class="mt-1 bg-gray-100" readonly />
                        </div>
                        <div>
                            <InputLabel value="Scheme" />
                            <select v-model="newItem.scheme_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                <option value="">None</option>
                                <option v-for="s in productSchemes" :key="s.id" :value="s.id">{{ s.discount_value }}{{
                                    s.discount_type === 'percentage' ? '%' : ' Rs' }}</option>
                            </select>
                        </div>
                        <div>
                            <button type="button" @click="addItem"
                                :disabled="!newItem.product_id || newItem.total_pieces <= 0"
                                class="w-full px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">+
                                Add</button>
                        </div>
                    </div>
                </div>

                <!-- Items Grid -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b bg-gray-50">
                        <h2 class="text-lg font-medium">Items ({{ form.items.length }})</h2>
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
                                    <td class="px-4 py-3 text-right text-red-600">-{{ formatAmount(item.scheme_discount)
                                        }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-600">{{
                                        formatAmount((item.total_pieces * item.price) - item.scheme_discount) }}</td>
                                    <td class="px-4 py-3">
                                        <button type="button" @click="removeItem(index)"
                                            class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50 font-bold">
                                <tr>
                                    <td colspan="8" class="px-4 py-3 text-right">Grand Total:</td>
                                    <td class="px-4 py-3 text-right text-lg text-emerald-600">Rs. {{
                                        formatAmount(grandTotal) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
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
